<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

/* Start tick  */
define('DC_START_TIME', microtime(true));

/* ------------------------------------------------------------------------------------------- */
#  ClearBricks, DotClear classes auto-loader
if (@is_dir('/usr/lib/clearbricks')) {
    define('CLEARBRICKS_PATH', '/usr/lib/clearbricks');
} elseif (is_dir(__DIR__ . '/libs/clearbricks')) {
    define('CLEARBRICKS_PATH', __DIR__ . '/libs/clearbricks');
} elseif (isset($_SERVER['CLEARBRICKS_PATH']) && is_dir($_SERVER['CLEARBRICKS_PATH'])) {
    define('CLEARBRICKS_PATH', $_SERVER['CLEARBRICKS_PATH']);
}

if (!defined('CLEARBRICKS_PATH') || !is_dir(CLEARBRICKS_PATH)) {
    exit('No clearbricks path defined');
}

require CLEARBRICKS_PATH . '/_common.php';

$__autoload['dcCore']            = __DIR__ . '/core/class.dc.core.php';
$__autoload['dcAuth']            = __DIR__ . '/core/class.dc.auth.php';
$__autoload['dcBlog']            = __DIR__ . '/core/class.dc.blog.php';
$__autoload['dcCategories']      = __DIR__ . '/core/class.dc.categories.php';
$__autoload['dcError']           = __DIR__ . '/core/class.dc.error.php';
$__autoload['dcMeta']            = __DIR__ . '/core/class.dc.meta.php';
$__autoload['dcMedia']           = __DIR__ . '/core/class.dc.media.php';
$__autoload['dcPostMedia']       = __DIR__ . '/core/class.dc.postmedia.php';
$__autoload['dcModules']         = __DIR__ . '/core/class.dc.modules.php';
$__autoload['dcPlugins']         = __DIR__ . '/core/class.dc.plugins.php';
$__autoload['dcThemes']          = __DIR__ . '/core/class.dc.themes.php';
$__autoload['dcRestServer']      = __DIR__ . '/core/class.dc.rest.php';
$__autoload['dcNamespace']       = __DIR__ . '/core/class.dc.namespace.php';
$__autoload['dcNotices']         = __DIR__ . '/core/class.dc.notices.php';
$__autoload['dcSettings']        = __DIR__ . '/core/class.dc.settings.php';
$__autoload['dcTrackback']       = __DIR__ . '/core/class.dc.trackback.php';
$__autoload['dcUpdate']          = __DIR__ . '/core/class.dc.update.php';
$__autoload['dcUtils']           = __DIR__ . '/core/class.dc.utils.php';
$__autoload['dcXmlRpc']          = __DIR__ . '/core/class.dc.xmlrpc.php';
$__autoload['dcLog']             = __DIR__ . '/core/class.dc.log.php';
$__autoload['rsExtLog']          = __DIR__ . '/core/class.dc.log.php';
$__autoload['dcWorkspace']       = __DIR__ . '/core/class.dc.workspace.php';
$__autoload['dcPrefs']           = __DIR__ . '/core/class.dc.prefs.php';
$__autoload['dcStore']           = __DIR__ . '/core/class.dc.store.php';
$__autoload['dcStoreReader']     = __DIR__ . '/core/class.dc.store.reader.php';
$__autoload['dcStoreParser']     = __DIR__ . '/core/class.dc.store.parser.php';
$__autoload['dcSqlStatement']    = __DIR__ . '/core/class.dc.sql.statement.php';
$__autoload['dcSelectStatement'] = __DIR__ . '/core/class.dc.sql.statement.php';
$__autoload['dcUpdateStatement'] = __DIR__ . '/core/class.dc.sql.statement.php';
$__autoload['dcDeleteStatement'] = __DIR__ . '/core/class.dc.sql.statement.php';
$__autoload['dcInsertStatement'] = __DIR__ . '/core/class.dc.sql.statement.php';
$__autoload['rsExtPost']         = __DIR__ . '/core/class.dc.rs.extensions.php';
$__autoload['rsExtComment']      = __DIR__ . '/core/class.dc.rs.extensions.php';
$__autoload['rsExtDates']        = __DIR__ . '/core/class.dc.rs.extensions.php';
$__autoload['rsExtUser']         = __DIR__ . '/core/class.dc.rs.extensions.php';

