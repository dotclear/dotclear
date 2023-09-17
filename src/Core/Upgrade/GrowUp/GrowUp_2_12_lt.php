<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_12_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # switch from jQuery 2.2.0 to 2.2.4
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = '2.2.4' " .
            " WHERE setting_id = 'jquery_version' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = '2.2.0' ";
        App::con()->execute($strReq);

        return $cleanup_sessions;
    }
}
