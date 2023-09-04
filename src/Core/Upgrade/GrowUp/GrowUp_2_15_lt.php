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
use Dotclear\Core\Upgrade\Upgrade;

class GrowUp_2_15_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # switch from jQuery 1.11.3 to 1.12.4
        $strReq = 'UPDATE ' . App::con()->prefix() . BlogWorkspace::NS_TABLE_NAME .
            " SET setting_value = '1.12.4' " .
            " WHERE setting_id = 'jquery_version' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = '1.11.3' ";
        App::con()->execute($strReq);

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
