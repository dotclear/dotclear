<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use Dotclear\FileServer;
use Dotclear\Core\Frontend\Url;
use Dotclear\Helper\Clearbricks;
use Dotclear\Helper\Date;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Exception\ContextException;
use Dotclear\Exception\ProcessException;
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\PostTypesInterface;
use Dotclear\Interface\Core\UrlInterface;
use Dotclear\Interface\Core\TaskInterface;
use Throwable;

/**
 * @brief   Application task launcher.
 *
 * This class execute application according to an Utility and its Process.
 *
 * @since   2.28, preload events has been grouped in this class
 */
class Task implements TaskInterface
{
    /**
     * Watchdog.
     */
    private static bool $watchdog = false;

    /**
     * The context(s).
     *
     * Multiple contexts can be set at same time like:
     * INSTALL / BACKEND, or BACKEND / MODULE
     *
     * @var     array<string,bool>  The contexts in use
     */
    private array $context = [
        'BACKEND'  => false,
        'FRONTEND' => false,
        'MODULE'   => false,
        'INSTALL'  => false,
        'UPGRADE'  => false,
    ];

    /**
     * Constructor.
     *
     * @param   BehaviorInterface   $behavior       The behavior instance
     * @param   ConfigInterface     $config         The application configuration
     * @param   PostTypesInterface  $post_types     The post types handler
     * @param   UrlInterface        $url            The URL handler
     */
    public function __construct(
        protected BehaviorInterface $behavior,
        protected ConfigInterface $config,
        protected PostTypesInterface $post_types,
        protected UrlInterface $url,
    ) {
    }

