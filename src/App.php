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
    use Dotclear\Core\Backend\Utility as Backend;
    use Dotclear\Core\Container;
    use Dotclear\Core\Frontend\Url;
    use Dotclear\Core\Frontend\Utility as Frontend;
    use Dotclear\Core\PostType;
    use Dotclear\Core\Process;
    use Dotclear\Helper\Clearbricks;
    use Dotclear\Helper\Date;
    use Dotclear\Helper\L10n;
    use Dotclear\Helper\Network\Http;
    use Error;
    use Exception;

    // Load Autoloader file
    require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'Autoloader.php']);

    // Add root folder for namespaced and autoloaded classes
    Autoloader::me()->addNamespace('Dotclear', __DIR__);

    /**
     * Application.
     *
     * Note this class includes all core container methods.
     * @see Container
     */
    final class App extends Container
    {
        /**
         * Requirements are loaded.
         *
         * @var     bool    $initialized
         */
        private static bool $initialized = false;

        /**
         * Backend Utility instance.
         *
         * @var    Backend  $backend
         */
        private static Backend $backend;

        /**
         * Frontend Utility instance
         *
         * @var    Frontend  $frontend
         */
        private static Frontend $frontend;

        /**
         * The current lang
         *
         * @var     string     $lang
         */
        private static string $lang = 'en';

        /**
         * The context(s).
         *
         * Multiple contexts can be set at same time like:
         * INSTALL / BACKEND, or BACKEND / MODULE
         *
         * @var     array<string,bool>  The contexts in use
         */
        private static array $context = [
            'BACKEND'  => false,
            'FRONTEND' => false,
            'MODULE'   => false,
            'INSTALL'  => false,
            'UPGRADE'  => false,
        ];

        /**
         * App boostrap.
         *
         * Load application with their utility and process, if any.
         *
         * Usage:
         * @code{php}
         * require_once path/to/App.php
         * Dotclear\App::bootstrap(Utility, Process);
         * @endcode
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
            self::setContext($utility);
            $ret = empty($utility) ? false : self::utility('Dotclear\\Core\\' . $utility . '\\Utility', false);

            // Load app requirements
            self::load();

            // Process app utility. If any.
            if ($ret && true === self::utility('Dotclear\\Core\\' . $utility . '\\Utility', true)) {
                // Try to load utility process, the _REQUEST process as priority on method process.
                if (!empty($_REQUEST['process']) && is_string($_REQUEST['process'])) {
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
         * @param   string  $process    The process
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

            return App::config()->release($key);
        }

        /**
         * Set a context.
         *
         * Method is not case sensitive.
         *
         * Context can be one of:
         * * BACKEND
         * * FRONTEND
         * * INSTALL
         * * MODULE
         * * UPGRADE
         *
         * @param   string  $context    The context to set
         */
        public static function setContext(string $context): void
        {
            $context = strtoupper($context);

            if (array_key_exists($context, self::$context)) {
                self::$context[$context] = true;

                // Constant compatibility
                $constant = 'DC_CONTEXT_' . match ($context) {
                    'BACKEND'  => 'ADMIN',
                    'FRONTEND' => 'PUBLIC',
                    default    => $context
                };
                if (!defined($constant)) {
                    define($constant, true);
                }
            }
        }

        /**
         * Check if a context is set.
         *
         * Method is not case sensitive.
         *
         * @param   string  $context    The cotenxt to check
         *
         * @return  bool    True if context is set
         */
        public static function context(string $context): bool
        {
            return self::$context[strtoupper($context)] ?? false;
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

            mb_internal_encoding('UTF-8');

            // We may need l10n __() function
            L10n::bootstrap();

            // We set default timezone to avoid warning
            Date::setTZ('UTC');

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

            // Say app is initialized (before querying self::release)
            self::$initialized = true;
        }

        /**
         * load other requirements.
         */
        private static function load(): void
        {
            // Preload requirements
            self::preload();

            L10n::init();

            // path::real() may be used in inc/config.php
            if (!class_exists('path')) {
                class_alias('Dotclear\Helper\File\Path', 'path');
            }

            // Load dotclear config
            try {
                $config = new Config(dirname(__DIR__));
            } catch (Exception|Error $e) {
                if (!self::context('BACKEND')) {
                    new Fault('Server error', 'Site temporarily unavailable', Fault::SETUP_ISSUE);
                } else {
                    new Fault('Dotclear error', $e->getMessage(), Fault::SETUP_ISSUE);
                }
                exit;
            }

            // deprecated since 2.28, loads core classes (old way)
            Clearbricks::lib()->autoload([
                'dcCore'  => implode(DIRECTORY_SEPARATOR, [$config->dotclearRoot(),  'inc', 'core', 'class.dc.core.php']),
                'dcUtils' => implode(DIRECTORY_SEPARATOR, [$config->dotclearRoot(),  'inc', 'core', 'class.dc.utils.php']),
            ]);

            // Check and serve plugins and var files. (from ?pf= and ?vf= URI)
            FileServer::check($config);

            try {
                // Instanciate once App with core factory
                new App(
                    config: $config,
                    class: Factories::getFactory('core')
                );
            } catch (Exception $e) {
                if (!self::context('BACKEND')) {
                    new Fault('Server error', 'Site temporarily unavailable', Fault::SETUP_ISSUE);
                } else {
                    new Fault('Dotclear error', $e->getMessage(), Fault::SETUP_ISSUE);
                }
                exit;
            }

            // no config file and not in install process
            if (!is_file($config->configPath())) {
                if (!str_contains($_SERVER['SCRIPT_FILENAME'], '\admin') && !str_contains($_SERVER['SCRIPT_FILENAME'], '/admin')) {
                    Http::redirect(implode(DIRECTORY_SEPARATOR, ['admin', 'install', 'index.php']));
                } elseif (!str_contains($_SERVER['PHP_SELF'], '\install') && !str_contains($_SERVER['PHP_SELF'], '/install')) {
                    Http::redirect(implode(DIRECTORY_SEPARATOR, ['install', 'index.php']));
                }

                return;
            }

            if ($config->httpScheme443()) {
                Http::$https_scheme_on_443 = true;
            }

            if ($config->httpReverseProxy()) {
                Http::$reverse_proxy = true;
            }

            // try connection
            try {
                App::con();
                // deprecated since 2.23, use App:: instead
                $core            = new dcCore();
                $GLOBALS['core'] = $core;
            } catch (Exception $e) {
                // Loading locales for detected language
                $detected_languages = Http::getAcceptLanguages();
                foreach ($detected_languages as $language) {
                    if ($language === 'en' || L10n::set(implode(DIRECTORY_SEPARATOR, [$config->l10nRoot(), $language, 'main'])) !== false) {
                        L10n::lang($language);

                        // We stop at first accepted language
                        break;
                    }
                }
                unset($detected_languages);

                if (!self::context('BACKEND')) {
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
                            ($config->debugMode() ?
                                '<p>' . __('The following error was encountered while trying to read the database:') . '</p><ul><li>' . $e->getMessage() . '</li></ul>' :
                                ''),
                            ($config->dbHost() !== '' ? $config->dbHost() : 'localhost')
                        ) :
                        '',
                        Fault::DATABASE_ISSUE
                    );
                }
            }

            # If we have some __top_behaviors, we load them
            if (isset($GLOBALS['__top_behaviors']) && is_array($GLOBALS['__top_behaviors'])) {
                foreach ($GLOBALS['__top_behaviors'] as $b) {
                    App::behavior()->addBehavior($b[0], $b[1]);
                }
                unset($GLOBALS['__top_behaviors'], $b);
            }

            Http::trimRequest();

            App::url()->registerDefault(Url::home(...));

            App::url()->registerError(Url::default404(...));

            App::url()->register('lang', '', '^(a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', Url::lang(...));
            App::url()->register('posts', 'posts', '^posts(/.+)?$', Url::home(...));
            App::url()->register('post', 'post', '^post/(.+)$', Url::post(...));
            App::url()->register('preview', 'preview', '^preview/(.+)$', Url::preview(...));
            App::url()->register('category', 'category', '^category/(.+)$', Url::category(...));
            App::url()->register('archive', 'archive', '^archive(/.+)?$', Url::archive(...));
            App::url()->register('try', 'try', '^try/(.+)$', Url::try(...));

            App::url()->register('feed', 'feed', '^feed/(.+)$', Url::feed(...));
            App::url()->register('trackback', 'trackback', '^trackback/(.+)$', Url::trackback(...));
            App::url()->register('webmention', 'webmention', '^webmention(/.+)?$', Url::webmention(...));
            App::url()->register('xmlrpc', 'xmlrpc', '^xmlrpc/(.+)$', Url::xmlrpc(...));

            App::url()->register('wp-admin', 'wp-admin', '^wp-admin(?:/(.+))?$', Url::wpfaker(...));
            App::url()->register('wp-login', 'wp-login', '^wp-login.php(?:/(.+))?$', Url::wpfaker(...));

            // set post type for frontend instance with harcoded backend URL (but should not be required in backend before Utility instanciated)
            App::postTypes()->set(new PostType('post', 'index.php?process=Post&id=%d', App::url()->getURLFor('post', '%s'), 'Posts'));

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
                    App::con()->close();
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
         * @param   string  $utility    The utility
         * @param   bool    $next       Go to process step
         *
         * @return  bool    Result of $utility::init() or $utility::process() if exist
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
         * @deprecated  Since 2.27, use Autoloader::me() instead
         *
         * @return  Autoloader $autoload The autoload instance
         */
        public static function autoload(): Autoloader
        {
            return Autoloader::me();
        }

        /**
         * Get backend Utility.
         *
         * @return  Backend
         */
        public static function backend(): Backend
        {
            // Instanciate Backend instance
            if (!isset(self::$backend)) {
                self::$backend = new Backend();

                // deprecated since 2.28, use App::backend() instead
                dcCore::app()->admin = self::$backend;
            }

            return self::$backend;
        }

        /**
         * Get frontend Utility.
         *
         * @return  Frontend
         */
        public static function frontend(): Frontend
        {
            // Instanciate Backend instance
            if (!isset(self::$frontend)) {
                self::$frontend = new Frontend();

                // deprecated since 2.28, use App::frontend() instead
                dcCore::app()->public = self::$frontend;
            }

            return self::$frontend;
        }

        /**
         * Get current lang.
         *
         * @return  string
         */
        public static function lang(): string
        {
            return self::$lang;
        }

        /**
         * Set the lang to use.
         *
         * @param   string  $id     The lang ID
         */
        public static function setLang($id): void
        {
            self::$lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $id) ? $id : 'en';

            // deprecated since 2.28, use App::setLoang() instead
            dcCore::app()->lang = self::$lang;
        }
    }
}

namespace {
    use Dotclear\Fault;

    /**
     * @deprecated  since 2.27, use new Dotclear\Fault();
     *
     * @param   string  $summary    The summary
     * @param   string  $message    The message
     * @param   int     $code   The code
     */
    function __error(string $summary, string $message, int $code = 0): void
    {
        new Fault($summary, $message, $code);
    }
}
