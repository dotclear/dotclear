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

use Dotclear\Upgrade\Upgrade;

class GrowUp_2_21_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // The old js datepicker has gone
                'admin/js/date-picker.js',
                'admin/style/date-picker.css',
                'admin/images/date-picker.png',
                // Some PNG icon have been converted to SVG
                'admin/images/expand.png',
                'admin/images/hide.png',
                'admin/images/logout.png',
                'admin/images/menu_off.png',
                'admin/images/menu_on.png',
                'admin/images/minus-theme.png',
                'admin/images/outgoing-blue.png',
                'admin/images/outgoing.png',
                'admin/images/page_help.png',
                'admin/images/plus-theme.png',
                'admin/images/picker.png',
                'admin/images/menu/blog-pref.png',
                'admin/images/menu/blog-pref-b.png',
                'admin/images/menu/blog-theme-b.png',
                'admin/images/menu/blog-theme-b-update.png',
                'admin/images/menu/blogs.png',
                'admin/images/menu/blogs-b.png',
                'admin/images/menu/categories.png',
                'admin/images/menu/categories-b.png',
                'admin/images/menu/comments.png',
                'admin/images/menu/comments-b.png',
                'admin/images/menu/edit.png',
                'admin/images/menu/edit-b.png',
                'admin/images/menu/entries.png',
                'admin/images/menu/entries-b.png',
                'admin/images/menu/help.png',
                'admin/images/menu/help-b.png',
                'admin/images/menu/langs.png',
                'admin/images/menu/langs-b.png',
                'admin/images/menu/media.png',
                'admin/images/menu/media-b.png',
                'admin/images/menu/plugins.png',
                'admin/images/menu/plugins-b.png',
                'admin/images/menu/plugins-b-update.png',
                'admin/images/menu/search.png',
                'admin/images/menu/search-b.png',
                'admin/images/menu/themes.png',
                'admin/images/menu/update.png',
                'admin/images/menu/user-pref.png',
                'admin/images/menu/user-pref-b.png',
                'admin/images/menu/users.png',
                'admin/images/menu/users-b.png',
                'admin/pagination/first.png',
                'admin/pagination/last.png',
                'admin/pagination/next.png',
                'admin/pagination/no-first.png',
                'admin/pagination/no-last.png',
                'admin/pagination/no-next.png',
                'admin/pagination/no-previous.png',
                'admin/pagination/previous.png',
                'admin/style/dashboard.png',
                'admin/style/dashboard-alt.png',
                'admin/style/help-mini.png',
                'admin/style/help12.png',
                'plugins/aboutConfig/icon-big.png',
                'plugins/aboutConfig/icon.png',
                'plugins/antispam/icon-big.png',
                'plugins/antispam/icon.png',
                'plugins/blogroll/icon-small.png',
                'plugins/blogroll/icon.png',
                'plugins/dcCKEditor/imgs/icon.png',
                'plugins/dcLegacyEditor/icon.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_bquote.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_br.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_clean.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_code.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_del.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_em.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_img.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_img_select.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_ins.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_link.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_mark.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_ol.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_paragraph.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_post.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_pre.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_quote.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_strong.png',
                'plugins/dcLegacyEditor/css/jsToolBar/bt_ul.png',
                'plugins/importExport/icon-big.png',
                'plugins/importExport/icon.png',
                'plugins/maintenance/icon-big-update.png',
                'plugins/maintenance/icon-big.png',
                'plugins/maintenance/icon-small.png',
                'plugins/maintenance/icon.png',
                'plugins/pages/icon-big.png',
                'plugins/pages/icon-np-big.png',
                'plugins/pages/icon-np.png',
                'plugins/pages/icon.png',
                'plugins/pings/icon-big.png',
                'plugins/pings/icon.png',
                'plugins/simpleMenu/icon-small.png',
                'plugins/simpleMenu/icon.png',
                'plugins/tags/icon-big.png',
                'plugins/tags/icon.png',
                'plugins/tags/img/tag-add.png',
                'plugins/tags/img/loader.gif',
                'plugins/userPref/icon-big.png',
                'plugins/userPref/icon.png',
                'plugins/widgets/icon-big.png',
                'plugins/widgets/icon.png',
            ],
            // Folders
            [
                'plugins/dcCKEditor/imgs',
            ]
        );

        return $cleanup_sessions;
    }
}
