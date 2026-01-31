<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Exception;
use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Feed\Reader;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text as Txt;

/**
 * @brief   The feed import module handler.
 * @ingroup importExport
 */
class ModuleImportFeed extends Module
{
    /**
     * Current status.
     *
     * @var     bool    $status
     */
    protected $status = false;

    /**
     * Feed URL.
     *
     * @var     string  $feed_url
     */
    protected $feed_url = '';

    /**
     * Get AAAA record for $host.
     *
     * If $try_a is true, if AAAA fails, it tries for A
     * The first match found is returned otherwise returns false
     *
     * IPv6 functions (from https://gist.github.com/tbaschak/7866688)
     *
     * @param   string  $host   The host
     * @param   bool    $try_a  The try a
     */
    private function gethostbyname6(string $host, bool $try_a = false): false|string
    {
        $dns = $this->gethostbynamel6($host, $try_a);
        if (!$dns) {
            return false;
        }

        return $dns[0];
    }

    /**
     * Get AAAA records for $host.
     *
     * If $try_a is true, if AAAA fails, it tries for A
     * Results are returned in an array of ips found matching type otherwise returns false
     *
     * @param   string  $host   The host
     * @param   bool    $try_a  The try a
     *
     * @return  array<array-key, string>|false
     */
    private function gethostbynamel6(string $host, bool $try_a = false): false|array
    {
        $dns  = [];
        $dns6 = dns_get_record($host, DNS_AAAA);
        if ($try_a) {
            $dns4 = dns_get_record($host, DNS_A);
            if ($dns4 !== false) {
                $dns = $dns4;
                if ($dns6 !== false) {
                    $dns = [...$dns4, ...$dns6];
                }
            }
        } elseif ($dns6 !== false) {
            $dns = $dns6;
        }

        /**
         * @var array<array-key, string>
         */
        $ip6 = [];

        /**
         * @var array<array-key, string>
         */
        $ip4 = [];

        foreach ($dns as $record) {
            if ($record['type'] === 'A' && is_string($record['ip'])) {
                $ip4[] = $record['ip'];
            }
            if ($record['type'] == 'AAAA' && is_string($record['ipv6'])) {
                $ip6[] = $record['ipv6'];
            }
        }
        if ($ip6 === []) {
            if ($try_a) {
                if ($ip4 === []) {
                    return false;
                }

                return $ip4;
            }

            return false;
        }

        return $ip6;
    }

    public function setInfo(): void
    {
        $this->type        = 'import';
        $this->name        = __('RSS or Atom feed import');
        $this->description = __('Add a feed content to the blog.');
    }

    public function process(string $do): void
    {
        if ($do === 'ok') {
            $this->status = true;

            return;
        }

        if (empty($_POST['feed_url']) || !is_string($_POST['feed_url'])) {
            return;
        }

        $this->feed_url = $_POST['feed_url'];

        // Check feed URL
        if (App::blog()->settings()->system->import_feed_url_control) {
            // Get IP from URL
            $bits = parse_url((string) $this->feed_url);
            if (!$bits || !isset($bits['host'])) {
                throw new Exception(__('Cannot retrieve feed URL.'));
            }
            $ip = gethostbyname($bits['host']);
            if ($ip === $bits['host']) {
                $ip = $this->gethostbyname6($bits['host']);
                if (!$ip) {
                    throw new Exception(__('Cannot retrieve feed URL.'));
                }
            }
            // Check feed IP
            $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
            if (App::blog()->settings()->system->import_feed_no_private_ip) {
                $flag |= FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            }
            if (!filter_var($ip, $flag)) {
                throw new Exception(__('Cannot retrieve feed URL.'));
            }

            // IP control (white list regexp)
            $ip_regexp = is_string($ip_regexp = App::blog()->settings()->system->import_feed_ip_regexp) ? $ip_regexp : '';
            if ($ip_regexp !== '' && !preg_match($ip_regexp, $ip)) {
                throw new Exception(__('Cannot retrieve feed URL.'));
            }

            // Port control (white list regexp)
            $port_regexp = is_string($port_regexp = App::blog()->settings()->system->import_feed_port_regexp) ? $port_regexp : '';
            if ($port_regexp !== '' && isset($bits['port']) && !preg_match($port_regexp, (string) $bits['port'])) {
                throw new Exception(__('Cannot retrieve feed URL.'));
            }
        }

        $feed = Reader::quickParse($this->feed_url);
        if ($feed === false) {
            throw new Exception(__('Cannot retrieve feed URL.'));
        }
        if (count($feed->items) === 0) {
            throw new Exception(__('No items in feed.'));
        }

        $cur = App::blog()->openPostCursor();
        App::db()->con()->begin();
        foreach ($feed->items as $item) {
            $content = is_string($item->content) ? $item->content : (is_string($item->description) ? $item->description : '');
            $title   = is_string($item->title) ? $item->title : Txt::cutString(Html::clean($content), 60);
            $ts      = is_numeric($item->TS) ? (int) $item->TS : null;

            $cur->clean();
            $cur->user_id      = App::auth()->userID();
            $cur->post_content = $content;
            $cur->post_title   = $title;
            $cur->post_format  = 'xhtml';
            $cur->post_status  = App::status()->post()::PENDING;
            $cur->post_dt      = Date::strftime('%Y-%m-%d %H:%M:%S', $ts);

            try {
                $post_id = App::blog()->addPost($cur);
            } catch (Exception $e) {
                App::db()->con()->rollback();

                throw $e;
            }

            /**
             * @var array<array-key, string>
             */
            $subjects = is_array($item->subject) ? $item->subject : [];
            foreach ($subjects as $subject) {
                App::meta()->setPostMeta($post_id, 'tag', App::meta()::sanitizeMetaID($subject));
            }
        }

        App::db()->con()->commit();
        Http::redirect($this->getURL() . '&do=ok');
    }

    public function gui(): void
    {
        if ($this->status) {
            App::backend()->notices()->success(__('Content successfully imported.'));
        }

        echo (new Form('ie-form'))
            ->method('post')
            ->action($this->getURL(true))
            ->fields([
                (new Para())
                    ->items([
                        (new Text(null, sprintf(__('Add a feed content to the current blog: <strong>%s</strong>.'), Html::escapeHTML(App::blog()->name())))),
                    ]),
                (new Para())
                    ->items([
                        (new Url('feed_url'))
                            ->size(50)
                            ->maxlength(300)
                            ->value(Html::escapeHTML($this->feed_url))
                            ->label((new Label(__('Feed URL:'), Label::OUTSIDE_TEXT_BEFORE))),
                    ]),
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        ...My::hiddenFields(),
                        (new Hidden(['do'], '1')),
                        (new Submit('ie-form-submit', __('Import'))),
                    ]),
            ])
        ->render();
    }
}
