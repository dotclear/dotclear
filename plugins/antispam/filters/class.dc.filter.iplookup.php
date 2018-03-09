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

if (!defined('DC_RC_PATH')) {return;}

class dcFilterIpLookup extends dcSpamFilter
{
    public $name    = 'IP Lookup';
    public $has_gui = true;
    public $help    = 'iplookup-filter';

    private $default_bls = 'sbl-xbl.spamhaus.org , bsb.spamlookup.net';

    public function __construct($core)
    {
        parent::__construct($core);

        if (defined('DC_DNSBL_SUPER') && DC_DNSBL_SUPER && !$core->auth->isSuperAdmin()) {
            $this->has_gui = false;
        }
    }

    protected function setInfo()
    {
        $this->description = __('Checks sender IP address against DNSBL servers');
    }

    public function getStatusMessage($status, $comment_id)
    {
        return sprintf(__('Filtered by %1$s with server %2$s.'), $this->guiLink(), $status);
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if (!$ip || long2ip(ip2long($ip)) != $ip) {
            return;
        }

        $bls = $this->getServers();
        $bls = preg_split('/\s*,\s*/', $bls);

        foreach ($bls as $bl) {
            if ($this->dnsblLookup($ip, $bl)) {
                // Pass by reference $status to contain matching DNSBL
                $status = $bl;
                return true;
            }
        }
    }

    public function gui($url)
    {
        $bls = $this->getServers();

        if (isset($_POST['bls'])) {
            try {
                $this->core->blog->settings->addNamespace('antispam');
                $this->core->blog->settings->antispam->put('antispam_dnsbls', $_POST['bls'], 'string', 'Antispam DNSBL servers', true, false);
                dcPage::addSuccessNotice(__('The list of DNSBL servers has been succesfully updated.'));
                http::redirect($url);
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }

        /* DISPLAY
        ---------------------------------------------- */
        $res = dcPage::notices();

        $res .=
        '<form action="' . html::escapeURL($url) . '" method="post" class="fieldset">' .
        '<h3>' . __('IP Lookup servers') . '</h3>' .
        '<p><label for="bls">' . __('Add here a coma separated list of servers.') . '</label>' .
        form::textarea('bls', 40, 3, html::escapeHTML($bls), 'maximal') .
        '</p>' .
        '<p><input type="submit" value="' . __('Save') . '" />' .
        $this->core->formNonce() . '</p>' .
            '</form>';

        return $res;
    }

    private function getServers()
    {
        $bls = $this->core->blog->settings->antispam->antispam_dnsbls;
        if ($bls === null) {
            $this->core->blog->settings->addNamespace('antispam');
            $this->core->blog->settings->antispam->put('antispam_dnsbls', $this->default_bls, 'string', 'Antispam DNSBL servers', true, false);
            return $this->default_bls;
        }

        return $bls;
    }

    private function dnsblLookup($ip, $bl)
    {
        $revIp = implode('.', array_reverse(explode('.', $ip)));

        $host = $revIp . '.' . $bl . '.';
        if (gethostbyname($host) != $host) {
            return true;
        }

        return false;
    }
}
