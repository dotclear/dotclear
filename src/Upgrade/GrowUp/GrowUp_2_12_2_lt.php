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

namespace Dotclear\Upgrade\GrowUp;

use dcCore;
use dcNamespace;

class GrowUp_2_12_2_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
        // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
        $csp_prefix = dcCore::app()->con->driver() == 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver

        # Update CSP img-src default directive
        $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
            " SET setting_value = '" . $csp_prefix . "''self'' data: http://media.dotaddict.org blob:' " .
            " WHERE setting_id = 'csp_admin_img' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = '" . $csp_prefix . "''self'' data: media.dotaddict.org blob:' ";
        dcCore::app()->con->execute($strReq);

        return $cleanup_sessions;
    }
}
