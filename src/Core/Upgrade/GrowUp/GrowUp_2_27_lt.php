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

use Dotclear\Core\Upgrade\Upgrade;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_27_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'plugins/pages/icon-np-dark.svg',
                'admin/tpl/_charte.php',
                'admin/comments_actions.php',
                'admin/posts_actions.php',
                'admin/xmlrpc.php',
                'admin/install/wizard.php',
                'admin/install/check.php',
                'inc/public/class.dc.public.php',
                'inc/prepend.php',
                'themes/ductile/src/Prepend.php',
                'inc/dbschema/upgrade.php',
            ],
            // Folders
            [
                'admin/tpl',
                'inc/admin',
                'inc/dbschema',
                'inc/js/jquery/3.6.0',
            ]
        );

        return $cleanup_sessions;
    }
}
