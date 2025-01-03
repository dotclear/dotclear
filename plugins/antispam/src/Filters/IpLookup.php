<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam\Filters;

use Dotclear\Core\Backend\Notices;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @brief   The module IP lookup spam filter.
 * @ingroup antispam
 */
class IpLookup extends SpamFilter
{
    /**
     * Filter id.
     */
    public string $id = 'dcFilterIpLookup';

    /**
     * Filter name.
     */
    public string $name = 'IP Lookup';

    /**
     * Filter has settings GUI?
     */
    public bool $has_gui = true;

    /**
     * Filter help ID.
     */
    public ?string $help = 'iplookup-filter';

    /**
     * DNS blacklist lookup default domains.
     */
    private string $default_bls = 'sbl-xbl.spamhaus.org , bsb.spamlookup.net';

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();

        if (defined('DC_DNSBL_SUPER') && constant('DC_DNSBL_SUPER') && !App::auth()->isSuperAdmin()) {
            $this->has_gui = false;
        }
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo(): void
    {
        $this->description = __('Checks sender IP address against DNSBL servers');
    }

    /**
     * Gets the status message.
     *
     * @param   string  $status         The status
     * @param   int     $comment_id     The comment identifier
     *
     * @return  string  The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return sprintf(__('Filtered by %1$s with server %2$s.'), $this->guiLink(), $status);
    }

    /**
     * This method should return if a comment is a spam or not.
     *
     * If it returns true or false, execution of next filters will be stoped.
     * If should return nothing to let next filters apply.
     *
     * @param   string  $type       The comment type (comment / trackback)
     * @param   string  $author     The comment author
     * @param   string  $email      The comment author email
     * @param   string  $site       The comment author site
     * @param   string  $ip         The comment author IP
     * @param   string  $content    The comment content
     * @param   int     $post_id    The comment post_id
     * @param   string  $status     The comment status
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status): ?bool
    {
        if (!$ip) {
            // No IP given
            return null;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE)) {
            // Not an IPv4 IP (excludind private range) not an IPv6 IP (excludind private range)
            return null;
        }

        $bls = array_map(fn ($v): string => trim($v), explode(',', $this->getServers()));

        foreach ($bls as $bl) {
            if ($this->dnsblLookup($ip, $bl)) {
                // Pass by reference $status to contain matching DNSBL
                $status = $bl;

                return true;
            }
        }

        return null;
    }

    /**
     * Filter settings.
     *
     * @param   string  $url    The GUI URL
     */
    public function gui(string $url): string
    {
        $bls = $this->getServers();

        if (isset($_POST['bls'])) {
            try {
                App::blog()->settings()->antispam->put('antispam_dnsbls', $_POST['bls'], 'string', 'Antispam DNSBL servers', true, false);
                Notices::addSuccessNotice(__('The list of DNSBL servers has been succesfully updated.'));
                Http::redirect($url);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Return form
        return (new Form('iplookup_form'))
            ->action(Html::escapeURL($url))
            ->method('post')
            ->fields([
                (new Fieldset())
                    ->legend((new Legend(__('IP Lookup servers'))))
                    ->items([
                        (new Para())
                        ->items([
                            (new Textarea('bls'))
                                ->label(new Label(__('Add here a coma separated list of servers.'), Label::INSIDE_LABEL_AFTER))
                                ->cols(60)
                                ->rows(3)
                                ->value(Html::escapeHTML($bls))
                                ->class('maximal'),
                        ]),
                        (new Para())
                        ->items([
                            (new Submit('iplookup_save', __('Save'))),
                            App::nonce()->formNonce(),
                        ]),
                    ]),
            ])
        ->render();
    }

    /**
     * Gets the servers.
     */
    private function getServers(): string
    {
        $bls = App::blog()->settings()->antispam->antispam_dnsbls;
        if ($bls === null) {
            App::blog()->settings()->antispam->put('antispam_dnsbls', $this->default_bls, 'string', 'Antispam DNSBL servers', true, false);

            return $this->default_bls;
        }

        return $bls;
    }

    /**
     * Check IP.
     *
     * @param   string  $ip     The IP
     * @param   string  $bl     The list of servers
     */
    private function dnsblLookup(string $ip, string $bl): bool
    {
        $revIp = implode('.', array_reverse(explode('.', $ip)));

        $host = $revIp . '.' . $bl . '.';

        return gethostbyname($host) !== $host;
    }
}
