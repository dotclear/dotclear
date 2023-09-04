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

use Dotclear\Core\Upgrade\Upgrade;
use Exception;

class GrowUp_2_28_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'src/Upgrade/dummy.txt',
                'inc/core/class.dc.auth.php',
                'inc/core/class.dc.blog.php',
                'inc/core/class.dc.deprecated.php',
                'inc/core/class.dc.error.php',
                'inc/core/class.dc.log.php',
                'inc/core/class.dc.media.php',
                'inc/core/class.dc.meta.php',
                'inc/core/class.dc.module.define.php',
                'inc/core/class.dc.modules.php',
                'inc/core/class.dc.notices.php',
                'inc/core/class.dc.plugins.php',
                'inc/core/class.dc.postmedia.php',
                'inc/core/class.dc.rest.php',
                'inc/core/class.dc.store.php',
                'inc/core/class.dc.store.parser.php',
                'inc/core/class.dc.store.reader.php',
                'inc/core/class.dc.themes.php',
                'inc/core/class.dc.utils.php',
                'inc/public/lib.urlhandlers.php',
                'inc/public/class.dc.template.php',
                'inc/public/lib.tpl.context.php',
            ],
            // Folders
            [
            ]
        );

        return $cleanup_sessions;
    }
}
