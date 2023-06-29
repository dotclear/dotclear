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

namespace Dotclear\Core\Upgrade\GrowUp;

use dcCore;
use dcNamespace;

class GrowUp_2_2_alpha1_r3043_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # metadata has been integrated to the core.
        dcCore::app()->plugins->loadModules(DC_PLUGINS_ROOT);
        if (dcCore::app()->plugins->moduleExists('metadata')) {
            dcCore::app()->plugins->deleteModule('metadata');
        }

        # Tags template class has been renamed
        $sqlstr = 'SELECT blog_id, setting_id, setting_value ' .
        'FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            'WHERE (setting_id = \'widgets_nav\' OR setting_id = \'widgets_extra\') ' .
            'AND setting_ns = \'widgets\';';
        $rs = dcCore::app()->con->select($sqlstr);
        while ($rs->fetch()) {
            $widgetsettings     = base64_decode($rs->setting_value);
            $widgetsettings     = str_replace('s:11:"tplMetadata"', 's:7:"tplTags"', $widgetsettings);
            $cur                = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
            $cur->setting_value = base64_encode($widgetsettings);
            $sqlstr             = 'WHERE setting_id = \'' . $rs->setting_id . '\' AND setting_ns = \'widgets\' ' .
                'AND blog_id ' .
                ($rs->blog_id == null ? 'is NULL' : '= \'' . dcCore::app()->con->escape($rs->blog_id) . '\'');
            $cur->update($sqlstr);
        }

        return $cleanup_sessions;
    }
}
