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
                'inc/core/class.dc.categories.php',
                'inc/core/class.dc.deprecated.php',
                'inc/core/class.dc.error.php',
                'inc/core/class.dc.log.php',
                'inc/core/class.dc.media.php',
                'inc/core/class.dc.meta.php',
                'inc/core/class.dc.module.define.php',
                'inc/core/class.dc.modules.php',
                'inc/core/class.dc.namespace.php',
                'inc/core/class.dc.notices.php',
                'inc/core/class.dc.plugins.php',
                'inc/core/class.dc.postmedia.php',
                'inc/core/class.dc.prefs.php',
                'inc/core/class.dc.rest.php',
                'inc/core/class.dc.settings.php',
                'inc/core/class.dc.store.php',
                'inc/core/class.dc.store.parser.php',
                'inc/core/class.dc.store.reader.php',
                'inc/core/class.dc.themes.php',
                'inc/core/class.dc.trackback.php',
                'inc/core/class.dc.update.php',
                'inc/core/class.dc.workspace.php',
                'inc/core/class.dc.xmlrpc.php',
                'inc/core/class.dc.rs.extensions.php',
                'inc/core/lib.rs.ext.log.php',
                'inc/core/trait.dc.dynprop.php',
                'inc/public/lib.urlhandlers.php',
                'inc/public/class.dc.template.php',
                'inc/public/rs.extension.php',
                'inc/public/lib.tpl.context.php',
                'plugins/antispam/_init.php',
                'plugins/blogroll/_init.php',
                'plugins/pages/_init.php',
                // typo or missing in some previous housecleanings
                'inc/core_error.php',
                'inc/load_plugin_file.php',
                'inc/load_var_file.php',
                'inc/core/class.dc.ns.process.php',
                'plugins/maintenance/_services.php',
                'plugins/tags/tag_posts.php',
                'plugins/blogroll/index.php',
            ],
            // Folders
            [
            ]
        );

        return $cleanup_sessions;
    }
}
