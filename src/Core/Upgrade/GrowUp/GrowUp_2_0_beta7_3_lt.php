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
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_0_beta7_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Blowup becomes default theme
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns('setting_value')
            ->values('blueSilence')
            ->where('setting_id = ' . $sql->quote('theme'))
            ->and('setting_value = ' . $sql->quote('default'))
            ->and('blog_id IS NOT NULL')
            ->update();

        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns('setting_value')
            ->values('default')
            ->where('setting_id = ' . $sql->quote('theme'))
            ->and('setting_value = ' . $sql->quote('blowup'))
            ->and('blog_id IS NOT NULL')
            ->update();

        return $cleanup_sessions;
    }
}
