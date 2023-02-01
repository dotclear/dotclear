<?php
/**
 * @brief Dotclear upgrade procedure
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcUpgrade
{
    /**
     * Do Dotclear upgrade if necessary
     *
     * @throws     Exception
     *
     * @return     bool|dbStruct|int
     */
    public static function dotclearUpgrade()
    {
        $version = dcCore::app()->getVersion('core');

        if ($version === null) {
            return false;
        }

        if (version_compare($version, DC_VERSION, '<') == 1 || strpos(DC_VERSION, 'dev')) {
            try {
                if (dcCore::app()->con->driver() == 'sqlite') {
                    return false; // Need to find a way to upgrade sqlite database
                }

                # Database upgrade
                $_s = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
                require __DIR__ . '/db-schema.php';

                $si      = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
                $changes = $si->synchronize($_s);

                /* Some other upgrades
                ------------------------------------ */
                $cleanup_sessions = self::growUp($version);

                # Drop content from session table if changes or if needed
                if ($changes != 0 || $cleanup_sessions) {
                    dcCore::app()->con->execute('DELETE FROM ' . dcCore::app()->prefix . dcCore::SESSION_TABLE_NAME);
                }

                # Empty templates cache directory
                try {
                    dcCore::app()->emptyTemplatesCache();
                } catch (Exception $e) {
                }

                return $changes;
            } catch (Exception $e) {
                throw new Exception(__('Something went wrong with auto upgrade:') .
                    ' ' . $e->getMessage());
            }
        }

        # No upgrade?
        return false;
    }

    /**
     * Make necessary updates in DB and in filesystem
     *
     * @param      null|string  $version  The version
     *
     * @return     bool     true if a session cleanup is requested
     */
    public static function growUp(?string $version): bool
    {
        if ($version === null) {
            return false;
        }

        /**
         * Update it in a step that needed sessions to be removed
         *
         * @var        bool
         */
        $cleanup_sessions = false;

        # Populate media_dir field (since 2.0-beta3.3)
        if (version_compare($version, '2.0-beta3.3', '<')) {
            $strReq = 'SELECT media_id, media_file FROM ' . dcCore::app()->prefix . dcMedia::MEDIA_TABLE_NAME . ' ';
            $rs_m   = dcCore::app()->con->select($strReq);
            while ($rs_m->fetch()) {
                $cur            = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcMedia::MEDIA_TABLE_NAME);
                $cur->media_dir = dirname($rs_m->media_file);
                $cur->update('WHERE media_id = ' . (int) $rs_m->media_id);
            }
        }

        if (version_compare($version, '2.0-beta7.3', '<')) {
            # Blowup becomes default theme
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "SET setting_value = '%s' " .
                "WHERE setting_id = 'theme' " .
                "AND setting_value = '%s' " .
                'AND blog_id IS NOT NULL ';
            dcCore::app()->con->execute(sprintf($strReq, 'blueSilence', 'default'));
            dcCore::app()->con->execute(sprintf($strReq, 'default', 'blowup'));
        }

        if (version_compare($version, '2.1-alpha2-r2383', '<')) {
            $schema = dbSchema::init(dcCore::app()->con);
            $schema->dropUnique(dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME, dcCore::app()->prefix . 'uk_cat_title');

            # Reindex categories
            $rs = dcCore::app()->con->select(
                'SELECT cat_id, cat_title, blog_id ' .
                'FROM ' . dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME . ' ' .
                'ORDER BY blog_id ASC , cat_position ASC '
            );
            $cat_blog = $rs->blog_id;
            $i        = 2;
            while ($rs->fetch()) {
                if ($cat_blog != $rs->blog_id) {
                    $i = 2;
                }
                dcCore::app()->con->execute(
                    'UPDATE ' . dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME . ' SET '
                    . 'cat_lft = ' . ($i++) . ', cat_rgt = ' . ($i++) . ' ' .
                    'WHERE cat_id = ' . (int) $rs->cat_id
                );
                $cat_blog = $rs->blog_id;
            }
        }

        if (version_compare($version, '2.1.6', '<=')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    // ie7js has been upgraded
                    'admin/js/ie7/ie7-base64.php ',
                    'admin/js/ie7/ie7-content.htc',
                    'admin/js/ie7/ie7-core.js',
                    'admin/js/ie7/ie7-css2-selectors.js',
                    'admin/js/ie7/ie7-css3-selectors.js',
                    'admin/js/ie7/ie7-css-strict.js',
                    'admin/js/ie7/ie7-dhtml.js',
                    'admin/js/ie7/ie7-dynamic-attributes.js',
                    'admin/js/ie7/ie7-fixed.js',
                    'admin/js/ie7/ie7-graphics.js',
                    'admin/js/ie7/ie7-html4.js',
                    'admin/js/ie7/ie7-ie5.js',
                    'admin/js/ie7/ie7-layout.js',
                    'admin/js/ie7/ie7-load.htc',
                    'admin/js/ie7/ie7-object.htc',
                    'admin/js/ie7/ie7-overflow.js',
                    'admin/js/ie7/ie7-quirks.js',
                    'admin/js/ie7/ie7-server.css',
                    'admin/js/ie7/ie7-standard-p.js',
                    'admin/js/ie7/ie7-xml-extras.js',
                ],
            );
        }

        if (version_compare($version, '2.2-alpha1-r3043', '<')) {
            # metadata has been integrated to the core.
            dcCore::app()->plugins->loadModules(DC_PLUGINS_ROOT);
            if (dcCore::app()->plugins->moduleExists('metadata')) {
                dcCore::app()->plugins->deleteModule('metadata');
            }

            # Tags template class has been renamed
            $sqlstr = 'SELECT blog_id, setting_id, setting_value ' .
            'FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                'WHERE (setting_id = \'widgets_nav\' OR setting_id = \'widgets_extra\') ' .
                'AND setting_ns = \'widgets\';';
            $rs = dcCore::app()->con->select($sqlstr);
            while ($rs->fetch()) {
                $widgetsettings     = base64_decode($rs->setting_value);
                $widgetsettings     = str_replace('s:11:"tplMetadata"', 's:7:"tplTags"', $widgetsettings);
                $cur                = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                $cur->setting_value = base64_encode($widgetsettings);
                $sqlstr             = 'WHERE setting_id = \'' . $rs->setting_id . '\' AND setting_ns = \'widgets\' ' .
                    'AND blog_id ' .
                    ($rs->blog_id == null ? 'is NULL' : '= \'' . dcCore::app()->con->escape($rs->blog_id) . '\'');
                $cur->update($sqlstr);
            }
        }

        if (version_compare($version, '2.3', '<')) {
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
            foreach ($init_fav as $k => $f) {
                $t = ['name'     => $f[0], 'title' => $f[1], 'url' => $f[2], 'small-icon' => $f[3],
                    'large-icon' => $f[4], 'permissions' => $f[5], 'id' => $f[6], 'class' => $f[7], ];
                $sqlstr = 'INSERT INTO ' . dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME . ' (pref_id, user_id, pref_ws, pref_value, pref_type, pref_label) VALUES (' .
                '\'' . sprintf('g%03s', $count) . '\',NULL,\'favorites\',\'' . serialize($t) . '\',\'string\',NULL);';
                dcCore::app()->con->execute($sqlstr);
                $count++;
            }

            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
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
        }

        if (version_compare($version, '2.3.1', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'inc/libs/clearbricks/.hgignore',
                ],
            );
        }

        if (version_compare($version, '2.5', '<=')) {
            # Try to disable daInstaller plugin if it has been installed outside the default plugins directory
            $path    = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
            $default = path::real(__DIR__ . '/../../plugins/');
            foreach ($path as $root) {
                if (!is_dir($root) || !is_readable($root)) {
                    continue;
                }
                if (substr($root, -1) != '/') {
                    $root .= '/';
                }
                if (($p = @dir($root)) === false) {
                    continue;
                }
                if (path::real($root) == $default) {
                    continue;
                }
                if (($d = @dir($root . 'daInstaller')) === false) {
                    continue;
                }
                $f = $root . '/daInstaller/_disabled';
                if (!file_exists($f)) {
                    @file_put_contents($f, '');
                }
            }
        }

        if (version_compare($version, '2.5.1', '<=')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    // Flash enhanced upload no longer needed
                    'inc/swf/swfupload.swf',
                ],
            );
        }

        if (version_compare($version, '2.6', '<=')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'README',
                    'admin/trackbacks.php',
                ],
            );

            # daInstaller has been integrated to the core.
            # Try to remove it
            $path = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
            foreach ($path as $root) {
                if (!is_dir($root) || !is_readable($root)) {
                    continue;
                }
                if (substr($root, -1) != '/') {
                    $root .= '/';
                }
                if (($p = @dir($root)) === false) {
                    continue;
                }
                if (($d = @dir($root . 'daInstaller')) === false) {
                    continue;
                }
                files::deltree($root . '/daInstaller');
            }

            # Some settings change, prepare db queries
            $strReqFormat = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
            $strReqFormat .= ' (setting_id,setting_ns,setting_value,setting_type,setting_label)';
            $strReqFormat .= ' VALUES(\'%s\',\'system\',\'%s\',\'string\',\'%s\')';

            $strReqSelect = 'SELECT count(1) FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
            $strReqSelect .= ' WHERE setting_id = \'%s\'';
            $strReqSelect .= ' AND setting_ns = \'system\'';
            $strReqSelect .= ' AND blog_id IS NULL';

            # Add date and time formats
            $date_formats = ['%Y-%m-%d', '%m/%d/%Y', '%d/%m/%Y', '%Y/%m/%d', '%d.%m.%Y', '%b %e %Y', '%e %b %Y', '%Y %b %e',
                '%a, %Y-%m-%d', '%a, %m/%d/%Y', '%a, %d/%m/%Y', '%a, %Y/%m/%d', '%B %e, %Y', '%e %B, %Y', '%Y, %B %e', '%e. %B %Y',
                '%A, %B %e, %Y', '%A, %e %B, %Y', '%A, %Y, %B %e', '%A, %Y, %B %e', '%A, %e. %B %Y', ];
            $time_formats = ['%H:%M', '%I:%M', '%l:%M', '%Hh%M', '%Ih%M', '%lh%M'];
            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                $date_formats = array_map(fn ($f) => str_replace('%e', '%#d', $f), $date_formats);
            }

            $rs = dcCore::app()->con->select(sprintf($strReqSelect, 'date_formats'));
            if ($rs->f(0) == 0) {
                $strReq = sprintf($strReqFormat, 'date_formats', serialize($date_formats), 'Date formats examples');
                dcCore::app()->con->execute($strReq);
            }
            $rs = dcCore::app()->con->select(sprintf($strReqSelect, 'time_formats'));
            if ($rs->f(0) == 0) {
                $strReq = sprintf($strReqFormat, 'time_formats', serialize($time_formats), 'Time formats examples');
                dcCore::app()->con->execute($strReq);
            }

            # Add repository URL for themes and plugins as daInstaller move to core
            $rs = dcCore::app()->con->select(sprintf($strReqSelect, 'store_plugin_url'));
            if ($rs->f(0) == 0) {
                $strReq = sprintf($strReqFormat, 'store_plugin_url', 'http://update.dotaddict.org/dc2/plugins.xml', 'Plugins XML feed location');
                dcCore::app()->con->execute($strReq);
            }
            $rs = dcCore::app()->con->select(sprintf($strReqSelect, 'store_theme_url'));
            if ($rs->f(0) == 0) {
                $strReq = sprintf($strReqFormat, 'store_theme_url', 'http://update.dotaddict.org/dc2/themes.xml', 'Themes XML feed location');
                dcCore::app()->con->execute($strReq);
            }
        }

        if (version_compare($version, '2.7', '<=')) {
            # Some new settings should be initialized, prepare db queries
            $strReqFormat = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
            $strReqFormat .= ' (setting_id,setting_ns,setting_value,setting_type,setting_label)';
            $strReqFormat .= ' VALUES(\'%s\',\'system\',\'%s\',\'string\',\'%s\')';

            $strReqCount = 'SELECT count(1) FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
            $strReqCount .= ' WHERE setting_id = \'%s\'';
            $strReqCount .= ' AND setting_ns = \'system\'';
            $strReqCount .= ' AND blog_id IS NULL';

            $strReqSelect = 'SELECT setting_value FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
            $strReqSelect .= ' WHERE setting_id = \'%s\'';
            $strReqSelect .= ' AND setting_ns = \'system\'';
            $strReqSelect .= ' AND blog_id IS NULL';

            # Add nb of posts for home (first page), copying nb of posts on every page
            $rs = dcCore::app()->con->select(sprintf($strReqCount, 'nb_post_for_home'));
            if ($rs->f(0) == 0) {
                $rs     = dcCore::app()->con->select(sprintf($strReqSelect, 'nb_post_per_page'));
                $strReq = sprintf($strReqFormat, 'nb_post_for_home', $rs->f(0), 'Nb of posts on home (first page only)');
                dcCore::app()->con->execute($strReq);
            }
        }

        if (version_compare($version, '2.8.1', '<=')) {
            # switch from jQuery 1.11.1 to 1.11.2
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '1.11.3' " .
                " WHERE setting_id = 'jquery_version' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = '1.11.1' ";
            dcCore::app()->con->execute($strReq);
            # Some new settings should be initialized, prepare db queries
            $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
                ' VALUES(\'%s\',\'system\',\'%s\',\'boolean\',\'%s\')';
            dcCore::app()->con->execute(sprintf($strReq, 'no_search', '0', 'Disable internal search system'));
        }

        if (version_compare($version, '2.9', '<=')) {
            # Some new settings should be initialized, prepare db queries
            $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
                ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
            dcCore::app()->con->execute(
                sprintf($strReq, 'media_video_width', '400', 'integer', 'Media video insertion width')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'media_video_height', '300', 'integer', 'Media video insertion height')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'media_flash_fallback', '1', 'boolean', 'Flash player fallback for audio and video media')
            );

            # Some settings and prefs should be moved from string to array
            self::settings2array('system', 'date_formats');
            self::settings2array('system', 'time_formats');
            self::settings2array('antispam', 'antispam_filters');
            self::settings2array('pings', 'pings_uris');
            self::settings2array('system', 'simpleMenu');
            self::prefs2array('dashboard', 'favorites');
        }

        if (version_compare($version, '2.9.1', '<=')) {
            # Some settings and prefs should be moved from string to array
            self::prefs2array('dashboard', 'favorites');
            self::prefs2array('interface', 'media_last_dirs');
        }

        if (version_compare($version, '2.10', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'admin/js/jsUpload/vendor/jquery.ui.widget.js',
                ],
                // Folders
                [
                    'admin/js/jsUpload/vendor',
                ]
            );

            # Create new var directory and its .htaccess file
            @files::makeDir(DC_VAR);
            $f = DC_VAR . '/.htaccess';
            if (!file_exists($f)) {
                @file_put_contents($f, 'Require all denied' . "\n" . 'Deny from all' . "\n");
            }

            # Some new settings should be initialized, prepare db queries
            $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
                ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
            # Import feed control
            dcCore::app()->con->execute(
                sprintf($strReq, 'import_feed_url_control', true, 'boolean', 'Control feed URL before import')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'import_feed_no_private_ip', true, 'boolean', 'Prevent import feed from private IP')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'import_feed_ip_regexp', '', 'string', 'Authorize import feed only from this IP regexp')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'import_feed_port_regexp', '/^(80|443)$/', 'string', 'Authorize import feed only from this port regexp')
            );
            # CSP directive (admin part)
            dcCore::app()->con->execute(
                sprintf($strReq, 'csp_admin_on', true, 'boolean', 'Send CSP header (admin)')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'csp_admin_default', "''self''", 'string', 'CSP default-src directive')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'csp_admin_script', "''self'' ''unsafe-inline'' ''unsafe-eval''", 'string', 'CSP script-src directive')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'csp_admin_style', "''self'' ''unsafe-inline''", 'string', 'CSP style-src directive')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'csp_admin_img', "''self'' data: media.dotaddict.org", 'string', 'CSP img-src directive')
            );
        }

        if (version_compare($version, '2.11', '<')) {
            // Some new settings should be initialized, prepare db queries
            $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
                ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
            dcCore::app()->con->execute(
                sprintf($strReq, 'csp_admin_report_only', false, 'boolean', 'CSP Report only violations (admin)')
            );

            // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
                                                                                // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
            $csp_prefix = dcCore::app()->con->driver() == 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver
            $csp_suffix = dcCore::app()->con->driver() == 'sqlite' ? ' 127.0.0.1' : ''; // Hack for SQlite Clearbricks driver

            # Try to fix some CSP directive wrongly stored for SQLite drivers
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '" . $csp_prefix . "''self''" . $csp_suffix . "' " .
                " WHERE setting_id = 'csp_admin_default' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = 'self' ";
            dcCore::app()->con->execute($strReq);
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '" . $csp_prefix . "''self'' ''unsafe-inline'' ''unsafe-eval''" . $csp_suffix . "' " .
                " WHERE setting_id = 'csp_admin_script' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = 'self'' ''unsafe-inline'' ''unsafe-eval' ";
            dcCore::app()->con->execute($strReq);
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '" . $csp_prefix . "''self'' ''unsafe-inline''" . $csp_suffix . "' " .
                " WHERE setting_id = 'csp_admin_style' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = 'self'' ''unsafe-inline' ";
            dcCore::app()->con->execute($strReq);
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '" . $csp_prefix . "''self'' data: media.dotaddict.org blob:' " .
                " WHERE setting_id = 'csp_admin_img' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = 'self'' data: media.dotaddict.org' ";
            dcCore::app()->con->execute($strReq);

            # Update CSP img-src default directive
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '" . $csp_prefix . "''self'' data: media.dotaddict.org blob:' " .
                " WHERE setting_id = 'csp_admin_img' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = '''self'' data: media.dotaddict.org' ";
            dcCore::app()->con->execute($strReq);

            # Update first publication on published posts
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME .
                ' SET post_firstpub = 1' .
                ' WHERE post_status = ' . (string) dcBlog::POST_PUBLISHED;
            dcCore::app()->con->execute($strReq);

            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'admin/csp_report.txt',
                    'admin/js/jquery/jquery.modal.js',
                    'admin/style/modal/close.png',
                    'admin/style/modal/loader.gif',
                    'admin/style/modal/modal.css',
                    'admin/js/dragsort-tablerows.js',
                    'admin/js/tool-man/cookies.js',
                    'admin/js/tool-man/coordinates.js',
                    'admin/js/tool-man/core.js',
                    'admin/js/tool-man/css.js',
                    'admin/js/tool-man/drag.js',
                    'admin/js/tool-man/dragsort.js',
                    'admin/js/tool-man/events.js',
                    'admin/js/ie7/IE7.js',
                    'admin/js/ie7/IE8.js',
                    'admin/js/ie7/IE9.js',
                    'admin/js/ie7/blank.gif',
                    'admin/js/ie7/ie7-hashchange.js',
                    'admin/js/ie7/ie7-recalc.js',
                    'admin/js/ie7/ie7-squish.js',
                    'admin/style/iesucks.css',
                    'plugins/tags/js/jquery.autocomplete.js',
                    'theme/ductile/ie.css',
                ],
                // Folders
                [
                    'admin/style/modal',
                    'admin/js/tool-man',
                    'admin/js/ie7',
                ]
            );
        }

        if (version_compare($version, '2.12', '<')) {
            # switch from jQuery 2.2.0 to 2.2.4
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '2.2.4' " .
                " WHERE setting_id = 'jquery_version' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = '2.2.0' ";
            dcCore::app()->con->execute($strReq);
        }

        if (version_compare($version, '2.12.2', '<')) {
            // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
                                                                                // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
            $csp_prefix = dcCore::app()->con->driver() == 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver

            # Update CSP img-src default directive
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '" . $csp_prefix . "''self'' data: http://media.dotaddict.org blob:' " .
                " WHERE setting_id = 'csp_admin_img' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = '" . $csp_prefix . "''self'' data: media.dotaddict.org blob:' ";
            dcCore::app()->con->execute($strReq);
        }

        if (version_compare($version, '2.14', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'admin/js/jquery/jquery.bgFade.js',
                ],
            );
        }

        if (version_compare($version, '2.14.3', '<')) {
            # Update flie exclusion upload regex
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '/\\.(phps?|pht(ml)?|phl|.?html?|xml|js|htaccess)[0-9]*$/i' " .
                " WHERE setting_id = 'media_exclusion' " .
                " AND setting_ns = 'system' " .
                " AND (setting_value = '/\\.php[0-9]*$/i' " .
                "   OR setting_value = '/\\.php$/i') " .
                "   OR setting_value = '/\\.(phps?|pht(ml)?|phl)[0-9]*$/i' " .
                "   OR setting_value = '/\\.(phps?|pht(ml)?|phl|s?html?|js)[0-9]*$/i'" .
                "   OR setting_value = '/\\.(phps?|pht(ml)?|phl|s?html?|js|htaccess)[0-9]*$/i'";
            dcCore::app()->con->execute($strReq);
        }

        if (version_compare($version, '2.15', '<')) {
            # switch from jQuery 1.11.3 to 1.12.4
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = '1.12.4' " .
                " WHERE setting_id = 'jquery_version' " .
                " AND setting_ns = 'system' " .
                " AND setting_value = '1.11.3' ";
            dcCore::app()->con->execute($strReq);

            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'plugins/dcLegacyEditor/tpl/index.tpl',
                    'plugins/dcCKEditor/tpl/index.tpl',
                ],
            );
        }

        if (version_compare($version, '2.15.1', '<')) {
            // Remove unsafe-inline from CSP script directives
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = REPLACE(setting_value, '''unsafe-inline''', '') " .
                " WHERE setting_id = 'csp_admin_script' " .
                " AND setting_ns = 'system' ";
            dcCore::app()->con->execute($strReq);
        }

        if (version_compare($version, '2.16', '<')) {
            // Update DotAddict plugins store URL
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = REPLACE(setting_value, 'http://update.dotaddict.org', 'https://update.dotaddict.org') " .
                " WHERE setting_id = 'store_plugin_url' " .
                " AND setting_ns = 'system' ";
            dcCore::app()->con->execute($strReq);
            // Update DotAddict themes store URL
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = REPLACE(setting_value, 'http://update.dotaddict.org', 'https://update.dotaddict.org') " .
                " WHERE setting_id = 'store_theme_url' " .
                " AND setting_ns = 'system' ";
            dcCore::app()->con->execute($strReq);
            // Update CSP img-src default directive for media.dotaddict.org
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                " SET setting_value = REPLACE(setting_value, 'http://media.dotaddict.org', 'https://media.dotaddict.org') " .
                " WHERE setting_id = 'csp_admin_img' " .
                " AND setting_ns = 'system' ";
            dcCore::app()->con->execute($strReq);
            // Set default jQuery loading for blog
            $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
                ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
            dcCore::app()->con->execute(
                sprintf($strReq, 'jquery_needed', true, 'boolean', 'Load jQuery library')
            );

            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    // jQuery farbtastic Color picker
                    'admin/js/color-picker.js',
                    'admin/js/jquery/jquery.farbtastic.js',
                    'admin/style/farbtastic/farbtastic.css',
                    'admin/style/farbtastic/marker.png',
                    'admin/style/farbtastic/mask.png',
                    'admin/style/farbtastic/wheel.png',
                ],
                // Folders
                [
                    // jQuery farbtastic Color picker
                    'admin/style/farbtastic',
                ]
            );
        }

        if (version_compare($version, '2.16.1', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    // Oldest jQuery public lib
                    'inc/js/jquery/1.4.2/jquery.js',
                    'inc/js/jquery/1.4.2/jquery.cookie.js',
                    'inc/js/jquery/1.11.1/jquery.js',
                    'inc/js/jquery/1.11.1/jquery.cookie.js',
                    'inc/js/jquery/1.11.3/jquery.js',
                    'inc/js/jquery/1.11.3/jquery.cookie.js',
                    'inc/js/jquery/1.12.4/jquery.js',
                    'inc/js/jquery/1.12.4/jquery.cookie.js',
                    'inc/js/jquery/2.2.0/jquery.js',
                    'inc/js/jquery/2.2.0/jquery.cookie.js',
                    'inc/js/jquery/2.2.4/jquery.js',
                    'inc/js/jquery/2.2.4/jquery.cookie.js',
                    'inc/js/jquery/3.3.1/jquery.js',
                    'inc/js/jquery/3.3.1/jquery.cookie.js',
                ],
                // Folders
                [
                    // Oldest jQuery public lib
                    'inc/js/jquery/1.4.2',
                    'inc/js/jquery/1.11.1',
                    'inc/js/jquery/1.11.3',
                    'inc/js/jquery/1.12.4',
                    'inc/js/jquery/2.2.0',
                    'inc/js/jquery/2.2.4',
                    'inc/js/jquery/3.3.1',
                ]
            );
        }

        if (version_compare($version, '2.16.9', '<')) {
            // Fix 87,5% which should be 87.5% in pref for htmlfontsize
            $strReq = 'UPDATE ' . dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME .
                " SET pref_value = REPLACE(pref_value, '87,5%', '87.5%') " .
                " WHERE pref_id = 'htmlfontsize' " .
                " AND pref_ws = 'interface' ";
            dcCore::app()->con->execute($strReq);
        }

        if (version_compare($version, '2.17', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'inc/admin/class.dc.notices.php',
                ],
                // Folders
                [
                    // Oldest jQuery public lib
                    'inc/js/jquery/3.4.1',
                ]
            );

            // Help specific (files was moved)
            $remtree  = scandir(DC_ROOT . '/locales');
            $remfiles = [
                'help/blowupConfig.html',
                'help/themeEditor.html',
            ];
            foreach ($remtree as $dir) {
                if (is_dir(DC_ROOT . '/' . 'locales' . '/' . $dir) && $dir !== '.' && $dir !== '.') {
                    foreach ($remfiles as $f) {
                        @unlink(DC_ROOT . '/' . 'locales' . '/' . $dir . '/' . $f);
                    }
                }
            }
        }

        if (version_compare($version, '2.19', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    // No more used in Berlin theme
                    'themes/berlin/scripts/boxsizing.htc',
                    // That old easter egg is not more present
                    'admin/images/thanks.mp3',
                    // No more used jQuery pwd strength and cookie plugins
                    'admin/js/jquery/jquery.pwstrength.js',
                    'admin/js/jquery/jquery.biscuit.js',
                    // No more need of this fake common.js (was used by install)
                    'admin/js/mini-common.js',
                ],
                // Folders
                [
                    // Oldest jQuery public lib
                    'inc/js/jquery/3.5.1',
                    // No more used in Berlin theme
                    'themes/berlin/scripts',
                ]
            );

            # Global settings
            $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
                ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
                ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
            dcCore::app()->con->execute(
                sprintf($strReq, 'prevents_clickjacking', true, 'boolean', 'Prevents Clickjacking')
            );
            dcCore::app()->con->execute(
                sprintf($strReq, 'prevents_floc', true, 'boolean', 'Prevents FLoC tracking')
            );
        }

        if (version_compare($version, '2.21', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
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
        }

        if (version_compare($version, '2.21.2', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                null,
                // Folders
                [
                    'inc/public/default-templates/currywurst',
                    'plugins/pages/default-templates/currywurst',
                    'plugins/tags/default-templates/currywurst',
                ]
            );
        }

        if (version_compare($version, '2.23', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
                // Files
                [
                    'admin/images/module.png',
                ],
            );
        }

        if (version_compare($version, '2.24', '<')) {
            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
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
        }

        if (version_compare($version, '2.25', '<')) {
            // Plugin blowupConfig has been integrated to the theme blowup.
            dcCore::app()->plugins->loadModules(DC_PLUGINS_ROOT);
            if (dcCore::app()->plugins->moduleExists('blowupConfig')) {
                dcCore::app()->plugins->deleteModule('blowupConfig');
            }

            // Remove removed blogs from users default blog
            $ids = [];
            $rs  = (new dcSelectStatement())
                ->from(dcCore::app()->prefix . dcBlog::BLOG_TABLE_NAME)
                ->where('blog_status = ' . dcBlog::BLOG_REMOVED)
                ->select();
            while ($rs->fetch()) {
                $ids[] = $rs->blog_id;
            }
            if (count($ids)) {
                dcCore::app()->removeUsersDefaultBlogs($ids);
            }

            // A bit of housecleaning for no longer needed folders
            self::houseCleaning(
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
        }

        dcCore::app()->setVersion('core', DC_VERSION);
        dcCore::app()->blogDefaults();

        return $cleanup_sessions;
    }

    /**
     * Convert old-fashion serialized array setting to new-fashion json encoded array
     *
     * @param      string  $ns        namespace name
     * @param      string  $setting   The setting ID
     */
    public static function settings2array(string $ns, string $setting)
    {
        $strReqSelect = 'SELECT setting_id,blog_id,setting_ns,setting_type,setting_value FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "WHERE setting_id = '%s' " .
            "AND setting_ns = '%s' " .
            "AND setting_type = 'string'";
        $rs = dcCore::app()->con->select(sprintf($strReqSelect, $setting, $ns));
        while ($rs->fetch()) {
            $value = @unserialize($rs->setting_value);
            if (!$value) {
                $value = [];
            }
            settype($value, 'array');
            $value = json_encode($value, JSON_THROW_ON_ERROR);
            $rs2   = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "SET setting_type='array', setting_value = '" . dcCore::app()->con->escape($value) . "' " .
            "WHERE setting_id='" . dcCore::app()->con->escape($rs->setting_id) . "' " .
            "AND setting_ns='" . dcCore::app()->con->escape($rs->setting_ns) . "' ";
            if ($rs->blog_id == '') {
                $rs2 .= 'AND blog_id IS null';
            } else {
                $rs2 .= "AND blog_id = '" . dcCore::app()->con->escape($rs->blog_id) . "'";
            }
            dcCore::app()->con->execute($rs2);
        }
    }

    /**
     * Convert old-fashion serialized array pref to new-fashion json encoded array
     *
     * @param      string  $ws     workspace name
     * @param      string  $pref   The preference ID
     */
    public static function prefs2array(string $ws, string $pref)
    {
        $strReqSelect = 'SELECT pref_id,user_id,pref_ws,pref_type,pref_value FROM ' . dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME . ' ' .
            "WHERE pref_id = '%s' " .
            "AND pref_ws = '%s' " .
            "AND pref_type = 'string'";
        $rs = dcCore::app()->con->select(sprintf($strReqSelect, $pref, $ws));
        while ($rs->fetch()) {
            $value = @unserialize($rs->pref_value);
            if (!$value) {
                $value = [];
            }
            settype($value, 'array');
            $value = json_encode($value, JSON_THROW_ON_ERROR);
            $rs2   = 'UPDATE ' . dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME . ' ' .
            "SET pref_type='array', pref_value = '" . dcCore::app()->con->escape($value) . "' " .
            "WHERE pref_id='" . dcCore::app()->con->escape($rs->pref_id) . "' " .
            "AND pref_ws='" . dcCore::app()->con->escape($rs->pref_ws) . "' ";
            if ($rs->user_id == '') {
                $rs2 .= 'AND user_id IS null';
            } else {
                $rs2 .= "AND user_id = '" . dcCore::app()->con->escape($rs->user_id) . "'";
            }
            dcCore::app()->con->execute($rs2);
        }
    }

    /**
     * Remove files and/or folders
     *
     * @param      array|null  $files    The files
     * @param      array|null  $folders  The folders
     */
    private static function houseCleaning(?array $files = null, ?array $folders = null)
    {
        if (!defined('DC_ROOT') || (DC_ROOT === '')) {
            return;
        }

        if (is_array($files)) {
            foreach ($files as $f) {
                if (file_exists(DC_ROOT . '/' . $f)) {
                    @unlink(DC_ROOT . '/' . $f);
                }
            }
        }

        if (is_array($folders)) {
            foreach ($folders as $f) {
                if (file_exists(DC_ROOT . '/' . $f)) {
                    files::deltree(DC_ROOT . '/' . $f);
                }
            }
        }
    }
}
