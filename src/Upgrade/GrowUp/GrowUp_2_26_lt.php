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
use Dotclear\Upgrade\Upgrade;
use Dotclear\Database\Statement\UpdateStatement;

class GrowUp_2_26_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Update file exclusion upload regex
        $sql = new UpdateStatement();
        $sql
            ->ref(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
            ->column('setting_value')
            ->value('/\.(phps?|pht(ml)?|phl|phar|.?html?|xml|js|htaccess)[0-9]*$/i')
            ->where('setting_id = ' . $sql->quote('media_exclusion'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->and('setting_value = ' . $sql->quote('/\.(phps?|pht(ml)?|phl|.?html?|xml|js|htaccess)[0-9]*$/i'))
        ;
        $sql->update();

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // Core
                'inc/core/class.dc.sql.statement.php',
                'inc/core/class.dc.record.php',
            ],
            // Folders
            [
                // DC html.form moved to src
                'inc/admin/html.form',
                // CB moved to src
                'inc/helper',
            ]
        );

        return $cleanup_sessions;
    }
}
