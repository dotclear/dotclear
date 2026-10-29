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
class GrowUp_2_12_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # switch from jQuery 2.2.0 to 2.2.4
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns('setting_value')
            ->values('2.2.4')
            ->where('setting_id = ' . $sql->quote('jquery_version'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->and('setting_value = ' . $sql->quote('2.2.0'))
            ->update();

        return $cleanup_sessions;
    }
}
