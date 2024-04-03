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

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_30_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Set default Legacy JS loading for blog
        $strReq = 'INSERT INTO ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
            ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
        App::con()->execute(
            sprintf($strReq, 'legacy_needed', (string) true, 'boolean', 'Load Legacy JS library')
        );

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'admin/images/admin.png',
                'admin/images/attach.png',
                'admin/images/check-on.png',
                'admin/images/check-off.png',
                'admin/images/check-wrn.png',
                'admin/images/collapser-hide.png',
                'admin/images/collapser-show.png',
                'admin/images/comments.png',
                'admin/images/disabled_down.png',
                'admin/images/disabled_up.png',
                'admin/images/down.png',
                'admin/images/edit-mini.png',
                'admin/images/fav-on.png',
                'admin/images/fav-off.png',
                'admin/images/grid-on.png',
                'admin/images/grid-off.png',
                'admin/images/hidden.png',
                'admin/images/junk.png',
                'admin/images/list-on.png',
                'admin/images/list-off.png',
                'admin/images/locker.png',
                'admin/images/palette-traviata.png',
                'admin/images/plus.png',
                'admin/images/scheduled.png',
                'admin/images/selected.png',
                'admin/images/superadmin.png',
                'admin/images/trackbacks.png',
                'admin/images/up.png',
                'admin/js/easter.js',
                'admin/style/cancel.png',
                'admin/style/drag.png',
                'admin/style/search.png',
                'admin/style/settings.png',
                'admin/style/trash.png',
                'admin/style/user.png',
                'admin/style/dc_logos/b-dotclear120.png',
                'admin/style/dc_logos/w-dotclear240.png',
                'plugins/antispam/feed.png',
                'plugins/dcCKEditor/js/ckeditor-plugins/dclink/icons/dclink.png',
                'plugins/dcCKEditor/js/ckeditor-plugins/entrylink/icons/entrylink.png',
                'plugins/dcCKEditor/js/ckeditor-plugins/img/icons/img.png',
                'plugins/dcCKEditor/js/ckeditor-plugins/media/icons/media.png',
                'plugins/tags/js/tag.png',
            ],
            // Folders
            [
                'admin/style/install',
            ]
        );

        return $cleanup_sessions;
    }
}
