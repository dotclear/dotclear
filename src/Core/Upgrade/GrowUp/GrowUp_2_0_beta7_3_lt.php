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

use dcNamespace;
use Dotclear\Core\Core;

class GrowUp_2_0_beta7_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Blowup becomes default theme
        $strReq = 'UPDATE ' . Core::con()->prefix() . dcNamespace::NS_TABLE_NAME . ' ' .
            "SET setting_value = '%s' " .
            "WHERE setting_id = 'theme' " .
            "AND setting_value = '%s' " .
            'AND blog_id IS NOT NULL ';
        Core::con()->execute(sprintf($strReq, 'blueSilence', 'default'));
        Core::con()->execute(sprintf($strReq, 'default', 'blowup'));

        return $cleanup_sessions;
    }
}
