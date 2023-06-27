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

class GrowUp_2_14_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Update flie exclusion upload regex
        $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
            " SET setting_value = '/\\.(phps?|pht(ml)?|phl|.?html?|xml|js|htaccess)[0-9]*$/i' " .
            " WHERE setting_id = 'media_exclusion' " .
            " AND setting_ns = 'system' " .
            " AND (setting_value = '/\\.php[0-9]*$/i' " .
            "   OR setting_value = '/\\.php$/i') " .
            "   OR setting_value = '/\\.(phps?|pht(ml)?|phl)[0-9]*$/i' " .
            "   OR setting_value = '/\\.(phps?|pht(ml)?|phl|s?html?|js)[0-9]*$/i'" .
            "   OR setting_value = '/\\.(phps?|pht(ml)?|phl|s?html?|js|htaccess)[0-9]*$/i'";
        dcCore::app()->con->execute($strReq);

        return $cleanup_sessions;
    }
}
