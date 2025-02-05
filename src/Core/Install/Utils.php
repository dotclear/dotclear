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
use Dotclear\Schema\Schema;

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
     *
     * @deprecated  Since 2.33  Use Schema::fillStructure()
     */
    public static function dbSchema(Structure $_s): void
    {
        Schema::fillStructure($_s);
    }

    /**
     * Creates default settings for active blog.
     *
     * Optionnal parameter <var>defaults</var> replaces default params while needed.
     *
     * @param   null|array<array{0:string, 1:string, 2:mixed, 3:string}>  $defaults   The defaults settings
     *
     * @todo    Move this method to Core/Blogs, as there is a similar method for user exists in Core/Users->userDefaults()
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
