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
use Dotclear\Database\Statement\InsertStatement;
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_8_1_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        # switch from jQuery 1.11.1 to 1.11.2
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns('setting_value')
            ->values('1.11.3')
            ->where('setting_id = ' . $sql->quote('jquery_version'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->and('setting_value = ' . $sql->quote('1.11.1'))
            ->update();

        # Some new settings should be initialized, prepare db queries
        $sql = new InsertStatement();
        $sql
            ->into(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns(['setting_id', 'setting_ns', 'setting_value', 'setting_type', 'setting_label'])
            ->values([['no_search', 'system', '0', 'boolean', 'Disable internal search system']])
            ->insert();

        return $cleanup_sessions;
    }
}
