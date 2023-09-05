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

use Dotclear\App;
use Dotclear\Core\BlogWorkspace;

class GrowUp_2_15_1_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Remove unsafe-inline from CSP script directives
        $strReq = 'UPDATE ' . App::con()->prefix() . BlogWorkspace::NS_TABLE_NAME .
            " SET setting_value = REPLACE(setting_value, '''unsafe-inline''', '') " .
            " WHERE setting_id = 'csp_admin_script' " .
            " AND setting_ns = 'system' ";
        App::con()->execute($strReq);

        return $cleanup_sessions;
    }
}
