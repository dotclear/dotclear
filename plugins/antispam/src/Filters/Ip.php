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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Core;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\Antispam;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;
use form;

class Ip extends SpamFilter
{
    /**
     * Filter id
     *
     * @var        string
     */
    public $id = 'dcFilterIP';

    /**
     * Filter name
     *
     * @var        string
     */
    public $name = 'IP Filter';

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
    public $help = 'ip-filter';

    /**
     * Table name
     */
    private string $table;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Core::con()->prefix() . Antispam::SPAMRULE_TABLE_NAME;
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo()
    {
        $this->description = __('IP Blocklist / Allowlist Filter');
    }

    /**
     * Gets the status message.
     *
     * @param      string    $status      The status
     * @param      int       $comment_id  The comment identifier
     *
     * @return     string    The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return sprintf(__('Filtered by %1$s with rule %2$s.'), $this->guiLink(), $status);
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
            return;
        }

        # White list check
        if ($this->checkIP($ip, 'white') !== false) {
            return false;
        }

        # Black list check
        if (($s = $this->checkIP($ip, 'black')) !== false) {
            $status = $s;

            return true;
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
        # Set current type and tab
        $ip_type = 'black';
        if (!empty($_REQUEST['ip_type']) && $_REQUEST['ip_type'] == 'white') {
            $ip_type = 'white';
        }
        Core::backend()->default_tab = 'tab_' . $ip_type;

        # Add IP to list
        if (!empty($_POST['addip'])) {
            try {
                $global = !empty($_POST['globalip']) && Core::auth()->isSuperAdmin();

                $this->addIP($ip_type, $_POST['addip'], $global);
                Notices::addSuccessNotice(__('IP address has been successfully added.'));
                Http::redirect($url . '&ip_type=' . $ip_type);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        # Remove IP from list
        if (!empty($_POST['delip']) && is_array($_POST['delip'])) {
            try {
                $this->removeRule($_POST['delip']);
                Notices::addSuccessNotice(__('IP addresses have been successfully removed.'));
                Http::redirect($url . '&ip_type=' . $ip_type);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        /* DISPLAY
        ---------------------------------------------- */
        return
        $this->displayForms($url, 'black', __('Blocklist')) .
        $this->displayForms($url, 'white', __('Allowlist'));
    }

    /**
     * Return black/white list form
     *
     * @param      string  $url    The url
     * @param      string  $type   The type
     * @param      string  $title  The title
     *
     * @return     string
     */
    private function displayForms(string $url, string $type, string $title): string
    {
        $res = '<div class="multi-part" id="tab_' . $type . '" title="' . $title . '">' .

        '<form action="' . Html::escapeURL($url) . '" method="post" class="fieldset">' .

        '<p>' .
        form::hidden(['ip_type'], $type) .
        '<label class="classic" for="addip_' . $type . '">' . __('Add an IP address: ') . '</label> ' .
        form::field(['addip', 'addip_' . $type], 18, 255);
        if (Core::auth()->isSuperAdmin()) {
            $res .= '<label class="classic" for="globalip_' . $type . '">' . form::checkbox(['globalip', 'globalip_' . $type], 1) . ' ' .
            __('Global IP (used for all blogs)') . '</label> ';
        }

        $res .= Core::nonce()->getFormNonce() .
        '</p>' .
        '<p><input type="submit" value="' . __('Add') . '"/></p>' .
            '</form>';

        $rs = $this->getRules($type);

        if ($rs->isEmpty()) {
            $res .= '<p><strong>' . __('No IP address in list.') . '</strong></p>';
        } else {
            $res .= '<form action="' . Html::escapeURL($url) . '" method="post">' .
            '<h3>' . __('IP list') . '</h3>' .
                '<div class="antispam">';

            $res_global = '';
            $res_local  = '';
            while ($rs->fetch()) {
                $bits    = explode(':', $rs->rule_content);
                $pattern = $bits[0];

                $disabled_ip = false;
                $p_style     = '';
                if (!$rs->blog_id) {
                    $disabled_ip = !Core::auth()->isSuperAdmin();
                    $p_style .= ' global';
                }

                $item = '<p class="' . $p_style . '"><label class="classic" for="' . $type . '-ip-' . $rs->rule_id . '">' .
                form::checkbox(
                    ['delip[]', $type . '-ip-' . $rs->rule_id],
                    $rs->rule_id,
                    [
                        'disabled' => $disabled_ip,
                    ]
                ) . ' ' .
                Html::escapeHTML($pattern) .
                    '</label></p>';

                if ($rs->blog_id) {
                    // local list
                    if ($res_local == '') {
                        $res_local = '<h4>' . __('Local IPs (used only for this blog)') . '</h4>';
                    }
                    $res_local .= $item;
                } else {
                    // global list
                    if ($res_global == '') {
                        $res_global = '<h4>' . __('Global IPs (used for all blogs)') . '</h4>';
                    }
                    $res_global .= $item;
                }
            }
            $res .= $res_local . $res_global;

            $res .= '</div>' .
            '<p><input class="submit delete" type="submit" value="' . __('Delete') . '"/>' .
            Core::nonce()->getFormNonce() .
            form::hidden(['ip_type'], $type) .
                '</p>' .
                '</form>';
        }

        $res .= '</div>';

        return $res;
    }

    /**
     * Extract IP and mask from rule pattern
     *
     * @param      string     $pattern  The pattern
     * @param      mixed      $ip       The IP
     * @param      mixed      $mask     The mask
     *
     * @throws     Exception
     */
    private function ipmask(string $pattern, &$ip, &$mask)
    {
        $bits = explode('/', $pattern);

        # Set IP
        $bits[0] .= str_repeat('.0', 3 - substr_count($bits[0], '.'));

        if (!filter_var($bits[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new Exception('Invalid IPv4 address');
        }

        $ip = ip2long($bits[0]);

        if (!$ip || $ip == -1) {
            throw new Exception('Invalid IP address');
        }

        # Set mask
        if (!isset($bits[1])) {
            $mask = -1;
        } elseif (strpos($bits[1], '.')) {
            $mask = ip2long($bits[1]);
            if (!$mask) {
                $mask = -1;
            }
        } else {
            $mask = ~((1 << (32 - min((int) $bits[1], 32))) - 1);
        }
    }

    /**
     * Adds an IP rule.
     *
     * @param      string  $type     The type
     * @param      string  $pattern  The pattern
     * @param      bool    $global   The global
     */
    public function addIP(string $type, string $pattern, bool $global): void
    {
        $this->ipmask($pattern, $ip, $mask);
        $pattern = long2ip($ip) . ($mask != -1 ? '/' . long2ip($mask) : '');
        $content = $pattern . ':' . $ip . ':' . $mask;

        $old = $this->getRuleCIDR($type, $global, $ip, $mask);
        $cur = Core::con()->openCursor($this->table);

        if ($old->isEmpty()) {
            $id = (new MetaRecord(Core::con()->select('SELECT MAX(rule_id) FROM ' . $this->table)))->f(0) + 1;

            $cur->rule_id      = $id;
            $cur->rule_type    = (string) $type;
            $cur->rule_content = (string) $content;

            if ($global && Core::auth()->isSuperAdmin()) {
                $cur->blog_id = null;
            } else {
                $cur->blog_id = Core::blog()->id;
            }

            $cur->insert();
        } else {
            $cur->rule_type    = (string) $type;
            $cur->rule_content = (string) $content;
            $cur->update('WHERE rule_id = ' . (int) $old->rule_id);
        }
    }

    /**
     * Gets the rules.
     *
     * @param      string  $type   The type
     *
     * @return     MetaRecord  The rules.
     */
    private function getRules(string $type = 'all'): MetaRecord
    {
        $strReq = 'SELECT rule_id, rule_type, blog_id, rule_content ' .
        'FROM ' . $this->table . ' ' .
        "WHERE rule_type = '" . Core::con()->escape($type) . "' " .
        "AND (blog_id = '" . Core::blog()->id . "' OR blog_id IS NULL) " .
        'ORDER BY blog_id ASC, rule_content ASC ';

        return new MetaRecord(Core::con()->select($strReq));
    }

    /**
     * Gets the rule CIDR.
     *
     * @param      string  $type    The type
     * @param      bool    $global  The global
     * @param      mixed   $ip      The IP
     * @param      mixed   $mask    The mask
     *
     * @return     MetaRecord  The rules.
     */
    private function getRuleCIDR(string $type, bool $global, $ip, $mask): MetaRecord
    {
        $strReq = 'SELECT * FROM ' . $this->table . ' ' .
        "WHERE rule_type = '" . Core::con()->escape($type) . "' " .
        "AND rule_content LIKE '%:" . (int) $ip . ':' . (int) $mask . "' " .
        'AND blog_id ' . ($global ? 'IS NULL ' : "= '" . Core::blog()->id . "' ");

        return new MetaRecord(Core::con()->select($strReq));
    }

    /**
     * Check an IP
     *
     * @param      string  $cip    The IP
     * @param      string  $type   The type
     *
     * @return     bool|string
     */
    private function checkIP(string $cip, string $type)
    {
        $strReq = 'SELECT DISTINCT(rule_content) ' .
        'FROM ' . $this->table . ' ' .
        "WHERE rule_type = '" . Core::con()->escape($type) . "' " .
        "AND (blog_id = '" . Core::blog()->id . "' OR blog_id IS NULL) " .
        'ORDER BY rule_content ASC ';

        $rs = new MetaRecord(Core::con()->select($strReq));
        while ($rs->fetch()) {
            [$pattern, $ip, $mask] = explode(':', $rs->rule_content);
            if ((ip2long($cip) & (int) $mask) == ((int) $ip & (int) $mask)) {
                return $pattern;
            }
        }

        return false;
    }

    /**
     * Removes a rule.
     *
     * @param      mixed  $ids    The rules identifiers
     */
    private function removeRule($ids): void
    {
        $strReq = 'DELETE FROM ' . $this->table . ' ';

        if (is_array($ids)) {
            foreach ($ids as $i => $v) {
                $ids[$i] = (int) $v;
            }
            $strReq .= 'WHERE rule_id IN (' . implode(',', $ids) . ') ';
        } else {
            $ids = (int) $ids;
            $strReq .= 'WHERE rule_id = ' . $ids . ' ';
        }

        if (!Core::auth()->isSuperAdmin()) {
            $strReq .= "AND blog_id = '" . Core::blog()->id . "' ";
        }

        Core::con()->execute($strReq);
    }
}
