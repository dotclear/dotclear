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

class GrowUp_2_0_beta7_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Blowup becomes default theme
        $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "SET setting_value = '%s' " .
            "WHERE setting_id = 'theme' " .
            "AND setting_value = '%s' " .
            'AND blog_id IS NOT NULL ';
        dcCore::app()->con->execute(sprintf($strReq, 'blueSilence', 'default'));
        dcCore::app()->con->execute(sprintf($strReq, 'default', 'blowup'));

        return $cleanup_sessions;
    }
}
