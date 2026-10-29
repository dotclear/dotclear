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
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
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
        $rs = (new SelectStatement())
            ->columns(['blog_id', 'setting_id', 'setting_value'])
            ->from(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->where("(setting_id = 'widgets_nav' OR setting_id = 'widgets_extra')")
            ->and("setting_ns = 'widgets'")
            ->select();
        if (!$rs instanceof MetaRecord) {
            return $cleanup_sessions;
        }

        while ($rs->fetch()) {
            $blog_id       = $rs->strField('bolg_id', true);
            $setting_id    = $rs->strField('setting_id');
            $setting_value = $rs->strField('setting_value');
            if ($setting_id !== '' && $setting_value !== '') {
                $widgetsettings = (string) base64_decode($setting_value, true);
                $widgetsettings = str_replace('s:11:"tplMetadata"', 's:7:"tplTags"', $widgetsettings);

                $cur = App::db()->con()->openCursor(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME);
                $cur->setStrField('setting_value', base64_encode($widgetsettings));

                $sql = new UpdateStatement();
                $sql
                    ->where('setting_id = ' . $sql->quote($setting_id))
                    ->and('setting_ns = ' . $sql->quote('widgets'));
                if ($blog_id === null) {
                    $sql->and('blog_id IS NULL');
                } else {
                    $sql->and('blog_id = ' . $sql->quote($blog_id));
                }

                $sql->update($cur);
            }
        }

        return $cleanup_sessions;
    }
}