$__autoload['dcUpgrade'] = __DIR__ . '/dbschema/upgrade.php';

$__autoload['dcMenu']                = __DIR__ . '/admin/class.dc.menu.php';
$__autoload['dcFavorites']           = __DIR__ . '/admin/class.dc.favorites.php';
$__autoload['dcPage']                = __DIR__ . '/admin/lib.dc.page.php';
$__autoload['adminGenericList']      = __DIR__ . '/admin/lib.pager.php';
$__autoload['adminPostList']         = __DIR__ . '/admin/lib.pager.php';
$__autoload['adminPostMiniList']     = __DIR__ . '/admin/lib.pager.php';
$__autoload['adminCommentList']      = __DIR__ . '/admin/lib.pager.php';
$__autoload['adminBlogList']         = __DIR__ . '/admin/lib.pager.php';
$__autoload['adminUserList']         = __DIR__ . '/admin/lib.pager.php';
$__autoload['adminMediaList']        = __DIR__ . '/admin/lib.pager.php';
$__autoload['dcPager']               = __DIR__ . '/admin/lib.pager.php';
$__autoload['dcAdminCombos']         = __DIR__ . '/admin/lib.admincombos.php';
$__autoload['dcAdminFilter']         = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['dcAdminFilters']        = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['adminGenericFilter']    = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['adminPostFilter']       = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['adminCommentFilter']    = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['adminUserFilter']       = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['adminBlogFilter']       = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['adminMediaFilter']      = __DIR__ . '/admin/lib.adminfilters.php';
$__autoload['adminModulesList']      = __DIR__ . '/admin/lib.moduleslist.php';
$__autoload['adminThemesList']       = __DIR__ . '/admin/lib.moduleslist.php';
$__autoload['dcThemeConfig']         = __DIR__ . '/admin/lib.themeconfig.php';
$__autoload['dcAdminURL']            = __DIR__ . '/admin/lib.dc.adminurl.php';
$__autoload['dcAdminNotices']        = __DIR__ . '/admin/lib.dc.notices.php';
$__autoload['dcPostsActionsPage']    = __DIR__ . '/admin/actions/class.dcactionposts.php';
$__autoload['dcCommentsActionsPage'] = __DIR__ . '/admin/actions/class.dcactioncomments.php';
$__autoload['dcBlogsActionsPage']    = __DIR__ . '/admin/actions/class.dcactionblogs.php';
$__autoload['dcActionsPage']         = __DIR__ . '/admin/actions/class.dcaction.php';
$__autoload['dcAdminBlogPref']       = __DIR__ . '/admin/class.dc.blog_pref.php';
$__autoload['adminUserPref']         = __DIR__ . '/admin/lib.adminuserpref.php';
$__autoload['dcAdminHelper']         = __DIR__ . '/admin/lib.helper.php';

$__autoload['dcTemplate']    = __DIR__ . '/public/class.dc.template.php';
$__autoload['context']       = __DIR__ . '/public/lib.tpl.context.php';
$__autoload['dcUrlHandlers'] = __DIR__ . '/public/lib.urlhandlers.php';

# Clearbricks extensions
html::$absolute_regs[] = '/(<param\s+name="movie"\s+value=")(.*?)(")/msu';
html::$absolute_regs[] = '/(<param\s+name="FlashVars"\s+value=".*?(?:mp3|flv)=)(.*?)(&|")/msu';
/* ------------------------------------------------------------------------------------------- */

mb_internal_encoding('UTF-8');

# Setting timezone
dt::setTZ('UTC');

# CLI_MODE, boolean constant that tell if we are in CLI mode
define('CLI_MODE', PHP_SAPI == 'cli');

