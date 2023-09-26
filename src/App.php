<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear {
    use Autoloader;
    use dcCore;
    use dcUrlHandlers;
    use dcUtils;
    use Dotclear\Core\Process;
    use Dotclear\Helper\Clearbricks;
    use Dotclear\Helper\Crypt;
    use Dotclear\Helper\Date;
    use Dotclear\Helper\File\Files;
    use Dotclear\Helper\L10n;
    use Dotclear\Helper\Network\Http;
    use Exception;

    /**
     * Application.
     */
    final class App
    {
        /** @var    string  Dotclear default release config file name */
        public const RELEASE_FILE = 'release.json';

        /** @var    array<string,mixed>     Dotclear default release config */
        private static array $release = [];

        /** @var    bool    Requirements loaded */
        private static bool $initialized = false;

        /**
         * App boostrap.
         *
         * Load application with their utility and process, if any.
         *
         * Use:
         * require_once path/to/App.php
         * Dotclear\App::bootstrap(Utility, Process);
         *
         * utility and process MUST extend Dotclear\Core\Process.
         *
         * Supported utilities are Backend, Frontend, Install, Upgrade (CLI)
         *
         * @param   string  $utility    The optionnal app utility (Backend or Frontend)
         * @param   string  $process    The optionnal app utility default process
         */
        public static function bootstrap(string $utility = '', string $process = ''): void
        {
            // Can not run twice the app
            self::initialized(true);

            // Load app prerequisites
            self::preload();

            // Init app utility. If any.
            $ret = empty($utility) ? false : self::utility('Dotclear\\Core\\' . $utility . '\\Utility', false);

            // Load app requirements
            self::load();

            // Process app utility. If any.
            if ($ret && true === self::utility('Dotclear\\Core\\' . $utility . '\\Utility', true)) {
                // Try to load utility process, the _REQUEST process as priority on method process.
                if (!empty($_REQUEST['process']) && preg_match('/^[A-Za-z]+$/', ($_REQUEST['process'])) {
                    $process = $_REQUEST['process'];
                }
                if (!empty($process)) {
                    self::process('Dotclear\\Process\\' . $utility . '\\' . $process);
                }
            }
        }

        /**
         * Processes the given process.
         *
         * A process MUST extends Dotclear\Core\Process class.
         *
         * @param      string  $process  The process
         */
        public static function process(string $process): void
        {
            // App::preload() not done
            self::initialized();

            try {
                if (!is_subclass_of($process, Process::class, true)) {
                    throw new Exception(sprintf(__('Unable to find class %s'), $process));
                }

                // Call process in 3 steps: init, process, render.
                if ($process::init() !== false && $process::process() !== false) {
                    $process::render();
                }
            } catch (Exception $e) {
                Fault::throw(__('Process failed'), $e);
            }
        }

        /**
         * Read Dotclear release config.
         *
         * This method always returns string,
         * casting int, bool, array, to string.
         *
         * @param   string  $key The release key
         *
         * @return  string  The release value
         */
        public static function release(string $key): string
        {
            // App::preload() not done
            self::initialized();

            try {
                // Load once release file
                if (empty(self::$release)) {
                    $file = DC_ROOT . DIRECTORY_SEPARATOR . self::RELEASE_FILE;
                    if (!is_file($file) || !is_readable($file)) {
                        throw new Exception(__('Dotclear release file was not found'), Fault::SETUP_ISSUE);
                    }

                    $release = json_decode((string) file_get_contents($file), true);
                    if (!is_array($release)) {
                        throw new Exception(__('Dotclear release file is not readable'), Fault::SETUP_ISSUE);
                    }

                    self::$release = $release;
                }

                // Release key not found
                if (!array_key_exists($key, self::$release)) {
                    throw new Exception(sprintf(__('Dotclear release key %s was not found'), $key), Fault::SETUP_ISSUE);
                }

                // Return casted release key value
                return is_array(self::$release[$key]) ? implode(',', self::$release[$key]) : (string) self::$release[$key];
            } catch(Exception $e) {
                Fault::throw(__('Not found'), $e);
            }
        }

        /**
         * Preload requirements (namespace, class, constant).
         *
         * Called from self::bootstrap() and self::load()
         */
        private static function preload(): void
        {
            // Load once
            if (self::$initialized) {
                return;
            }

            // Start tick
            define('DC_START_TIME', microtime(true));

            // Dotclear root path
            define('DC_ROOT', dirname(__DIR__));

            // Load Autoloader file
            require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'Autoloader.php']);

            // Add root folder for namespaced and autoloaded classes
            Autoloader::me()->addNamespace('Dotclear', __DIR__);

            // Load core classes (old way) This will moved to namespace from Autoloader in the near futur
            $inc = fn (string $folder, string $file) => implode(DIRECTORY_SEPARATOR, [DC_ROOT,  'inc', $folder, $file]);
            Clearbricks::lib()->autoload([
                // Traits
                'dcTraitDynamicProperties' => $inc('core', 'trait.dc.dynprop.php'),

                // Core
                'dcCore' => $inc('core', 'class.dc.core.php'),

                'dcAuth'         => $inc('core', 'class.dc.auth.php'),
                'dcBlog'         => $inc('core', 'class.dc.blog.php'),
                'dcCategories'   => $inc('core', 'class.dc.categories.php'),
                'dcError'        => $inc('core', 'class.dc.error.php'),
                'dcMeta'         => $inc('core', 'class.dc.meta.php'),
                'dcMedia'        => $inc('core', 'class.dc.media.php'),
                'dcPostMedia'    => $inc('core', 'class.dc.postmedia.php'),
                'dcModuleDefine' => $inc('core', 'class.dc.module.define.php'),
                'dcModules'      => $inc('core', 'class.dc.modules.php'),
                'dcPlugins'      => $inc('core', 'class.dc.plugins.php'),
                'dcThemes'       => $inc('core', 'class.dc.themes.php'),
                'dcRestServer'   => $inc('core', 'class.dc.rest.php'),
                'dcNamespace'    => $inc('core', 'class.dc.namespace.php'),
                'dcNotices'      => $inc('core', 'class.dc.notices.php'),
                'dcSettings'     => $inc('core', 'class.dc.settings.php'),
                'dcTrackback'    => $inc('core', 'class.dc.trackback.php'),
                'dcUpdate'       => $inc('core', 'class.dc.update.php'),
                'dcUtils'        => $inc('core', 'class.dc.utils.php'),
                'dcXmlRpc'       => $inc('core', 'class.dc.xmlrpc.php'),
                'dcDeprecated'   => $inc('core', 'class.dc.deprecated.php'),
                'dcLog'          => $inc('core', 'class.dc.log.php'),
                'rsExtLog'       => $inc('core', 'class.dc.log.php'),
                'dcWorkspace'    => $inc('core', 'class.dc.workspace.php'),
                'dcPrefs'        => $inc('core', 'class.dc.prefs.php'),
                'dcStore'        => $inc('core', 'class.dc.store.php'),
                'dcStoreReader'  => $inc('core', 'class.dc.store.reader.php'),
                'dcStoreParser'  => $inc('core', 'class.dc.store.parser.php'),
                'rsExtPost'      => $inc('core', 'class.dc.rs.extensions.php'),
                'rsExtComment'   => $inc('core', 'class.dc.rs.extensions.php'),
                'rsExtDates'     => $inc('core', 'class.dc.rs.extensions.php'),
                'rsExtUser'      => $inc('core', 'class.dc.rs.extensions.php'),
                'rsExtBlog'      => $inc('core', 'class.dc.rs.extensions.php'),

                // Public
                'dcTemplate'         => $inc('public', 'class.dc.template.php'),
                'context'            => $inc('public', 'lib.tpl.context.php'),
                'dcUrlHandlers'      => $inc('public', 'lib.urlhandlers.php'),
                'rsExtendPublic'     => $inc('public', 'rs.extension.php'),
                'rsExtPostPublic'    => $inc('public', 'rs.extension.php'),
                'rsExtCommentPublic' => $inc('public', 'rs.extension.php'),
            ]);

            // CLI_MODE, boolean constant that tell if we are in CLI mode
            define('CLI_MODE', PHP_SAPI == 'cli');

            mb_internal_encoding('UTF-8');

            // We may need l10n __() function
            L10n::bootstrap();

            // We set default timezone to avoid warning
            Date::setTZ('UTC');

            // Say app is initialized (before querying self::release)
            self::$initialized = true;

            // Release constants
            define('DC_VERSION', self::release('release_version'));
            define('DC_NAME', self::release('release_name'));
        }

        /**
         * load other requirements.
         */
        private static function load(): void
        {
            // Preload requirements
            self::preload();

            // Disallow every special wrapper
            if (function_exists('\\stream_wrapper_unregister')) {
                $special_wrappers = array_intersect([
                    'http',
                    'https',
                    'ftp',
                    'ftps',
                    'ssh2.shell',
                    'ssh2.exec',
                    'ssh2.tunnel',
                    'ssh2.sftp',
                    'ssh2.scp',
                    'ogg',
                    'expect',
                    // 'phar',   // Used by PharData to manage Zip/Tar archive
                ], stream_get_wrappers());
                foreach ($special_wrappers as $p) {
                    @stream_wrapper_unregister($p);
                }
                unset($special_wrappers, $p);
            }

            if (!isset($_SERVER['PATH_INFO'])) {
                $_SERVER['PATH_INFO'] = '';
            }

            if (isset($_SERVER['DC_RC_PATH'])) {
                define('DC_RC_PATH', $_SERVER['DC_RC_PATH']);
            } elseif (isset($_SERVER['REDIRECT_DC_RC_PATH'])) {
                define('DC_RC_PATH', $_SERVER['REDIRECT_DC_RC_PATH']);
            } else {
                define('DC_RC_PATH', implode(DIRECTORY_SEPARATOR, [DC_ROOT, 'inc', 'config.php']));
            }

            // no config file and not in install process
            if (!is_file(DC_RC_PATH)) {
                // do not process install on CLI mode
                if (CLI_MODE) {
                    new Fault('Dotclear is not installed or failed to load config file.', '', Fault::CONFIG_ISSUE);
                }
                if ((strpos($_SERVER['SCRIPT_FILENAME'], '\admin') || strpos($_SERVER['SCRIPT_FILENAME'], '/admin')) === false) {
                    Http::redirect(implode(DIRECTORY_SEPARATOR, ['admin', 'install', 'index.php']));
                } elseif ((strpos($_SERVER['PHP_SELF'], '\install') || strpos($_SERVER['PHP_SELF'], '/install')) === false) {
                    Http::redirect(implode(DIRECTORY_SEPARATOR, ['install', 'index.php']));
                }
                // stop App init here on install wizard
                return;
            }

            // path::real() may be used in inc/config.php
            if (!class_exists('path')) {
                class_alias('Dotclear\Helper\File\Path', 'path');
            }

            require DC_RC_PATH;

            //*== DC_DEBUG ==
            if (!defined('DC_DEBUG')) {
                define('DC_DEBUG', true);
            }
            if (DC_DEBUG) {
                ini_set('display_errors', '1');
                error_reporting(E_ALL);
            }
            //*/

            if (!defined('DC_DEBUG')) {
                define('DC_DEBUG', false);
            }

            // Other constants
            define('DC_DIGESTS', dcUtils::path([DC_ROOT, 'inc', 'digests']));
            define('DC_L10N_ROOT', dcUtils::path([DC_ROOT, 'locales']));
            define('DC_L10N_UPDATE_URL', self::release('l10n_update_url'));

            // Update Makefile if the following list is modified
            define('DC_DISTRIB_PLUGINS', self::release('distributed_plugins'));
            // Update Makefile if the following list is modified
            define('DC_DISTRIB_THEMES', self::release('distributed_themes'));

            define('DC_DEFAULT_THEME', self::release('default_theme'));
            define('DC_DEFAULT_TPLSET', self::release('default_tplset'));
            define('DC_DEFAULT_JQUERY', self::release('default_jquery'));

            if (!defined('DC_NEXT_REQUIRED_PHP')) {
                define('DC_NEXT_REQUIRED_PHP', self::release('next_php'));
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
                Http::$https_scheme_on_443 = true;
            }
            if (defined('DC_REVERSE_PROXY') && DC_REVERSE_PROXY) {
                Http::$reverse_proxy = true;
            }
            if (!defined('DC_DBPERSIST')) {
                define('DC_DBPERSIST', false);
            }

            if (!defined('DC_UPDATE_URL')) {
                define('DC_UPDATE_URL', self::release('release_update_url'));
            }

            if (!defined('DC_UPDATE_VERSION')) {
                define('DC_UPDATE_VERSION', self::release('release_update_canal'));
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

            if (!defined('DC_REST_SERVICES')) {
                define('DC_REST_SERVICES', true);
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
                if (strlen(Crypt::hmac(DC_MASTER_KEY, DC_VENDOR_NAME, DC_CRYPT_ALGO)) < 40) {
                    if (!defined('DC_CONTEXT_ADMIN')) {
                        new Fault('Server error', 'Site temporarily unavailable', Fault::SETUP_ISSUE);
                    } else {
                        new Fault('Dotclear error', DC_CRYPT_ALGO . ' cryptographic algorithm configured is not strong enough, please change it.', Fault::SETUP_ISSUE);
                    }
                    exit;
                }
            }

            if (!defined('DC_TPL_CACHE')) {
                define('DC_TPL_CACHE', dcUtils::path([DC_ROOT, 'cache']));
            }
            // Check existence of cache directory
            if (!is_dir(DC_TPL_CACHE)) {
                // Try to create it
                @Files::makeDir(DC_TPL_CACHE);
                if (!is_dir(DC_TPL_CACHE)) {
                    // Admin must create it
                    if (!defined('DC_CONTEXT_ADMIN')) {
                        new Fault('Server error', 'Site temporarily unavailable', Fault::SETUP_ISSUE);
                    } else {
                        new Fault('Dotclear error', DC_TPL_CACHE . ' directory does not exist. Please create it.', Fault::SETUP_ISSUE);
                    }
                    exit;
                }
            }

            if (!defined('DC_VAR')) {
                define('DC_VAR', dcUtils::path([DC_ROOT, 'var']));
            }
            // Check existence of var directory
            if (!is_dir(DC_VAR)) {
                // Try to create it
                @Files::makeDir(DC_VAR);
                if (!is_dir(DC_VAR)) {
                    // Admin must create it
                    if (!defined('DC_CONTEXT_ADMIN')) {
                        new Fault('Server error', 'Site temporarily unavailable', Fault::SETUP_ISSUE);
                    } else {
                        new Fault('Dotclear error', DC_VAR . ' directory does not exist. Please create it.', Fault::SETUP_ISSUE);
                    }
                    exit;
                }
            }

            // Check and serve plugins and var files. (from ?pf= and ?vf= URI)
            FileServer::check();

            // REST server watchdog file (used to enable/disable REST services during last phase of Dotclear upgrade)
            if (!defined('DC_UPGRADE')) {
                define('DC_UPGRADE', dcUtils::path([DC_ROOT, 'inc', 'upgrade']));
            }

            L10n::init();

            try {
                /**
                 * Core instance
                 *
                 * @var        dcCore $core
                 *
                 * @deprecated since 2.23, use dcCore::app() instead
                 */
                $core = new dcCore(DC_DBDRIVER, DC_DBHOST, DC_DBNAME, DC_DBUSER, DC_DBPASSWORD, DC_DBPREFIX, DC_DBPERSIST);
                $GLOBALS['core'] = $core;
            } catch (Exception $e) {
                // Loading locales for detected language
                $detected_languages = Http::getAcceptLanguages();
                foreach ($detected_languages as $language) {
                    if ($language === 'en' || L10n::set(implode(DIRECTORY_SEPARATOR, [DC_L10N_ROOT, $language, 'main'])) !== false) {
                        L10n::lang($language);

                        // We stop at first accepted language
                        break;
                    }
                }
                unset($detected_languages);

                if (!defined('DC_CONTEXT_ADMIN')) {
                    new Fault(
                        __('Site temporarily unavailable'),
                        __('<p>We apologize for this temporary unavailability.<br />' .
                            'Thank you for your understanding.</p>'),
                        Fault::DATABASE_ISSUE
                    );
                } else {
                    new Fault(
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
                            (DC_DEBUG ?
                                '<p>' . __('The following error was encountered while trying to read the database:') . '</p><ul><li>' . $e->getMessage() . '</li></ul>' :
                                ''),
                            (DC_DBHOST !== '' ? DC_DBHOST : 'localhost')
                        ) :
                        '',
                        Fault::DATABASE_ISSUE
                    );
                }
            }

            # If we have some __top_behaviors, we load them
            if (isset($GLOBALS['__top_behaviors']) && is_array($GLOBALS['__top_behaviors'])) {
                foreach ($GLOBALS['__top_behaviors'] as $b) {
                    dcCore::app()->addBehavior($b[0], $b[1]);
                }
                unset($GLOBALS['__top_behaviors'], $b);
            }

            Http::trimRequest();

            dcCore::app()->url->registerDefault([dcUrlHandlers::class, 'home']);

            dcCore::app()->url->registerError([dcUrlHandlers::class, 'default404']);

            dcCore::app()->url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', [dcUrlHandlers::class, 'lang']);
            dcCore::app()->url->register('posts', 'posts', '^posts(/.+)?$', [dcUrlHandlers::class, 'home']);
            dcCore::app()->url->register('post', 'post', '^post/(.+)$', [dcUrlHandlers::class, 'post']);
            dcCore::app()->url->register('preview', 'preview', '^preview/(.+)$', [dcUrlHandlers::class, 'preview']);
            dcCore::app()->url->register('category', 'category', '^category/(.+)$', [dcUrlHandlers::class, 'category']);
            dcCore::app()->url->register('archive', 'archive', '^archive(/.+)?$', [dcUrlHandlers::class, 'archive']);
            dcCore::app()->url->register('try', 'try', '^try/(.+)$', [dcUrlHandlers::class, 'try']);

            dcCore::app()->url->register('feed', 'feed', '^feed/(.+)$', [dcUrlHandlers::class, 'feed']);
            dcCore::app()->url->register('trackback', 'trackback', '^trackback/(.+)$', [dcUrlHandlers::class, 'trackback']);
            dcCore::app()->url->register('webmention', 'webmention', '^webmention(/.+)?$', [dcUrlHandlers::class, 'webmention']);
            dcCore::app()->url->register('xmlrpc', 'xmlrpc', '^xmlrpc/(.+)$', [dcUrlHandlers::class, 'xmlrpc']);

            dcCore::app()->url->register('wp-admin', 'wp-admin', '^wp-admin(?:/(.+))?$', [dcUrlHandlers::class, 'wpfaker']);
            dcCore::app()->url->register('wp-login', 'wp-login', '^wp-login.php(?:/(.+))?$', [dcUrlHandlers::class, 'wpfaker']);

            // set post type for frontend instance with harcoded backend URL (but should not be required in backend before Utility instanciated)
            dcCore::app()->setPostType('post', 'index.php?process=Post&id=%d', dcCore::app()->url->getURLFor('post', '%s'), 'Posts');

            # Store upload_max_filesize in bytes
            $u_max_size = Files::str2bytes((string) ini_get('upload_max_filesize'));
            $p_max_size = Files::str2bytes((string) ini_get('post_max_size'));
            if ($p_max_size < $u_max_size) {
                $u_max_size = $p_max_size;
            }
            define('DC_MAX_UPLOAD_SIZE', $u_max_size);
            unset($u_max_size, $p_max_size);

            /*
             * Register local shutdown handler
             */
            register_shutdown_function(function () {
                if (isset($GLOBALS['__shutdown']) && is_array($GLOBALS['__shutdown'])) {
                    foreach ($GLOBALS['__shutdown'] as $f) {
                        if (is_callable($f)) {
                            call_user_func($f);
                        }
                    }
                }

                try {
                    if (session_id()) {
                        // Explicitly close session before DB connection
                        session_write_close();
                    }
                    dcCore::app()->con->close();
                } catch (Exception $e) {    // @phpstan-ignore-line
                    // Ignore exceptions
                }
            });
        }

        /**
         * Instanciate the given utility.
         *
         * An utility MUST extends Dotclear\Core\Process class.
         *
         * @param      string  $utility  The utility
         * @param      bool    $next     Go to process step
         *
         * @return     bool    Result of $utility::init() or $utility::process() if exist
         */
        private static function utility(string $utility, bool $next = false): bool
        {
            try {
                if (!is_subclass_of($utility, Process::class, true)) {
                    throw new Exception(sprintf(__('Unable to find or initialize class %s'), $utility));
                }

                return $next ? $utility::process() : $utility::init();
            } catch(Exception $e) {
                Fault::throw(__('Process failed'), $e);
            }
        }

        /**
         * Check if app is initialized.
         *
         * @param   bool    $is     true to exit if it is initialized
         *
         * @return  void
         */
        private static function initialized(bool $is = false): void
        {
            if ($is === self::$initialized) {
                // autoloader may not be loaded
                require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'Fault.php']);
                new Fault($is ? 'Application already in use.' : 'No application running.', '', Fault::SETUP_ISSUE);
                exit;
            }
        }

        /**
         * Call Dotclear autoloader.
         *
         * @return Autoloader $autoload The autoload instance
         *
         * @deprecated Since 2.27 Use Autoloader::me() instead
         */
        public static function autoload(): Autoloader
        {
            return Autoloader::me();
        }
    }
}

namespace {
    use Dotclear\Fault;

    /**
     * @deprecated since 2.27 Use new Dotclear\Fault();
     *
     * @param      string  $summary  The summary
     * @param      string  $message  The message
     * @param      int     $code     The code
     */
    function __error(string $summary, string $message, int $code = 0): void
    {
        new Fault($summary, $message, $code);
    }
}
