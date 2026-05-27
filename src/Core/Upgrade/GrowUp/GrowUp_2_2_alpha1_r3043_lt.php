<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

/**
 * @brief   Upgrade step.
 *
 * @todo switch to SqlStatement
 */
class GrowUp_2_2_alpha1_r3043_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # metadata has been integrated to the core.
        App::plugins()->loadModules(App::config()->pluginsRoot());
        if (App::plugins()->moduleExists('metadata')) {
            App::plugins()->deleteModule('metadata');
        }

        # Tags template class has been renamed
        $sqlstr = 'SELECT blog_id, setting_id, setting_value ' .
        'FROM ' . App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME . ' ' .
            'WHERE (setting_id = \'widgets_nav\' OR setting_id = \'widgets_extra\') ' .
            'AND setting_ns = \'widgets\';';
        $rs = new MetaRecord(App::db()->con()->select($sqlstr));
        while ($rs->fetch()) {
            $blog_id       = $rs->strField('bolg_id', true);
            $setting_id    = $rs->strField('setting_id');
            $setting_value = $rs->strField('setting_value');
            if ($setting_id !== '' && $setting_value !== '') {
                $widgetsettings     = base64_decode($setting_value);
                $widgetsettings     = str_replace('s:11:"tplMetadata"', 's:7:"tplTags"', $widgetsettings);
                $cur                = App::db()->con()->openCursor(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME);
                $cur->setting_value = base64_encode($widgetsettings);
                $sqlstr             = 'WHERE setting_id = \'' . $setting_id . '\' AND setting_ns = \'widgets\' ' .
                    'AND blog_id ' . ($blog_id === null ? 'is NULL' : '= \'' . App::db()->con()->escapeStr($blog_id) . '\'');
                $cur->update($sqlstr);
            }
        }

        return $cleanup_sessions;
    }
}
