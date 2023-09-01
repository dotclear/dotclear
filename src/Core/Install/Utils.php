<?php
/**
 * @package Dotclear
 * @subpackage Install
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

namespace Dotclear\Core\Install;

use dcBlog;
use dcNamespace;
use dcSettings;
use Dotclear\App;
use Dotclear\Database\Structure;
use Dotclear\Interface\Core\ConnectionInterface;

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
    public static function check(ConnectionInterface $con, array &$err)
    {
        $err = [];

        if (version_compare(phpversion(), App::release('php_min'), '<')) {
            $err[] = sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), App::release('php_min'));
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

        if ($con->syntax() == 'mysql') {
            if (version_compare($con->version(), App::release('mysql_min'), '<')) {
                $err[] = sprintf(__('MySQL version is %s (%s or earlier needed).'), $con->version(), App::release('mysql_min'));
            } else {
                $rs     = $con->select('SHOW ENGINES');
                $innodb = false;
                while ($rs->fetch()) {
                    if (strtolower($rs->f(0)) == 'innodb' && strtolower($rs->f(1)) != 'disabled' && strtolower($rs->f(1)) != 'no') {
                        $innodb = true;

                        break;
                    }
                }

                if (!$innodb) {
                    $err[] = __('MySQL InnoDB engine is not available.');
                }
            }
        } elseif ($con->driver() == 'pgsql') {
            if (version_compare($con->version(), App::release('pgsql_min'), '<')) {
                $err[] = sprintf(__('PostgreSQL version is %s (%s or earlier needed).'), $con->version(), App::release('pgsql_min'));
            }
        }

        return !count($err);
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
            ->blog_id('varchar', 32, false)
            ->blog_uid('varchar', 32, false)
            ->blog_creadt('timestamp', 0, false, 'now()')
            ->blog_upddt('timestamp', 0, false, 'now()')
            ->blog_url('varchar', 255, false)
            ->blog_name('varchar', 255, false)
            ->blog_desc('text', 0, true)
            ->blog_status('smallint', 0, false, defined('dcBlog::BLOG_ONLINE') ? dcBlog::BLOG_ONLINE : 1) // 2.24+ transition (update)

            ->primary('pk_blog', 'blog_id')
        ;

        $_s->category
            ->cat_id('bigint', 0, false)
            ->blog_id('varchar', 32, false)
            ->cat_title('varchar', 255, false)
            ->cat_url('varchar', 255, false)
            ->cat_desc('text', 0, true)
            ->cat_position('integer', 0, true, 0)
            ->cat_lft('integer', 0, true)
            ->cat_rgt('integer', 0, true)

            ->primary('pk_category', 'cat_id')

            ->unique('uk_cat_url', 'cat_url', 'blog_id')
        ;

        $_s->session
            ->ses_id('varchar', 40, false)
            ->ses_time('integer', 0, false, 0)
            ->ses_start('integer', 0, false, 0)
            ->ses_value('text', 0, false)

            ->primary('pk_session', 'ses_id')
        ;

        $_s->setting
            ->setting_id('varchar', 255, false)
            ->blog_id('varchar', 32, true)
            ->setting_ns('varchar', 32, false, "'system'")
            ->setting_value('text', 0, true, null)
            ->setting_type('varchar', 8, false, "'string'")
            ->setting_label('text', 0, true)

            ->unique('uk_setting', 'setting_ns', 'setting_id', 'blog_id')
        ;

        $_s->user
            ->user_id('varchar', 32, false)
            ->user_super('smallint', 0, true)
            ->user_status('smallint', 0, false, 1)
            ->user_pwd('varchar', 255, false)
            ->user_change_pwd('smallint', 0, false, 0)
            ->user_recover_key('varchar', 32, true, null)
            ->user_name('varchar', 255, true, null)
            ->user_firstname('varchar', 255, true, null)
            ->user_displayname('varchar', 255, true, null)
            ->user_email('varchar', 255, true, null)
            ->user_url('varchar', 255, true, null)
            ->user_desc('text', 0, true)
            ->user_default_blog('varchar', 32, true, null)
            ->user_options('text', 0, true)
            ->user_lang('varchar', 5, true, null)
            ->user_tz('varchar', 128, false, "'UTC'")
            ->user_post_status('smallint', 0, false, dcBlog::POST_PENDING)
            ->user_creadt('timestamp', 0, false, 'now()')
            ->user_upddt('timestamp', 0, false, 'now()')

            ->primary('pk_user', 'user_id')
        ;

        $_s->permissions
            ->user_id('varchar', 32, false)
            ->blog_id('varchar', 32, false)
            ->permissions('text', 0, true)

            ->primary('pk_permissions', 'user_id', 'blog_id')
        ;

        $_s->post
            ->post_id('bigint', 0, false)
            ->blog_id('varchar', 32, false)
            ->user_id('varchar', 32, false)
            ->cat_id('bigint', 0, true)
            ->post_dt('timestamp', 0, false, 'now()')
            ->post_tz('varchar', 128, false, "'UTC'")
            ->post_creadt('timestamp', 0, false, 'now()')
            ->post_upddt('timestamp', 0, false, 'now()')
            ->post_password('varchar', 32, true, null)
            ->post_type('varchar', 32, false, "'post'")
            ->post_format('varchar', 32, false, "'xhtml'")
            ->post_url('varchar', 255, false)
            ->post_lang('varchar', 5, true, null)
            ->post_title('varchar', 255, true, null)
            ->post_excerpt('text', 0, true, null)
            ->post_excerpt_xhtml('text', 0, true, null)
            ->post_content('text', 0, true, null)
            ->post_content_xhtml('text', 0, false)
            ->post_notes('text', 0, true, null)
            ->post_meta('text', 0, true, null)
            ->post_words('text', 0, true, null)
            ->post_status('smallint', 0, false, dcBlog::POST_UNPUBLISHED)
            ->post_firstpub('smallint', 0, false, 0)
            ->post_selected('smallint', 0, false, 0)
            ->post_position('integer', 0, false, 0)
            ->post_open_comment('smallint', 0, false, 0)
            ->post_open_tb('smallint', 0, false, 0)
            ->nb_comment('integer', 0, false, 0)
            ->nb_trackback('integer', 0, false, 0)

            ->primary('pk_post', 'post_id')

            ->unique('uk_post_url', 'post_url', 'post_type', 'blog_id')
        ;

        $_s->media
            ->media_id('bigint', 0, false)
            ->user_id('varchar', 32, false)
            ->media_path('varchar', 255, false)
            ->media_title('varchar', 255, false)
            ->media_file('varchar', 255, false)
            ->media_dir('varchar', 255, false, "'.'")
            ->media_meta('text', 0, true, null)
            ->media_dt('timestamp', 0, false, 'now()')
            ->media_creadt('timestamp', 0, false, 'now()')
            ->media_upddt('timestamp', 0, false, 'now()')
            ->media_private('smallint', 0, false, 0)

            ->primary('pk_media', 'media_id')
        ;

        $_s->post_media
            ->media_id('bigint', 0, false)
            ->post_id('bigint', 0, false)
            ->link_type('varchar', 32, false, "'attachment'")

            ->primary('pk_post_media', 'media_id', 'post_id', 'link_type')
        ;

        $_s->log
            ->log_id('bigint', 0, false)
            ->user_id('varchar', 32, true)
            ->blog_id('varchar', 32, true)
            ->log_table('varchar', 255, false)
            ->log_dt('timestamp', 0, false, 'now()')
            ->log_ip('varchar', 39, false)
            ->log_msg('text', 0, true, null)

            ->primary('pk_log', 'log_id')
        ;

        $_s->version
            ->module('varchar', 64, false)
            ->version('varchar', 32, false)

            ->primary('pk_version', 'module')
        ;

        $_s->ping
            ->post_id('bigint', 0, false)
            ->ping_url('varchar', 255, false)
            ->ping_dt('timestamp', 0, false, 'now()')

            ->primary('pk_ping', 'post_id', 'ping_url')
        ;

        $_s->comment
            ->comment_id('bigint', 0, false)
            ->post_id('bigint', 0, false)
            ->comment_dt('timestamp', 0, false, 'now()')
            ->comment_tz('varchar', 128, false, "'UTC'")
            ->comment_upddt('timestamp', 0, false, 'now()')
            ->comment_author('varchar', 255, true, null)
            ->comment_email('varchar', 255, true, null)
            ->comment_site('varchar', 255, true, null)
            ->comment_content('text', 0, true)
            ->comment_words('text', 0, true, null)
            ->comment_ip('varchar', 39, true, null)
            ->comment_status('smallint', 0, true, dcBlog::COMMENT_UNPUBLISHED)
            ->comment_spam_status('varchar', 128, true, 0)
            ->comment_spam_filter('varchar', 32, true, null)
            ->comment_trackback('smallint', 0, false, 0)

            ->primary('pk_comment', 'comment_id')
        ;

        $_s->meta
            ->meta_id('varchar', 255, false)
            ->meta_type('varchar', 64, false)
            ->post_id('bigint', 0, false)

            ->primary('pk_meta', 'meta_id', 'meta_type', 'post_id')
        ;

        $_s->pref
            ->pref_id('varchar', 255, false)
            ->user_id('varchar', 32, true)
            ->pref_ws('varchar', 32, false, "'system'")
            ->pref_value('text', 0, true, null)
            ->pref_type('varchar', 8, false, "'string'")
            ->pref_label('text', 0, true)

            ->unique('uk_pref', 'pref_ws', 'pref_id', 'user_id')
        ;

        $_s->notice
            ->notice_id('bigint', 0, false)
            ->ses_id('varchar', 40, false)
            ->notice_type('varchar', 32, true)
            ->notice_ts('timestamp', 0, false, 'now()')
            ->notice_msg('text', 0, true, null)
            ->notice_format('varchar', 32, true, "'text'")
            ->notice_options('text', 0, true, null)

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
        if ($_s->driver() == 'pgsql') {
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
     * @param   null|array  $defaults   The defaults settings
     */
    public static function blogDefaults(?array $defaults = null): void
    {
        if (!is_array($defaults)) {
            $defaults = [
                ['allow_comments', dcNamespace::NS_BOOL, true,
                    'Allow comments on blog', ],
                ['allow_trackbacks', dcNamespace::NS_BOOL, true,
                    'Allow trackbacks on blog', ],
                ['blog_timezone', dcNamespace::NS_STRING, 'Europe/London',
                    'Blog timezone', ],
                ['comments_nofollow', dcNamespace::NS_BOOL, true,
                    'Add rel="nofollow" to comments URLs', ],
                ['comments_pub', dcNamespace::NS_BOOL, true,
                    'Publish comments immediately', ],
                ['comments_ttl', dcNamespace::NS_INT, 0,
                    'Number of days to keep comments open (0 means no ttl)', ],
                ['copyright_notice', dcNamespace::NS_STRING, '',
                    'Copyright notice (simple text)', ],
                ['date_format', dcNamespace::NS_STRING, '%A, %B %e %Y',
                    'Date format. See PHP strftime function for patterns', ],
                ['editor', dcNamespace::NS_STRING, '',
                    'Person responsible of the content', ],
                ['enable_html_filter', dcNamespace::NS_BOOL, 0,
                    'Enable HTML filter', ],
                ['lang', dcNamespace::NS_STRING, 'en',
                    'Default blog language', ],
                ['media_exclusion', dcNamespace::NS_STRING, '/\.(phps?|pht(ml)?|phl|phar|.?html?|xml|js|htaccess)[0-9]*$/i',
                    'File name exclusion pattern in media manager. (PCRE value)', ],
                ['media_img_m_size', dcNamespace::NS_INT, 448,
                    'Image medium size in media manager', ],
                ['media_img_s_size', dcNamespace::NS_INT, 240,
                    'Image small size in media manager', ],
                ['media_img_t_size', dcNamespace::NS_INT, 100,
                    'Image thumbnail size in media manager', ],
                ['media_img_title_pattern', dcNamespace::NS_STRING, 'Title ;; Date(%b %Y) ;; separator(, )',
                    'Pattern to set image title when you insert it in a post', ],
                ['media_video_width', dcNamespace::NS_INT, 400,
                    'Video width in media manager', ],
                ['media_video_height', dcNamespace::NS_INT, 300,
                    'Video height in media manager', ],
                ['nb_post_for_home', dcNamespace::NS_INT, 20,
                    'Number of entries on first home page', ],
                ['nb_post_per_page', dcNamespace::NS_INT, 20,
                    'Number of entries on home pages and category pages', ],
                ['nb_post_per_feed', dcNamespace::NS_INT, 20,
                    'Number of entries on feeds', ],
                ['nb_comment_per_feed', dcNamespace::NS_INT, 20,
                    'Number of comments on feeds', ],
                ['post_url_format', dcNamespace::NS_STRING, '{y}/{m}/{d}/{t}',
                    'Post URL format. {y}: year, {m}: month, {d}: day, {id}: post id, {t}: entry title', ],
                ['public_path', dcNamespace::NS_STRING, 'public',
                    'Path to public directory, begins with a / for a full system path', ],
                ['public_url', dcNamespace::NS_STRING, '/public',
                    'URL to public directory', ],
                ['robots_policy', dcNamespace::NS_STRING, 'INDEX,FOLLOW',
                    'Search engines robots policy', ],
                ['short_feed_items', dcNamespace::NS_BOOL, false,
                    'Display short feed items', ],
                ['theme', dcNamespace::NS_STRING, DC_DEFAULT_THEME,
                    'Blog theme', ],
                ['themes_path', dcNamespace::NS_STRING, 'themes',
                    'Themes root path', ],
                ['themes_url', dcNamespace::NS_STRING, '/themes',
                    'Themes root URL', ],
                ['time_format', dcNamespace::NS_STRING, '%H:%M',
                    'Time format. See PHP strftime function for patterns', ],
                ['tpl_allow_php', dcNamespace::NS_BOOL, false,
                    'Allow PHP code in templates', ],
                ['tpl_use_cache', dcNamespace::NS_BOOL, true,
                    'Use template caching', ],
                ['trackbacks_pub', dcNamespace::NS_BOOL, true,
                    'Publish trackbacks immediately', ],
                ['trackbacks_ttl', dcNamespace::NS_INT, 0,
                    'Number of days to keep trackbacks open (0 means no ttl)', ],
                ['url_scan', dcNamespace::NS_STRING, 'query_string',
                    'URL handle mode (path_info or query_string)', ],
                ['no_public_css', dcNamespace::NS_BOOL, false,
                    'Don\'t use generic public.css stylesheet', ],
                ['use_smilies', dcNamespace::NS_BOOL, false,
                    'Show smilies on entries and comments', ],
                ['no_search', dcNamespace::NS_BOOL, false,
                    'Disable search', ],
                ['inc_subcats', dcNamespace::NS_BOOL, false,
                    'Include sub-categories in category page and category posts feed', ],
                ['wiki_comments', dcNamespace::NS_BOOL, false,
                    'Allow commenters to use a subset of wiki syntax', ],
                ['import_feed_url_control', dcNamespace::NS_BOOL, true,
                    'Control feed URL before import', ],
                ['import_feed_no_private_ip', dcNamespace::NS_BOOL, true,
                    'Prevent import feed from private IP', ],
                ['import_feed_ip_regexp', dcNamespace::NS_STRING, '',
                    'Authorize import feed only from this IP regexp', ],
                ['import_feed_port_regexp', dcNamespace::NS_STRING, '/^(80|443)$/',
                    'Authorize import feed only from this port regexp', ],
                ['jquery_needed', dcNamespace::NS_BOOL, true,
                    'Load jQuery library', ],
                ['sleepmode_timeout', dcNamespace::NS_INT, 31536000,
                    'Sleep mode timeout', ],
                ['store_plugin_url', dcNamespace::NS_STRING, 'https://update.dotaddict.org/dc2/plugins.xml',
                    'Plugins XML feed location', ],
                ['store_theme_url', dcNamespace::NS_STRING, 'https://update.dotaddict.org/dc2/themes.xml',
                    'Themes XML feed location', ],
            ];
        }

        $settings = new dcSettings(null);

        foreach ($defaults as $v) {
            $settings->system->put($v[0], $v[2], $v[1], $v[3], false, true);
        }
    }
}
