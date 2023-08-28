<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam\Filters;

use dcCore;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Core;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;
use form;

class IpLookup extends SpamFilter
{
    /**
     * Filter id
     *
     * @var        string
     */
    public $id = 'dcFilterIpLookup';

    /**
     * Filter name
     *
     * @var        string
     */
    public $name = 'IP Lookup';

    /**
     * Filter has GUI
     *
     * @var        bool
     */
    public $has_gui = true;

    /**
     * Filter help ID
     *
     * @var        string
     */
    public $help = 'iplookup-filter';

    /**
     * DNS blacklist lookup default domains
     */
    private string $default_bls = 'sbl-xbl.spamhaus.org , bsb.spamlookup.net';

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();

        if (defined('DC_DNSBL_SUPER') && DC_DNSBL_SUPER && !Core::auth()->isSuperAdmin()) {
            $this->has_gui = false;
        }
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo()
    {
        $this->description = __('Checks sender IP address against DNSBL servers');
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
        return sprintf(__('Filtered by %1$s with server %2$s.'), $this->guiLink(), $status);
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
        if (!$ip) {
            // No IP given
            return;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE)) {
            // Not an IPv4 IP (excludind private range) not an IPv6 IP (excludind private range)
            return;
        }

        $bls = array_map(fn ($v) => trim($v), explode(',', $this->getServers()));

        foreach ($bls as $bl) {
            if ($this->dnsblLookup($ip, $bl)) {
                // Pass by reference $status to contain matching DNSBL
                $status = $bl;

                return true;
            }
        }
    }

    /**
     * Filter settings
     *
     * @param      string  $url    The GUI URL
     *
     * @return     string
     */
    public function gui(string $url): string
    {
        $bls = $this->getServers();

        if (isset($_POST['bls'])) {
            try {
                Core::blog()->settings->antispam->put('antispam_dnsbls', $_POST['bls'], 'string', 'Antispam DNSBL servers', true, false);
                Notices::addSuccessNotice(__('The list of DNSBL servers has been succesfully updated.'));
                Http::redirect($url);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        /* DISPLAY
        ---------------------------------------------- */
        return
        '<form action="' . Html::escapeURL($url) . '" method="post" class="fieldset">' .
            '<h3>' . __('IP Lookup servers') . '</h3>' .
            '<p><label for="bls">' . __('Add here a coma separated list of servers.') . '</label>' .
                form::textarea('bls', 40, 3, Html::escapeHTML($bls), 'maximal') .
            '</p>' .
            '<p><input type="submit" value="' . __('Save') . '" />' . Core::nonce()->getFormNonce() . '</p>' .
        '</form>';
    }

    /**
     * Gets the servers.
     *
     * @return     string  The servers.
     */
    private function getServers(): string
    {
        $bls = Core::blog()->settings->antispam->antispam_dnsbls;
        if ($bls === null) {
            Core::blog()->settings->antispam->put('antispam_dnsbls', $this->default_bls, 'string', 'Antispam DNSBL servers', true, false);

            return $this->default_bls;
        }

        return $bls;
    }

    /**
     * Check IP
     *
     * @param      string  $ip     The IP
     * @param      string  $bl     The list of servers
     *
     * @return     bool
     */
    private function dnsblLookup(string $ip, string $bl): bool
    {
        $revIp = implode('.', array_reverse(explode('.', $ip)));

        $host = $revIp . '.' . $bl . '.';
        if (gethostbyname($host) != $host) {
            return true;
        }

        return false;
    }
}
