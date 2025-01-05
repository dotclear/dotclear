<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Core\Install;

use Dotclear\App;
use Dotclear\Database\Structure;
use Dotclear\Interface\Core\ConnectionInterface;

/**
 * @brief   Installation helpers
 */
class Utils
{
    /**
     * Check server support.
     *
     * @param   ConnectionInterface     $con    The db handler instance
     * @param   array<int,string>       $err    The errors
     *
     * @return  bool    False on error
     */
    public static function check(ConnectionInterface $con, array &$err): bool
    {
        $err = [];

        if (version_compare(phpversion(), App::config()->minRequiredPhp(), '<')) {
            $err[] = sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), App::config()->minRequiredPhp());
        }

        if (!function_exists('mb_detect_encoding')) {
            $err[] = __('Multibyte string module (mbstring) is not available.');
        }

        if (!function_exists('iconv')) {
            $err[] = __('Iconv module is not available.');
        }

        if (!function_exists('ob_start')) {
            $err[] = __('Output control functions are not available.');
        }

        if (!function_exists('simplexml_load_string')) {
            $err[] = __('SimpleXML module is not available.');
        }

        if (!function_exists('dom_import_simplexml')) {
            $err[] = __('DOM XML module is not available.');
        }

        $pcre_str = base64_decode('w6nDqMOgw6o=');
        if (!@preg_match('/' . $pcre_str . '/u', $pcre_str)) {
            $err[] = __('PCRE engine does not support UTF-8 strings.');
        }

        if (!function_exists('spl_classes')) {
            $err[] = __('SPL module is not available.');
        }

        if ($con->syntax() === 'mysql') {
            if (version_compare($con->version(), App::config()->minRequiredMysql(), '<')) {
                $err[] = sprintf(__('MySQL version is %s (%s or earlier needed).'), $con->version(), App::config()->minRequiredMysql());
            } else {
                $rs     = $con->select('SHOW ENGINES');
                $innodb = false;
                while ($rs->fetch()) {
                    if (strtolower((string) $rs->f(0)) === 'innodb' && strtolower((string) $rs->f(1)) !== 'disabled' && strtolower((string) $rs->f(1)) !== 'no') {
                        $innodb = true;

                        break;
                    }
                }

                if (!$innodb) {
                    $err[] = __('MySQL InnoDB engine is not available.');
                }
            }
        } elseif ($con->driver() === 'pgsql') {
            if (version_compare($con->version(), App::config()->minRequiredPgsql(), '<')) {
                $err[] = sprintf(__('PostgreSQL version is %s (%s or earlier needed).'), $con->version(), App::config()->minRequiredPgsql());
            }
        }

        return $err === [];
    }

    /**
     * Fill database structure.
     *
     * @param   Structure   $_s     The database structure handler instance
     */
    public static function dbSchema(Structure $_s): void
    {
        /* Tables
        -------------------------------------------------------- */
        $_s->blog
            ->field('blog_id', 'varchar', 32, false)
            ->field('blog_uid', 'varchar', 32, false)
            ->field('blog_creadt', 'timestamp', 0, false, 'now()')
            ->field('blog_upddt', 'timestamp', 0, false, 'now()')
            ->field('blog_url', 'varchar', 255, false)
            ->field('blog_name', 'varchar', 255, false)
            ->field('blog_desc', 'text', 0, true)
            ->field('blog_status', 'smallint', 0, false, App::status()->blog()->level('online')) // 2.24+ transition ', update)

            ->primary('pk_blog', 'blog_id')
        ;

        $_s->category
            ->field('cat_id', 'bigint', 0, false)
            ->field('blog_id', 'varchar', 32, false)
            ->field('cat_title', 'varchar', 255, false)
            ->field('cat_url', 'varchar', 255, false)
            ->field('cat_desc', 'text', 0, true)
            ->field('cat_position', 'integer', 0, true, 0)
            ->field('cat_lft', 'integer', 0, true)
            ->field('cat_rgt', 'integer', 0, true)

            ->primary('pk_category', 'cat_id')

            ->unique('uk_cat_url', 'cat_url', 'blog_id')
        ;

        $_s->session
            ->field('ses_id', 'varchar', 40, false)
            ->field('ses_time', 'integer', 0, false, 0)
            ->field('ses_start', 'integer', 0, false, 0)
            ->field('ses_value', 'text', 0, false)

            ->primary('pk_session', 'ses_id')
        ;

        $_s->setting
            ->field('setting_id', 'varchar', 255, false)
            ->field('blog_id', 'varchar', 32, true)
            ->field('setting_ns', 'varchar', 32, false, "'system'")
            ->field('setting_value', 'text', 0, true, null)
            ->field('setting_type', 'varchar', 8, false, "'string'")
            ->field('setting_label', 'text', 0, true)

            ->unique('uk_setting', 'setting_ns', 'setting_id', 'blog_id')
        ;

        $_s->user
            ->field('user_id', 'varchar', 32, false)
            ->field('user_super', 'smallint', 0, true)
            ->field('user_status', 'smallint', 0, false, App::status()->user()->level('enabled'))
            ->field('user_pwd', 'varchar', 255, false)
            ->field('user_change_pwd', 'smallint', 0, false, 0)
            ->field('user_recover_key', 'varchar', 32, true, null)
            ->field('user_name', 'varchar', 255, true, null)
            ->field('user_firstname', 'varchar', 255, true, null)
            ->field('user_displayname', 'varchar', 255, true, null)
            ->field('user_email', 'varchar', 255, true, null)
            ->field('user_url', 'varchar', 255, true, null)
            ->field('user_desc', 'text', 0, true)
            ->field('user_default_blog', 'varchar', 32, true, null)
            ->field('user_options', 'text', 0, true)
            ->field('user_lang', 'varchar', 5, true, null)
            ->field('user_tz', 'varchar', 128, false, "'UTC'")
            ->field('user_post_status', 'smallint', 0, false, App::status()->post()->level('pending'))
            ->field('user_creadt', 'timestamp', 0, false, 'now()')
            ->field('user_upddt', 'timestamp', 0, false, 'now()')

            ->primary('pk_user', 'user_id')
        ;

        $_s->permissions
            ->field('user_id', 'varchar', 32, false)
            ->field('blog_id', 'varchar', 32, false)
            ->field('permissions', 'text', 0, true)

            ->primary('pk_permissions', 'user_id', 'blog_id')
        ;

        $_s->post
            ->field('post_id', 'bigint', 0, false)
            ->field('blog_id', 'varchar', 32, false)
            ->field('user_id', 'varchar', 32, false)
            ->field('cat_id', 'bigint', 0, true)
            ->field('post_dt', 'timestamp', 0, false, 'now()')
            ->field('post_tz', 'varchar', 128, false, "'UTC'")
            ->field('post_creadt', 'timestamp', 0, false, 'now()')
            ->field('post_upddt', 'timestamp', 0, false, 'now()')
            ->field('post_password', 'varchar', 32, true, null)
            ->field('post_type', 'varchar', 32, false, "'post'")
            ->field('post_format', 'varchar', 32, false, "'xhtml'")
            ->field('post_url', 'varchar', 255, false)
            ->field('post_lang', 'varchar', 5, true, null)
            ->field('post_title', 'varchar', 255, true, null)
            ->field('post_excerpt', 'text', 0, true, null)
            ->field('post_excerpt_xhtml', 'text', 0, true, null)
            ->field('post_content', 'text', 0, true, null)
            ->field('post_content_xhtml', 'text', 0, false)
            ->field('post_notes', 'text', 0, true, null)
            ->field('post_meta', 'text', 0, true, null)
            ->field('post_words', 'text', 0, true, null)
            ->field('post_status', 'smallint', 0, false, App::status()->post()->level('unpublished'))
            ->field('post_firstpub', 'smallint', 0, false, 0)
            ->field('post_selected', 'smallint', 0, false, 0)
            ->field('post_position', 'integer', 0, false, 0)
            ->field('post_open_comment', 'smallint', 0, false, 0)
            ->field('post_open_tb', 'smallint', 0, false, 0)
            ->field('nb_comment', 'integer', 0, false, 0)
            ->field('nb_trackback', 'integer', 0, false, 0)

            ->primary('pk_post', 'post_id')

            ->unique('uk_post_url', 'post_url', 'post_type', 'blog_id')
        ;

        $_s->media
            ->field('media_id', 'bigint', 0, false)
            ->field('user_id', 'varchar', 32, false)
            ->field('media_path', 'varchar', 255, false)
            ->field('media_title', 'varchar', 255, false)
            ->field('media_file', 'varchar', 255, false)
            ->field('media_dir', 'varchar', 255, false, "'.'")
            ->field('media_meta', 'text', 0, true, null)
            ->field('media_dt', 'timestamp', 0, false, 'now()')
            ->field('media_creadt', 'timestamp', 0, false, 'now()')
            ->field('media_upddt', 'timestamp', 0, false, 'now()')
            ->field('media_private', 'smallint', 0, false, 0)

            ->primary('pk_media', 'media_id')
        ;

        $_s->post_media
            ->field('media_id', 'bigint', 0, false)
            ->field('post_id', 'bigint', 0, false)
            ->field('link_type', 'varchar', 32, false, "'attachment'")

            ->primary('pk_post_media', 'media_id', 'post_id', 'link_type')
        ;

        $_s->log
            ->field('log_id', 'bigint', 0, false)
            ->field('user_id', 'varchar', 32, true)
            ->field('blog_id', 'varchar', 32, true)
            ->field('log_table', 'varchar', 255, false)
            ->field('log_dt', 'timestamp', 0, false, 'now()')
            ->field('log_ip', 'varchar', 39, false)
            ->field('log_msg', 'text', 0, true, null)

            ->primary('pk_log', 'log_id')
        ;

        $_s->version
            ->field('module', 'varchar', 64, false)
            ->field('version', 'varchar', 32, false)

            ->primary('pk_version', 'module')
        ;

        $_s->ping
            ->field('post_id', 'bigint', 0, false)
            ->field('ping_url', 'varchar', 255, false)
            ->field('ping_dt', 'timestamp', 0, false, 'now()')

            ->primary('pk_ping', 'post_id', 'ping_url')
        ;

        $_s->comment
            ->field('comment_id', 'bigint', 0, false)
            ->field('post_id', 'bigint', 0, false)
            ->field('comment_dt', 'timestamp', 0, false, 'now()')
            ->field('comment_tz', 'varchar', 128, false, "'UTC'")
            ->field('comment_upddt', 'timestamp', 0, false, 'now()')
            ->field('comment_author', 'varchar', 255, true, null)
            ->field('comment_email', 'varchar', 255, true, null)
            ->field('comment_site', 'varchar', 255, true, null)
            ->field('comment_content', 'text', 0, true)
            ->field('comment_words', 'text', 0, true, null)
            ->field('comment_ip', 'varchar', 39, true, null)
            ->field('comment_status', 'smallint', 0, true, App::blog()::COMMENT_UNPUBLISHED)
            ->field('comment_spam_status', 'varchar', 128, true, 0)
            ->field('comment_spam_filter', 'varchar', 32, true, null)
            ->field('comment_trackback', 'smallint', 0, false, 0)

            ->primary('pk_comment', 'comment_id')
        ;

        $_s->meta
            ->field('meta_id', 'varchar', 255, false)
            ->field('meta_type', 'varchar', 64, false)
            ->field('post_id', 'bigint', 0, false)

            ->primary('pk_meta', 'meta_id', 'meta_type', 'post_id')
        ;

        $_s->pref
            ->field('pref_id', 'varchar', 255, false)
            ->field('user_id', 'varchar', 32, true)
            ->field('pref_ws', 'varchar', 32, false, "'system'")
            ->field('pref_value', 'text', 0, true, null)
            ->field('pref_type', 'varchar', 8, false, "'string'")
            ->field('pref_label', 'text', 0, true)

            ->unique('uk_pref', 'pref_ws', 'pref_id', 'user_id')
        ;

        $_s->notice
            ->field('notice_id', 'bigint', 0, false)
            ->field('ses_id', 'varchar', 40, false)
            ->field('notice_type', 'varchar', 32, true)
            ->field('notice_ts', 'timestamp', 0, false, 'now()')
            ->field('notice_msg', 'text', 0, true, null)
            ->field('notice_format', 'varchar', 32, true, "'text'")
            ->field('notice_options', 'text', 0, true, null)

            ->primary('pk_notice', 'notice_id')
        ;

        /* References indexes
        -------------------------------------------------------- */
        $_s->category->index('idx_category_blog_id', 'btree', 'blog_id');
        $_s->category->index('idx_category_cat_lft_blog_id', 'btree', 'blog_id', 'cat_lft');
        $_s->category->index('idx_category_cat_rgt_blog_id', 'btree', 'blog_id', 'cat_rgt');
        $_s->setting->index('idx_setting_blog_id', 'btree', 'blog_id');
        $_s->user->index('idx_user_user_default_blog', 'btree', 'user_default_blog');
        $_s->permissions->index('idx_permissions_blog_id', 'btree', 'blog_id');
        $_s->post->index('idx_post_cat_id', 'btree', 'cat_id');
        $_s->post->index('idx_post_user_id', 'btree', 'user_id');
        $_s->post->index('idx_post_blog_id', 'btree', 'blog_id');
        $_s->media->index('idx_media_user_id', 'btree', 'user_id');
        $_s->post_media->index('idx_post_media_post_id', 'btree', 'post_id');
        $_s->post_media->index('idx_post_media_media_id', 'btree', 'media_id');
        $_s->log->index('idx_log_user_id', 'btree', 'user_id');
        $_s->comment->index('idx_comment_post_id', 'btree', 'post_id');
        $_s->meta->index('idx_meta_post_id', 'btree', 'post_id');
        $_s->meta->index('idx_meta_meta_type', 'btree', 'meta_type');
        $_s->pref->index('idx_pref_user_id', 'btree', 'user_id');

        /* Performance indexes
        -------------------------------------------------------- */
        $_s->comment->index('idx_comment_post_id_dt_status', 'btree', 'post_id', 'comment_dt', 'comment_status');
        $_s->post->index('idx_post_post_dt', 'btree', 'post_dt');
        $_s->post->index('idx_post_post_dt_post_id', 'btree', 'post_dt', 'post_id');
        $_s->post->index('idx_blog_post_post_dt_post_id', 'btree', 'blog_id', 'post_dt', 'post_id');
        $_s->post->index('idx_blog_post_post_status', 'btree', 'blog_id', 'post_status');
        $_s->blog->index('idx_blog_blog_upddt', 'btree', 'blog_upddt');
        $_s->user->index('idx_user_user_super', 'btree', 'user_super');

        /* Foreign keys
        -------------------------------------------------------- */
        $_s->category->reference('fk_category_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
        $_s->setting->reference('fk_setting_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
        $_s->user->reference('fk_user_default_blog', 'user_default_blog', 'blog', 'blog_id', 'cascade', 'set null');
        $_s->permissions->reference('fk_permissions_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
        $_s->permissions->reference('fk_permissions_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');
        $_s->post->reference('fk_post_category', 'cat_id', 'category', 'cat_id', 'cascade', 'set null');
        $_s->post->reference('fk_post_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');
        $_s->post->reference('fk_post_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
        $_s->media->reference('fk_media_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');
        $_s->post_media->reference('fk_media', 'media_id', 'media', 'media_id', 'cascade', 'cascade');
        $_s->post_media->reference('fk_media_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
        $_s->ping->reference('fk_ping_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
        $_s->comment->reference('fk_comment_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
        $_s->log->reference('fk_log_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'set null');
        $_s->meta->reference('fk_meta_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
        $_s->pref->reference('fk_pref_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');
        $_s->notice->reference('fk_notice_session', 'ses_id', 'session', 'ses_id', 'cascade', 'cascade');

        /* PostgreSQL specific indexes
        -------------------------------------------------------- */
        if ($_s->driver() === 'pgsql') {
            $_s->setting->index('idx_setting_blog_id_null', 'btree', '(blog_id IS NULL)');
            $_s->media->index('idx_media_media_path', 'btree', 'media_path', 'media_dir');
            $_s->pref->index('idx_pref_user_id_null', 'btree', '(user_id IS NULL)');
        }
    }

    /**
     * Creates default settings for active blog.
     *
     * Optionnal parameter <var>defaults</var> replaces default params while needed.
     *
     * @param   null|array<array{0:string, 1:string, 2:mixed, 3:string}>  $defaults   The defaults settings
     */
    public static function blogDefaults(?array $defaults = null): void
    {
        if (!is_array($defaults)) {
            $defaults = [
                ['allow_comments', App::blogWorkspace()::NS_BOOL, true,
                    'Allow comments on blog', ],
                ['allow_trackbacks', App::blogWorkspace()::NS_BOOL, true,
                    'Allow trackbacks on blog', ],
                ['blog_timezone', App::blogWorkspace()::NS_STRING, 'Europe/London',
                    'Blog timezone', ],
                ['comments_nofollow', App::blogWorkspace()::NS_BOOL, true,
                    'Add rel="nofollow" to comments URLs', ],
                ['comments_pub', App::blogWorkspace()::NS_BOOL, true,
                    'Publish comments immediately', ],
                ['comments_ttl', App::blogWorkspace()::NS_INT, 0,
                    'Number of days to keep comments open (0 means no ttl)', ],
                ['copyright_notice', App::blogWorkspace()::NS_STRING, '',
                    'Copyright notice (simple text)', ],
                ['date_format', App::blogWorkspace()::NS_STRING, '%A, %B %e %Y',
                    'Date format. See PHP strftime function for patterns', ],
                ['editor', App::blogWorkspace()::NS_STRING, '',
                    'Person responsible of the content', ],
                ['enable_html_filter', App::blogWorkspace()::NS_BOOL, 0,
                    'Enable HTML filter', ],
                ['lang', App::blogWorkspace()::NS_STRING, 'en',
                    'Default blog language', ],
                ['media_exclusion', App::blogWorkspace()::NS_STRING, '/\.(phps?|pht(ml)?|phl|phar|.?html?|inc|xml|js|htaccess)[0-9]*$/i',
                    'File name exclusion pattern in media manager. (PCRE value)', ],
                ['media_img_m_size', App::blogWorkspace()::NS_INT, 448,
                    'Image medium size in media manager', ],
                ['media_img_s_size', App::blogWorkspace()::NS_INT, 240,
                    'Image small size in media manager', ],
                ['media_img_t_size', App::blogWorkspace()::NS_INT, 100,
                    'Image thumbnail size in media manager', ],
                ['media_img_title_pattern', App::blogWorkspace()::NS_STRING, 'Description ;; Date(%b %Y) ;; separator(, )',
                    'Pattern to set image legend when you insert it in a post', ],
                ['media_video_width', App::blogWorkspace()::NS_INT, 400,
                    'Video width in media manager', ],
                ['media_video_height', App::blogWorkspace()::NS_INT, 300,
                    'Video height in media manager', ],
                ['nb_post_for_home', App::blogWorkspace()::NS_INT, 20,
                    'Number of entries on first home page', ],
                ['nb_post_per_page', App::blogWorkspace()::NS_INT, 20,
                    'Number of entries on home pages and category pages', ],
                ['nb_post_per_feed', App::blogWorkspace()::NS_INT, 20,
                    'Number of entries on feeds', ],
                ['nb_comment_per_feed', App::blogWorkspace()::NS_INT, 20,
                    'Number of comments on feeds', ],
                ['post_url_format', App::blogWorkspace()::NS_STRING, '{y}/{m}/{d}/{t}',
                    'Post URL format. {y}: year, {m}: month, {d}: day, {id}: post id, {t}: entry title', ],
                ['public_path', App::blogWorkspace()::NS_STRING, 'public',
                    'Path to public directory, begins with a / for a full system path', ],
                ['public_url', App::blogWorkspace()::NS_STRING, '/public',
                    'URL to public directory', ],
                ['robots_policy', App::blogWorkspace()::NS_STRING, 'INDEX,FOLLOW',
                    'Search engines robots policy', ],
                ['short_feed_items', App::blogWorkspace()::NS_BOOL, false,
                    'Display short feed items', ],
                ['theme', App::blogWorkspace()::NS_STRING, App::config()->defaultTheme(),
                    'Blog theme', ],
                ['themes_path', App::blogWorkspace()::NS_STRING, 'themes',
                    'Themes root path', ],
                ['themes_url', App::blogWorkspace()::NS_STRING, '/themes',
                    'Themes root URL', ],
                ['time_format', App::blogWorkspace()::NS_STRING, '%H:%M',
                    'Time format. See PHP strftime function for patterns', ],
                ['tpl_allow_php', App::blogWorkspace()::NS_BOOL, false,
                    'Allow PHP code in templates', ],
                ['tpl_use_cache', App::blogWorkspace()::NS_BOOL, true,
                    'Use template caching', ],
                ['trackbacks_pub', App::blogWorkspace()::NS_BOOL, true,
                    'Publish trackbacks immediately', ],
                ['trackbacks_ttl', App::blogWorkspace()::NS_INT, 0,
                    'Number of days to keep trackbacks open (0 means no ttl)', ],
                ['url_scan', App::blogWorkspace()::NS_STRING, 'query_string',
                    'URL handle mode (path_info or query_string)', ],
                ['no_public_css', App::blogWorkspace()::NS_BOOL, false,
                    'Don\'t use generic public.css stylesheet', ],
                ['use_smilies', App::blogWorkspace()::NS_BOOL, false,
                    'Show smilies on entries and comments', ],
                ['no_search', App::blogWorkspace()::NS_BOOL, false,
                    'Disable search', ],
                ['inc_subcats', App::blogWorkspace()::NS_BOOL, false,
                    'Include sub-categories in category page and category posts feed', ],
                ['wiki_comments', App::blogWorkspace()::NS_BOOL, false,
                    'Allow commenters to use a subset of wiki syntax', ],
                ['import_feed_url_control', App::blogWorkspace()::NS_BOOL, true,
                    'Control feed URL before import', ],
                ['import_feed_no_private_ip', App::blogWorkspace()::NS_BOOL, true,
                    'Prevent import feed from private IP', ],
                ['import_feed_ip_regexp', App::blogWorkspace()::NS_STRING, '',
                    'Authorize import feed only from this IP regexp', ],
                ['import_feed_port_regexp', App::blogWorkspace()::NS_STRING, '/^(80|443)$/',
                    'Authorize import feed only from this port regexp', ],
                ['jquery_needed', App::blogWorkspace()::NS_BOOL, true,
                    'Load jQuery library', ],
                ['legacy_needed', App::blogWorkspace()::NS_BOOL, true,
                    'Load Legacy JS library', ],
                ['sleepmode_timeout', App::blogWorkspace()::NS_INT, 31536000,
                    'Sleep mode timeout', ],
                ['store_plugin_url', App::blogWorkspace()::NS_STRING, 'https://update.dotaddict.org/dc2/plugins.xml',
                    'Plugins XML feed location', ],
                ['store_theme_url', App::blogWorkspace()::NS_STRING, 'https://update.dotaddict.org/dc2/themes.xml',
                    'Themes XML feed location', ],
            ];
        }

        $settings = App::blogSettings();

        foreach ($defaults as $v) {
            $settings->system->put($v[0], $v[2], $v[1], $v[3], false, true);
        }
    }
}
