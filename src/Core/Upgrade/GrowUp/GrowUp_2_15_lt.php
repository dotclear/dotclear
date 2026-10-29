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
use Dotclear\Core\Upgrade\Upgrade;
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_15_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # switch from jQuery 1.11.3 to 1.12.4
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns('setting_value')
            ->values('1.12.4')
            ->where('setting_id = ' . $sql->quote('jquery_version'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->and('setting_value = ' . $sql->quote('1.11.3'))
            ->update();

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'plugins/dcLegacyEditor/tpl/index.tpl',
                'plugins/dcCKEditor/tpl/index.tpl',
            ],
        );

        return $cleanup_sessions;
    }
}
