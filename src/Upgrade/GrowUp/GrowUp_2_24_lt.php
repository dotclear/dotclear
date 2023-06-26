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

class GrowUp_2_24_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'admin/images/close.png',
                'admin/images/dotclear_pw.png',

                'admin/images/media/audio.png',
                'admin/images/media/blank.png',
                'admin/images/media/document.png',
                'admin/images/media/executable.png',
                'admin/images/media/folder-up.png',
                'admin/images/media/folder.png',
                'admin/images/media/html.png',
                'admin/images/media/image.png',
                'admin/images/media/package.png',
                'admin/images/media/presentation.png',
                'admin/images/media/spreadsheet.png',
                'admin/images/media/text.png',
                'admin/images/media/video.png',

                'admin/style/msg-error.png',
                'admin/style/msg-info.png',
                'admin/style/msg-std.png',
                'admin/style/msg-success.png',
                'admin/style/msg-warning.png',

                'admin/style/dc_logos/dc_logo_footer.png',
                'admin/style/dc_logos/sq-logo-32.png',
                'admin/style/dc_logos/w-dotclear180.png',
                'admin/style/dc_logos/w-dotclear90.png',

                'admin/style/scss/init/_mixins-functions.scss',
                'admin/style/config.rb',

                'inc/clearbricks/common/_main.php',
                'inc/clearbricks/common/lib.forms.php',

                'plugins/akismet/class.dc.filter.akismet.php',                  // Moved to plugins/akismet/filters

                'plugins/antispam/filters/class.dc.filter.ip.php',              // Renamed
                'plugins/antispam/filters/class.dc.filter.iplookup.php',        // Renamed
                'plugins/antispam/filters/class.dc.filter.ipv6.php',            // Renamed
                'plugins/antispam/filters/class.dc.filter.linkslookup.php',     // Renamed
                'plugins/antispam/filters/class.dc.filter.words.php',           // Renamed
                'plugins/antispam/inc/class.dc.spamfilter.php',                 // Renamed
                'plugins/antispam/inc/class.dc.spamfilters.php',                // Renamed
                'plugins/antispam/inc/lib.dc.antispam.php',                     // Renamed
                'plugins/antispam/inc/lib.dc.antispam.url.php',                 // Renamed

                'plugins/blogroll/class.dc.blogroll.php',                       // Moved to plugins/blogroll/inc
                'plugins/blogroll/class.dc.importblogroll.php',                 // Moved to plugins/blogroll/inc

                'plugins/blowupConfig/lib/class.blowup.config.php',             // Moved to plugins/blowupConfig/inc

                'plugins/dclegacy/_admin.php',
                'plugins/dclegacy/_define.php',

                'plugins/dcCKEditor/inc/_config.php',
                'plugins/dcCKEditor/inc/dc.ckeditor.behaviors.php',             // Renamed

                'plugins/dcLegacyEditor/inc/dc.legacy.editor.behaviors.php',    // Renamed

                'plugins/fairTrackbacks/class.dc.filter.fairtrackbacks.php',    // Moved to plugins/fairTrackbacks/filters

                'plugins/importExport/style.css',                               // Moved to plugins/importExport/css
                'plugins/importExport/img/progress.png',

                'plugins/maintenance/inc/class.dc.maintenance.php',                         // Renamed
                'plugins/maintenance/inc/class.dc.maintenance.descriptor.php',              // Renamed
                'plugins/maintenance/inc/class.dc.maintenance.task.php',                    // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.cache.php',             // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.csp.php',               // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.countcomments.php',     // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.indexcomments.php',     // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.indexposts.php',        // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.logs.php',              // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.synchpostsmeta.php',    // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.vacuum.php',            // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.zipmedia.php',          // Renamed
                'plugins/maintenance/inc/tasks/class.dc.maintenance.ziptheme.php',          // Renamed

                'plugins/pages/class.actionpage.php',                           // Moved to plugins/pages/inc
                'plugins/pages/class.listpage.php',                             // Moved to plugins/pages/inc

                'plugins/pings/lib.pings.php',                                  // Moved to plugins/pings/inc

                'plugins/tags/_xmlrpc.php',
                'plugins/tags/inc/tags.behaviors.php',                          // Renamed

                'plugins/themeEditor/class.themeEditor.php',                    // Moved to plugins/themeEditor/inc

                'plugins/widgets/_default_widgets.php',
                'plugins/widgets/_widgets_functions.php',                       // Moved to plugins/widgets/inc
                'plugins/widgets/class.widgets.php',                            // Moved to plugins/widgets/inc
            ],
            // Folders
            [
                'inc/libs/clearbricks/debian',
                'inc/libs/clearbricks/ext',
                'inc/libs/clearbricks/mail.convert',
                'inc/libs/clearbricks/mail.mime',
                'inc/libs/clearbricks/net.nntp',
                'inc/libs/clearbricks/xmlsql',
                'plugins/blowupConfig/lib',
                'plugins/dclegacy',
                'plugins/importExport/img',
            ]
        );

        // Global settings
        $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
            ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
            ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
        dcCore::app()->con->execute(
            sprintf($strReq, 'sleepmode_timeout', 31_536_000, 'integer', 'Sleep mode timeout')
        );

        return $cleanup_sessions;
    }
}
