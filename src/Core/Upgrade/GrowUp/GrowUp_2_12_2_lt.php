<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_12_2_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
        // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
        $csp_prefix = App::con()->driver() === 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver

        # Update CSP img-src default directive
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = '" . $csp_prefix . "''self'' data: http://media.dotaddict.org blob:' " .
            " WHERE setting_id = 'csp_admin_img' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = '" . $csp_prefix . "''self'' data: media.dotaddict.org blob:' ";
        App::con()->execute($strReq);

        return $cleanup_sessions;
    }
}
