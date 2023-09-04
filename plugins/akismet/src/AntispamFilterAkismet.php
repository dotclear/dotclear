<?php
/**
 * @brief akismet, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\akismet;

use Dotclear\Core\Backend\Notices;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;
use form;

class AntispamFilterAkismet extends SpamFilter
{
    /**
     * Filter id
     *
     * @var        string
     */
    public $id = 'dcFilterAkismet';

    /**
     * Filter name
     *
     * @var        string
     */
    public $name = 'Akismet';

    /**
     * Has GUI settings
     *
     * @var        bool
     */
    public $has_gui = true;

    /**
     * Filter active?
     *
     * @var        bool
     */
    public $active = false;

    /**
     * Filter help resource ID
     *
     * @var        string
     */
    public $help = 'akismet-filter';

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();

        if (defined('DC_AKISMET_SUPER') && DC_AKISMET_SUPER && !App::auth()->isSuperAdmin()) {
            $this->has_gui = false;
        }
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo()
    {
        $this->description = __('Akismet spam filter');
    }

    /**
     * Gets the status message.
     *
     * @param      string  $status      The status
     * @param      int     $comment_id  The comment identifier
     *
     * @return     string  The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return sprintf(__('Filtered by %s.'), $this->guiLink());
    }

    /**
     * Return a new akismet instance of false if API key not defined
     *
     * @return     Akismet|bool
     */
    private function akInit()
    {
        if (!My::settings()->ak_key) {
            return false;
        }

        return new Akismet(App::blog()->url(), My::settings()->ak_key);
    }

    /**
     * This method should return if a comment is a spam or not. If it returns true
     * or false, execution of next filters will be stoped. If should return nothing
     * to let next filters apply.
     *
     * @param      string   $type     The comment type (comment / trackback)
     * @param      string   $author   The comment author
     * @param      string   $email    The comment author email
     * @param      string   $site     The comment author site
     * @param      string   $ip       The comment author IP
     * @param      string   $content  The comment content
     * @param      int      $post_id  The comment post_id
     * @param      string   $status   The comment status
     *
     * @return  mixed
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if (($ak = $this->akInit()) === false) {
            return;
        }

        try {
            if ($ak->verify()) {
                $post = App::blog()->getPosts(['post_id' => $post_id]);

                $c = $ak->comment_check(
                    $post->getURL(),
                    $type,
                    $author,
                    $email,
                    $site,
                    $content
                );

                if ($c) {
                    $status = 'Filtered by Akismet';

                    return true;
                }
            }
        } catch (Exception $e) {
            // If http or akismet is dead, we don't need to know it
        }
    }

    /**
     * Train the antispam filter
     *
     * @param      string        $status   The comment status
     * @param      string        $filter   The filter
     * @param      string        $type     The comment type
     * @param      string        $author   The comment author
     * @param      string        $email    The comment author email
     * @param      string        $site     The comment author site
     * @param      string        $ip       The comment author IP
     * @param      string        $content  The comment content
     * @param      MetaRecord      $rs       The comment record
     */
    public function trainFilter(string $status, string $filter, string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, MetaRecord $rs)
    {
        # We handle only false positive from akismet
        if ($status === 'spam' && $filter !== 'dcFilterAkismet') {
            return;
        }

        $f = $status === 'spam' ? 'submit_spam' : 'submit_ham';

        if (($ak = $this->akInit()) === false) {
            return;
        }

        try {
            if ($ak->verify()) {
                $ak->{$f}($rs->getPostURL(), $type, $author, $email, $site, $content);
            }
        } catch (Exception $e) {
            // If http or akismet is dead, we don't need to know it
        }
    }

    /**
     * Filter settings
     *
     * @param      string  $url    The GUI URL
     *
     * @return     string
     */
    public function gui($url): string
    {
        $ak_key      = My::settings()->ak_key;
        $ak_verified = null;

        if (isset($_POST['ak_key'])) {
            try {
                $ak_key = $_POST['ak_key'];

                My::settings()->put('ak_key', $ak_key, 'string');

                Notices::addSuccessNotice(__('Filter configuration have been successfully saved.'));
                Http::redirect($url);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (My::settings()->ak_key) {
            try {
                $ak          = new Akismet(App::blog()->url(), My::settings()->ak_key);
                $ak_verified = $ak->verify();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        $res = Notices::getNotices();

        $res .= '<form action="' . Html::escapeURL($url) . '" method="post" class="fieldset">' .
        '<p><label for="ak_key" class="classic">' . __('Akismet API key:') . '</label> ' .
        form::field('ak_key', 12, 128, $ak_key);

        if ($ak_verified !== null) {
            if ($ak_verified) {
                $res .= ' <img src="images/check-on.png" alt="" /> ' . __('API key verified');
            } else {
                $res .= ' <img src="images/check-off.png" alt="" /> ' . __('API key not verified');
            }
        }

        $res .= '</p>';

        $res .= '<p><a href="https://akismet.com/">' . __('Get your own API key') . '</a></p>' .
        '<p><input type="submit" value="' . __('Save') . '" />' .
        App::nonce()->getFormNonce() . '</p>' .
            '</form>';

        return $res;
    }
}