# Disallow every special wrapper
if (function_exists('stream_wrapper_unregister')) {
    $special_wrappers = array_intersect(['http', 'https', 'ftp', 'ftps', 'ssh2.shell', 'ssh2.exec',
        'ssh2.tunnel', 'ssh2.sftp', 'ssh2.scp', 'ogg', 'expect', 'phar', ], stream_get_wrappers());
    foreach ($special_wrappers as $p) {
        @stream_wrapper_unregister($p);
    }
}

if (isset($_SERVER['DC_RC_PATH'])) {
    define('DC_RC_PATH', $_SERVER['DC_RC_PATH']);
} elseif (isset($_SERVER['REDIRECT_DC_RC_PATH'])) {
    define('DC_RC_PATH', $_SERVER['REDIRECT_DC_RC_PATH']);
} else {
    define('DC_RC_PATH', __DIR__ . '/config.php');
}

if (!is_file(DC_RC_PATH)) {
    if (strpos($_SERVER['SCRIPT_FILENAME'], '/admin') === false) {
        $path = 'admin/install/wizard.php';
    } else {
        $path = strpos($_SERVER['PHP_SELF'], '/install') === false ? 'install/wizard.php' : 'wizard.php';
    }
    http::redirect($path);
}

require DC_RC_PATH;

//*== DC_DEBUG ==
if (!defined('DC_DEBUG')) {
    define('DC_DEBUG', true);
}
if (DC_DEBUG) { // @phpstan-ignore-line
    ini_set('display_errors', '1');
    error_reporting(E_ALL | E_STRICT);
}
//*/

if (!defined('DC_DEBUG')) {
    define('DC_DEBUG', false);
}

# Constants
define('DC_ROOT', path::real(__DIR__ . '/..'));
define('DC_VERSION', '2.22.0');
define('DC_DIGESTS', __DIR__ . '/digests');
define('DC_L10N_ROOT', __DIR__ . '/../locales');
define('DC_L10N_UPDATE_URL', 'https://services.dotclear.net/dc2.l10n/?version=%s');
define('DC_DISTRIB_PLUGINS', 'aboutConfig,akismet,antispam,attachments,blogroll,blowupConfig,dclegacy,fairTrackbacks,importExport,maintenance,pages,pings,simpleMenu,tags,themeEditor,userPref,widgets,dcLegacyEditor,dcCKEditor,breadcrumb');
define('DC_DISTRIB_THEMES', 'berlin,blueSilence,blowupConfig,customCSS,default,ductile');
define('DC_DEFAULT_TPLSET', 'mustek');
define('DC_DEFAULT_JQUERY', '3.6.0');

if (!defined('DC_NEXT_REQUIRED_PHP')) {
    define('DC_NEXT_REQUIRED_PHP', '7.4');
}

if (!defined('DC_VENDOR_NAME')) {
    define('DC_VENDOR_NAME', 'Dotclear');
}

if (!defined('DC_XMLRPC_URL')) {
    define('DC_XMLRPC_URL', '%1$sxmlrpc/%2$s');
}

if (!defined('DC_SESSION_TTL')) {
    define('DC_SESSION_TTL', null);
}

if (!defined('DC_ADMIN_SSL')) {
    define('DC_ADMIN_SSL', false);
}

if (defined('DC_FORCE_SCHEME_443') && DC_FORCE_SCHEME_443) {
    http::$https_scheme_on_443 = true;
}
if (defined('DC_REVERSE_PROXY') && DC_REVERSE_PROXY) {
    http::$reverse_proxy = true;
}
if (!defined('DC_DBPERSIST')) {
    define('DC_DBPERSIST', false);
}

if (!defined('DC_UPDATE_URL')) {
    define('DC_UPDATE_URL', 'https://download.dotclear.org/versions.xml');
}

if (!defined('DC_UPDATE_VERSION')) {
    define('DC_UPDATE_VERSION', 'stable');
}

if (!defined('DC_NOT_UPDATE')) {
    define('DC_NOT_UPDATE', false);
}

if (!defined('DC_ALLOW_MULTI_MODULES')) {
    define('DC_ALLOW_MULTI_MODULES', false);
}

if (!defined('DC_STORE_NOT_UPDATE')) {
    define('DC_STORE_NOT_UPDATE', false);
}