    /**
     * Process app
     *
     * @param      string     $utility  The utility
     * @param      string     $process  The process
     *
     * @throws     ContextException|ProcessException
     */
    public function run(string $utility, string $process): void
    {
        // watchdog
        if (self::$watchdog) {
            throw new ContextException(__('Application can not be started twice.'));
        }
        self::$watchdog = true;

        // Set encoding
        mb_internal_encoding('UTF-8');

        // Initialize lang definition
        L10n::init();

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

        // Ensure server PATH_INFO is set
        if (!isset($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = '';
        }

        // Set called context
        $this->addContext($utility);

        // Initialize Utility
        $utility_response = $utility === '' ? false : $this->LoadUtility('Dotclear\\Core\\' . $utility . '\\Utility', false);

        // deprecated since 2.28, loads core classes (old way)
        Clearbricks::lib()->autoload([
            'dcCore'  => implode(DIRECTORY_SEPARATOR, [$this->config->dotclearRoot(),  'inc', 'core', 'class.dc.core.php']),
            'dcUtils' => implode(DIRECTORY_SEPARATOR, [$this->config->dotclearRoot(),  'inc', 'core', 'class.dc.utils.php']),
        ]);

        // Check and serve plugins and var files. (from ?pf= and ?vf= URI)
        FileServer::check($this->config);

        // Config file exists
        if (is_file($this->config->configPath())) {
            // Http setup
            if ($this->config->httpScheme443()) {
                Http::$https_scheme_on_443 = true;
            }
            if ($this->config->httpReverseProxy()) {
                Http::$reverse_proxy = true;
            }
            Http::trimRequest();

            try {
                // deprecated since 2.23, use App:: instead
                $core            = new dcCore();
                $GLOBALS['core'] = $core;
            } catch (Throwable) {
                throw new ProcessException(
                    $this->checkContext('BACKEND') ?
                    __('Unable to load deprecated core') :
                    __('<p>We apologize for this temporary unavailability.<br>Thank you for your understanding.</p>')
                );
            }

            # If we have some __top_behaviors, we load them
            if (isset($GLOBALS['__top_behaviors']) && is_array($GLOBALS['__top_behaviors'])) {
                foreach ($GLOBALS['__top_behaviors'] as $b) {
                    $this->behavior->addBehavior($b[0], $b[1]);
                }
                unset($GLOBALS['__top_behaviors'], $b);
            }

            // Register default URLs
            $this->url->registerDefault(Url::home(...));

            $this->url->registerError(Url::default404(...));

            $this->url->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', Url::lang(...));
            $this->url->register('posts', 'posts', '^posts(/.+)?$', Url::home(...));
            $this->url->register('post', 'post', '^post/(.+)$', Url::post(...));
            $this->url->register('preview', 'preview', '^preview/(.+)$', Url::preview(...));
            $this->url->register('category', 'category', '^category/(.+)$', Url::category(...));
            $this->url->register('archive', 'archive', '^archive(/.+)?$', Url::archive(...));
            $this->url->register('try', 'try', '^try/(.+)$', Url::try(...));

            $this->url->register('feed', 'feed', '^feed/(.+)$', Url::feed(...));
            $this->url->register('trackback', 'trackback', '^trackback/(.+)$', Url::trackback(...));
            $this->url->register('webmention', 'webmention', '^webmention(/.+)?$', Url::webmention(...));
            $this->url->register('xmlrpc', 'xmlrpc', '^xmlrpc/(.+)$', Url::xmlrpc(...));

            $this->url->register('wp-admin', 'wp-admin', '^wp-admin(?:/(.+))?$', Url::wpfaker(...));
            $this->url->register('wp-login', 'wp-login', '^wp-login.php(?:/(.+))?$', Url::wpfaker(...));

            // Set post type for frontend instance with harcoded backend URL (but should not be required in backend before Utility instanciated)
            $this->post_types->set(new PostType('post', 'index.php?process=Post&id=%d', $this->url->getURLFor('post', '%s'), 'Posts'));

            // Register local shutdown handler
            register_shutdown_function(function (): void {
                if (isset($GLOBALS['__shutdown']) && is_array($GLOBALS['__shutdown'])) {
                    foreach ($GLOBALS['__shutdown'] as $f) {
                        if (is_callable($f)) {
                            call_user_func($f);
                        }
                    }
                }
            });
        } elseif (!str_contains((string) $_SERVER['SCRIPT_FILENAME'], '\admin') && !str_contains((string) $_SERVER['SCRIPT_FILENAME'], '/admin')) {
            // Config file does not exist, go to install page
            Http::redirect(implode(DIRECTORY_SEPARATOR, ['admin', 'install', 'index.php']));
        } elseif (!str_contains((string) $_SERVER['PHP_SELF'], '\install') && !str_contains((string) $_SERVER['PHP_SELF'], '/install')) {
            // Config file does not exist, go to install page
            Http::redirect(implode(DIRECTORY_SEPARATOR, ['install', 'index.php']));
        }

        // Process app utility. If any.
        if ($utility_response && $this->loadUtility('Dotclear\\Core\\' . $utility . '\\Utility', true)) {
            // Try to load utility process, the _REQUEST process as priority on method process.
            if (!empty($_REQUEST['process']) && preg_match('/^[A-Za-z]+$/', (string) $_REQUEST['process'])) {
                $process = $_REQUEST['process'];
            }
            if (!empty($process)) {
                $this->loadProcess('Dotclear\\Process\\' . $utility . '\\' . $process);
            }
        }
    }

    public function checkContext(string $context): bool
    {
        return $this->context[strtoupper($context)] ?? false;
    }

    public function addContext(string $context): void
    {
        $context = strtoupper($context);

        if (array_key_exists($context, $this->context)) {
            $this->context[$context] = true;

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

    public function loadProcess(string $process): void
    {
        if (!is_subclass_of($process, Process::class, true)) {
            throw new ProcessException(sprintf(__('Unable to find class %s'), $process));
        }

        // Call process in 3 steps: init, process, render.
        if ($process::init() !== false && $process::process() !== false) {
            $process::render();
        }
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
    private function loadUtility(string $utility, bool $next = false): bool
    {
        if (!is_subclass_of($utility, Process::class, true)) {
            throw new ProcessException(sprintf(__('Unable to initialize class %s'), $utility));
        }

        return $next ? $utility::process() : $utility::init();
    }
}
