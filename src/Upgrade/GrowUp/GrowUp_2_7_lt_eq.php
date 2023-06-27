<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * Dotclear upgrade procedure.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Upgrade\GrowUp;

use dcCore;
use dcNamespace;

class GrowUp_2_7_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Some new settings should be initialized, prepare db queries
        $strReqFormat = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
        $strReqFormat .= ' (setting_id,setting_ns,setting_value,setting_type,setting_label)';
        $strReqFormat .= ' VALUES(\'%s\',\'system\',\'%s\',\'string\',\'%s\')';

        $strReqCount = 'SELECT count(1) FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
        $strReqCount .= ' WHERE setting_id = \'%s\'';
        $strReqCount .= ' AND setting_ns = \'system\'';
        $strReqCount .= ' AND blog_id IS NULL';

        $strReqSelect = 'SELECT setting_value FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
        $strReqSelect .= ' WHERE setting_id = \'%s\'';
        $strReqSelect .= ' AND setting_ns = \'system\'';
        $strReqSelect .= ' AND blog_id IS NULL';

        # Add nb of posts for home (first page), copying nb of posts on every page
        $rs = dcCore::app()->con->select(sprintf($strReqCount, 'nb_post_for_home'));
        if ($rs->f(0) == 0) {
            $rs     = dcCore::app()->con->select(sprintf($strReqSelect, 'nb_post_per_page'));
            $strReq = sprintf($strReqFormat, 'nb_post_for_home', $rs->f(0), 'Nb of posts on home (first page only)');
            dcCore::app()->con->execute($strReq);
        }

        return $cleanup_sessions;
    }
}