if (!defined('DC_ALLOW_REPOSITORIES')) {
    define('DC_ALLOW_REPOSITORIES', true);
}

if (!defined('DC_QUERY_TIMEOUT')) {
    define('DC_QUERY_TIMEOUT', 4);
}

if (!defined('DC_CRYPT_ALGO')) {
    define('DC_CRYPT_ALGO', 'sha1'); // As in Dotclear 2.9 and previous
} else {
    // Check length of cryptographic algorithm result and exit if less than 40 characters long
    if (strlen(crypt::hmac(DC_MASTER_KEY, DC_VENDOR_NAME, DC_CRYPT_ALGO)) < 40) {
        if (!defined('DC_CONTEXT_ADMIN')) {
            __error('Server error', 'Site temporarily unavailable');
        } else {
            __error('Dotclear error', DC_CRYPT_ALGO . ' cryptographic algorithm configured is not strong enough, please change it.');
        }
        exit;
    }
}

if (!defined('DC_TPL_CACHE')) {
    define('DC_TPL_CACHE', path::real(__DIR__ . '/..') . '/cache');
}
// Check existence of cache directory
if (!is_dir(DC_TPL_CACHE)) {
    // Try to create it
    @files::makeDir(DC_TPL_CACHE);
    if (!is_dir(DC_TPL_CACHE)) {
        // Admin must create it
        if (!defined('DC_CONTEXT_ADMIN')) {
            __error('Server error', 'Site temporarily unavailable');
        } else {
            __error('Dotclear error', DC_TPL_CACHE . ' directory does not exist. Please create it.');
        }
        exit;
    }
}

if (!defined('DC_VAR')) {
    define('DC_VAR', path::real(__DIR__ . '/..') . '/var');
}
// Check existence of var directory
if (!is_dir(DC_VAR)) {
    // Try to create it
    @files::makeDir(DC_VAR);
    if (!is_dir(DC_VAR)) {
        // Admin must create it
        if (!defined('DC_CONTEXT_ADMIN')) {
            __error('Server error', 'Site temporarily unavailable');
        } else {
            __error('Dotclear error', DC_VAR . ' directory does not exist. Please create it.');
        }
        exit;
    }
}

l10n::init();

try {
    $core = new dcCore(DC_DBDRIVER, DC_DBHOST, DC_DBNAME, DC_DBUSER, DC_DBPASSWORD, DC_DBPREFIX, DC_DBPERSIST);
} catch (Exception $e) {
    init_prepend_l10n();
    if (!defined('DC_CONTEXT_ADMIN')) {
        __error(
            __('Site temporarily unavailable'),
            __('<p>We apologize for this temporary unavailability.<br />' .
                'Thank you for your understanding.</p>'),
            20
        );
    } else {
        __error(
            __('Unable to connect to database'),
            $e->getCode() == 0 ?
            sprintf(
                __('<p>This either means that the username and password information in ' .
                'your <strong>config.php</strong> file is incorrect or we can\'t contact ' .
                'the database server at "<em>%s</em>". This could mean your ' .
                'host\'s database server is down.</p> ' .
                '<ul><li>Are you sure you have the correct username and password?</li>' .
                '<li>Are you sure that you have typed the correct hostname?</li>' .
                '<li>Are you sure that the database server is running?</li></ul>' .
                '<p>If you\'re unsure what these terms mean you should probably contact ' .
                'your host. If you still need help you can always visit the ' .
                '<a href="https://forum.dotclear.net/">Dotclear Support Forums</a>.</p>') .
                (DC_DEBUG ? // @phpstan-ignore-line
                    '<p>' . __('The following error was encountered while trying to read the database:') . '</p><ul><li>' . $e->getMessage() . '</li></ul>' :
                    ''),
                (DC_DBHOST !== '' ? DC_DBHOST : 'localhost')   // @phpstan-ignore-line
            ) :
            '',
            20
        );
    }
}

