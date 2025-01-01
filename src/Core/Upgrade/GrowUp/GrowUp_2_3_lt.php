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
class GrowUp_2_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Add global favorites
        $init_fav = [];

        $init_fav['new_post'] = ['new_post', 'New entry', 'post.php',
            'images/menu/edit.png', 'images/menu/edit-b.png',
            'usage,contentadmin', null, null, ];
        $init_fav['newpage'] = ['newpage', 'New page', 'plugin.php?p=pages&amp;act=page',
            'index.php?pf=pages/icon-np.png', 'index.php?pf=pages/icon-np-big.png',
            'contentadmin,pages', null, null, ];
        $init_fav['media'] = ['media', 'Media manager', 'media.php',
            'images/menu/media.png', 'images/menu/media-b.png',
            'media,media_admin', null, null, ];
        $init_fav['widgets'] = ['widgets', 'Presentation widgets', 'plugin.php?p=widgets',
            'index.php?pf=widgets/icon.png', 'index.php?pf=widgets/icon-big.png',
            'admin', null, null, ];
        $init_fav['blog_theme'] = ['blog_theme', 'Blog appearance', 'blog_theme.php',
            'images/menu/themes.png', 'images/menu/blog-theme-b.png',
            'admin', null, null, ];

        $count = 0;
        foreach ($init_fav as $f) {
            $t = ['name'     => $f[0], 'title' => $f[1], 'url' => $f[2], 'small-icon' => $f[3],
                'large-icon' => $f[4], 'permissions' => $f[5], 'id' => $f[6], 'class' => $f[7], ];
            $sqlstr = 'INSERT INTO ' . App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME . ' (pref_id, user_id, pref_ws, pref_value, pref_type, pref_label) VALUES (' .
            '\'' . sprintf('g%03s', $count) . '\',NULL,\'favorites\',\'' . serialize($t) . '\',\'string\',NULL);';
            App::con()->execute($sqlstr);
            $count++;
        }

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'admin/style/cat-bg.png',
                'admin/style/footer-bg.png',
                'admin/style/head-logo.png',
                'admin/style/tab-bg.png',
                'admin/style/tab-c-l.png',
                'admin/style/tab-c-r.png',
                'admin/style/tab-l-l.png',
                'admin/style/tab-l-r.png',
                'admin/style/tab-n-l.png',
                'admin/style/tab-n-r.png',
                'inc/clearbricks/_common.php',
                'inc/clearbricks/common/lib.crypt.php',
                'inc/clearbricks/common/lib.date.php',
                'inc/clearbricks/common/lib.files.php',
                'inc/clearbricks/common/lib.form.php',
                'inc/clearbricks/common/lib.html.php',
                'inc/clearbricks/common/lib.http.php',
                'inc/clearbricks/common/lib.l10n.php',
                'inc/clearbricks/common/lib.text.php',
                'inc/clearbricks/common/tz.dat',
                'inc/clearbricks/common/_main.php',
                'inc/clearbricks/dblayer/class.cursor.php',
                'inc/clearbricks/dblayer/class.mysql.php',
                'inc/clearbricks/dblayer/class.pgsql.php',
                'inc/clearbricks/dblayer/class.sqlite.php',
                'inc/clearbricks/dblayer/dblayer.php',
                'inc/clearbricks/dbschema/class.dbschema.php',
                'inc/clearbricks/dbschema/class.dbstruct.php',
                'inc/clearbricks/dbschema/class.mysql.dbschema.php',
                'inc/clearbricks/dbschema/class.pgsql.dbschema.php',
                'inc/clearbricks/dbschema/class.sqlite.dbschema.php',
                'inc/clearbricks/diff/lib.diff.php',
                'inc/clearbricks/diff/lib.unified.diff.php',
                'inc/clearbricks/filemanager/class.filemanager.php',
                'inc/clearbricks/html.filter/class.html.filter.php',
                'inc/clearbricks/html.validator/class.html.validator.php',
                'inc/clearbricks/image/class.image.meta.php',
                'inc/clearbricks/image/class.image.tools.php',
                'inc/clearbricks/mail/class.mail.php',
                'inc/clearbricks/mail/class.socket.mail.php',
                'inc/clearbricks/net/class.net.socket.php',
                'inc/clearbricks/net.http/class.net.http.php',
                'inc/clearbricks/net.http.feed/class.feed.parser.php',
                'inc/clearbricks/net.http.feed/class.feed.reader.php',
                'inc/clearbricks/net.xmlrpc/class.net.xmlrpc.php',
                'inc/clearbricks/pager/class.pager.php',
                'inc/clearbricks/rest/class.rest.php',
                'inc/clearbricks/session.db/class.session.db.php',
                'inc/clearbricks/template/class.template.php',
                'inc/clearbricks/text.wiki2xhtml/class.wiki2xhtml.php',
                'inc/clearbricks/url.handler/class.url.handler.php',
                'inc/clearbricks/zip/class.unzip.php',
                'inc/clearbricks/zip/class.zip.php',
                'themes/default/tpl/.htaccess',
                'themes/default/tpl/404.html',
                'themes/default/tpl/archive.html',
                'themes/default/tpl/archive_month.html',
                'themes/default/tpl/category.html',
                'themes/default/tpl/home.html',
                'themes/default/tpl/post.html',
                'themes/default/tpl/search.html',
                'themes/default/tpl/tag.html',
                'themes/default/tpl/tags.html',
                'themes/default/tpl/user_head.html',
                'themes/default/tpl/_flv_player.html',
                'themes/default/tpl/_footer.html',
                'themes/default/tpl/_head.html',
                'themes/default/tpl/_mp3_player.html',
                'themes/default/tpl/_top.html',
            ],
            // Folders
            [
                'inc/clearbricks/common',
                'inc/clearbricks/dblayer',
                'inc/clearbricks/dbschema',
                'inc/clearbricks/diff',
                'inc/clearbricks/filemanager',
                'inc/clearbricks/html.filter',
                'inc/clearbricks/html.validator',
                'inc/clearbricks/image',
                'inc/clearbricks/mail',
                'inc/clearbricks/net',
                'inc/clearbricks/net.http',
                'inc/clearbricks/net.http.feed',
                'inc/clearbricks/net.xmlrpc',
                'inc/clearbricks/pager',
                'inc/clearbricks/rest',
                'inc/clearbricks/session.db',
                'inc/clearbricks/template',
                'inc/clearbricks/text.wiki2xhtml',
                'inc/clearbricks/url.handler',
                'inc/clearbricks/zip',
                'inc/clearbricks',
                'themes/default/tpl',
            ]
        );

        return $cleanup_sessions;
    }
}
