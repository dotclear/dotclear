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
class GrowUp_2_15_1_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Remove unsafe-inline from CSP script directives
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->set('setting_value = REPLACE(setting_value, ' . $sql->quote("'unsafe-inline'") . ", '')")
            ->where('setting_id = ' . $sql->quote('csp_admin_script'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->update();

        return $cleanup_sessions;
    }
}