# If we have some __top_behaviors, we load them
if (isset($__top_behaviors) && is_array($__top_behaviors)) {
    foreach ($__top_behaviors as $b) {
        $core->addBehavior($b[0], $b[1]);
    }
    unset($b);
}

http::trimRequest();

try {
    http::unsetGlobals();
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo $e->getMessage();
    exit;
}

$core->url->registerDefault(['dcUrlHandlers', 'home']);
$core->url->registerError(['dcUrlHandlers', 'default404']);
$core->url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', ['dcUrlHandlers', 'lang']);
$core->url->register('posts', 'posts', '^posts(/.+)?$', ['dcUrlHandlers', 'home']);
$core->url->register('post', 'post', '^post/(.+)$', ['dcUrlHandlers', 'post']);
$core->url->register('preview', 'preview', '^preview/(.+)$', ['dcUrlHandlers', 'preview']);
$core->url->register('category', 'category', '^category/(.+)$', ['dcUrlHandlers', 'category']);
$core->url->register('archive', 'archive', '^archive(/.+)?$', ['dcUrlHandlers', 'archive']);

$core->url->register('feed', 'feed', '^feed/(.+)$', ['dcUrlHandlers', 'feed']);
$core->url->register('trackback', 'trackback', '^trackback/(.+)$', ['dcUrlHandlers', 'trackback']);
$core->url->register('webmention', 'webmention', '^webmention(/.+)?$', ['dcUrlHandlers', 'webmention']);
$core->url->register('rsd', 'rsd', '^rsd$', ['dcUrlHandlers', 'rsd']);
$core->url->register('xmlrpc', 'xmlrpc', '^xmlrpc/(.+)$', ['dcUrlHandlers', 'xmlrpc']);

// Should use dcAdminURL class, but only in admin -> to be moved to public/prepend.php and admin/prepend.php ?
$core->setPostType('post', 'post.php?id=%d', $core->url->getURLFor('post', '%s'), 'Posts');

# Store upload_max_filesize in bytes
$u_max_size = files::str2bytes(ini_get('upload_max_filesize'));
$p_max_size = files::str2bytes(ini_get('post_max_size'));
if ($p_max_size < $u_max_size) {
    $u_max_size = $p_max_size;
}
define('DC_MAX_UPLOAD_SIZE', $u_max_size);
unset($u_max_size, $p_max_size);

# Register supplemental mime types
files::registerMimeTypes([
    // Audio
    'aac'  => 'audio/aac',
    'ogg'  => 'audio/ogg',
    'weba' => 'audio/webm',
    'm4a'  => 'audio/mp4',
    // Video
    'mp4'  => 'video/mp4',
    'm4p'  => 'video/mp4',
    'webm' => 'video/webm',
]);

# Shutdown
register_shutdown_function('__shutdown');

function __shutdown()
{
    global $__shutdown;
    if (is_array($__shutdown)) {
        foreach ($__shutdown as $f) {
            if (is_callable($f)) {
                call_user_func($f);
            }
        }
    }
    # Explicitly close session before DB connection
    try {
        if (session_id()) {
            session_write_close();
        }
    } catch (Exception $e) {    // @phpstan-ignore-line
    }
    $GLOBALS['core']->con->close();
}

function __error($summary, $message, $code = 0)
{
    # Error codes
    # 10 : no config file
    # 20 : database issue
    # 30 : blog is not defined
    # 40 : template files creation
    # 50 : no default theme
    # 60 : template processing error
    # 70 : blog is offline

    if (CLI_MODE) {
        trigger_error($summary, E_USER_ERROR);
        exit(1);    // @phpstan-ignore-line
    }
    if (defined('DC_ERRORFILE') && is_file(DC_ERRORFILE)) {
        include DC_ERRORFILE;
    } else {
        include __DIR__ . '/core_error.php';
    }
    exit;
}

function init_prepend_l10n()
{
    # Loading locales for detected language
    $dlang = http::getAcceptLanguages();
    foreach ($dlang as $l) {
        if ($l == 'en' || l10n::set(__DIR__ . '/../locales/' . $l . '/main') !== false) {
            l10n::lang($l);

            break;
        }
    }
}
