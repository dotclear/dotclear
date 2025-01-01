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
use Dotclear\Core\Upgrade\Upgrade;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_25_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Remove removed blogs from users default blog
        $ids = [];
        $rs  = (new SelectStatement())
            ->from(App::con()->prefix() . App::blog()::BLOG_TABLE_NAME)
            ->where('blog_status = ' . App::blog()::BLOG_REMOVED)
            ->select();
        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $ids[] = $rs->blog_id;
            }
        }
        if ($ids !== []) {
            App::users()->removeUsersDefaultBlogs($ids);
        }

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'plugins/akismet/_prepend.php',
                'plugins/antispam/_admin.php',
                'plugins/antispam/_install.php',
                'plugins/antispam/_prepend.php',
                'plugins/antispam/_public.php',
                'plugins/antispam/_services.php',
                'plugins/antispam/index.php',
                'plugins/antispam/style.css',
                'plugins/aboutConfig/_admin.php',
                'plugins/aboutConfig/index.php',
                'plugins/attachments/_admin.php',
                'plugins/attachments/_prepend.php',
                'plugins/attachments/_public.php',
                'plugins/blogroll/_admin.php',
                'plugins/blogroll/_install.php',
                'plugins/blogroll/_prepend.php',
                'plugins/blogroll/_public.php',
                'plugins/blogroll/_widgets.php',
                'plugins/blogroll/edit.php',
                'plugins/blogroll/index;php',
                'plugins/breadcrumb/_admin.php',
                'plugins/breadcrumb/_prepend.php',
                'plugins/breadcrumb/_public.php',
                'plugins/buildtools/_admin.php',
                'plugins/buildtools/_prepend.php',
                'plugins/dcCKEditor/_admin.php',
                'plugins/dcCKEditor/_install.php',
                'plugins/dcCKEditor/_post_config.php',
                'plugins/dcCKEditor/_prepend.php',
                'plugins/dcCKEditor/index.php',
                'plugins/dcLegacyEditor/_admin.php',
                'plugins/dcLegacyEditor/_install.php',
                'plugins/dcLegacyEditor/_prepend.php',
                'plugins/dcLegacyEditor/_services.php',
                'plugins/dcLegacyEditor/index.php',
                'plugins/fairTrackbacks/_prepend.php',
                'plugins/fairTrackbacks/_public.php',
                'plugins/importExport/_admin.php',
                'plugins/importExport/_prepend.php',
                'plugins/importExport/index.php',
                'plugins/importExport/style.css',
                'plugins/maintenance/_admin.php',
                'plugins/maintenance/_prepend.php',
                'plugins/maintenance/index.php',
                'plugins/pages/_admin.php',
                'plugins/pages/_install.php',
                'plugins/pages/_prepend.php',
                'plugins/pages/_public.php',
                'plugins/pages/_widgets.php',
                'plugins/pages/index.php',
                'plugins/pages/list.php',
                'plugins/pages/page.php',
                'plugins/pings/_admin.php',
                'plugins/pings/_install.php',
                'plugins/pings/_prepend.php',
                'plugins/pings/index.php',
                'plugins/simpleMenu/_admin.php',
                'plugins/simpleMenu/_install.php',
                'plugins/simpleMenu/_prepend.php',
                'plugins/simpleMenu/_public.php',
                'plugins/simpleMenu/_widgets.php',
                'plugins/simpleMenu/index.php',
                'plugins/tags/_admin.php',
                'plugins/tags/_prepend.php',
                'plugins/tags/_public.php',
                'plugins/tags/_widgets.php',
                'plugins/tags/index.php',
                'plugins/tags/style.php',
                'plugins/tags/tags_post.php',
                'plugins/tags/tags.php',
                'plugins/themeEditor/_admin.php',
                'plugins/themeEditor/_prepend.php',
                'plugins/themeEditor/index.php',
                'plugins/themeEditor/style.css',
                'plugins/userPref/_admin.php',
                'plugins/userPref/index.php',
                'plugins/widget/_admin.php',
                'plugins/widget/_init.php',
                'plugins/widget/_install.php',
                'plugins/widget/_public.php',
                'plugins/widget/_prepend.php',
                'plugins/widget/index.php',
                'plugins/widget/style.css',
                'themes/berlin/_public.php',
                'themes/customCSS/_config.php',
                'themes/customCSS/_public.php',
                'themes/ductile/_config.php',
                'themes/ductile/_public.php',
                'themes/ductile/_prepend.php',
            ],
            // Folders
            [
                'inc/libs',
                'plugins/akismet/filters',          // Replaced by src
                'plugins/antispam/filters',         // Replaced by src/Filters
                'plugins/antispam/inc',             // Replaced by src
                'plugins/aboutConfig/inc',          // Replaced by src
                'plugins/attachments/inc',          // Replaced by src
                'plugins/blogroll/inc',             // Replaced by src
                'plugins/blowupConfig',
                'plugins/breadcrumb/inc',           // Replaced by src
                'plugins/buildtools/inc',           // Replaced by src
                'plugins/dcCKEditor/inc',           // Replaced by src
                'plugins/dcLegacyEditor/inc',       // Replaced by src
                'plugins/fairTrackbacks/filters',   // Replaced by src
                'plugins/importExport/inc',         // Replaced by src
                'plugins/maintenance/inc',          // Replaced by src
                'plugins/pages/inc',                // Replaced by src
                'plugins/pings/inc',                // Replaced by src
                'plugins/simpleMenu/inc',           // Replaced by src
                'plugins/tags/inc',                 // Replaced by src
                'plugins/themeEditor/inc',          // Replaced by src
                'plugins/userPref/inc',             // Replaced by src
                'plugins/widgets/inc',              // Replaced by src
            ]
        );

        return $cleanup_sessions;
    }
}
