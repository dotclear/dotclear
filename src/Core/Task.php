<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use Dotclear\Helper\Clearbricks;
use Dotclear\Helper\Date;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Exception\ContextException;
use Dotclear\Exception\ProcessException;
use Dotclear\Interface\Core\TaskInterface;
use Throwable;

/**
 * @brief   Application task launcher.
 *
 * @since   2.28, preload events has been grouped in this class
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Task implements TaskInterface
{
    /**
     * Watchdog.
     */
    private static bool $watchdog = false;

    /**
     * The contexts in use.
     *
     * Multiple contexts can be set at same time like:
     * INSTALL / BACKEND, or BACKEND / MODULE
     *
     * @var     array<string, bool>     $context
     */
    private array $context = [];

    /**
     * The running utility.
     */
    private string $utility = '';

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core,
    ) {
    }

    public function run(string $utility, string $process): void
    {
        // watchdog
        if (self::$watchdog) {
            throw new ContextException(__('Application can not be started twice.'));
        }
        self::$watchdog = true;

        // Set encoding
        @ini_set('mbstring.substitute_character', 'none'); // discard unsupported characters
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

        // deprecated since 2.28, loads core classes (old way)
        Clearbricks::lib()->autoload([
            'dcCore'  => implode(DIRECTORY_SEPARATOR, [$this->core->config()->dotclearRoot(),  'inc', 'core', 'class.dc.core.php']),
            'dcUtils' => implode(DIRECTORY_SEPARATOR, [$this->core->config()->dotclearRoot(),  'inc', 'core', 'class.dc.utils.php']),
        ]);

        // Check and serve plugins and var files. (from ?pf=, ?tf= and ?vf= URI)
        $this->core->fileserver();

        // Look at core factory to get utility class name to call it statically
        foreach ($this->core->dump() as $service) { // Not perfect but run once
            if (is_string($service) && is_subclass_of($service, Utility::class) && $service::CONTAINER_ID === $utility) {
                $this->utility = $service;

                break;
            }
        }
        if ($this->utility === '') {
            throw new ProcessException(sprintf(__('Unable to initialize utility %s'), $utility));
        }

        // Set called context
        $this->addContext($utility);

        // Initialize Utility
        $utility_response = $utility === '' ? false : $this->utility::init();

        // Config file exists
        if (is_file($this->core->config()->configPath())) {
            // Http setup
            if ($this->core->config()->httpScheme443()) {
                Http::$https_scheme_on_443 = true;
            }
            if ($this->core->config()->httpReverseProxy()) {
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
                    $this->core->behavior()->addBehavior($b[0], $b[1]);
                }
                unset($GLOBALS['__top_behaviors'], $b);
            }

            // Register default URLs
            $this->core->url()->registerDefault($this->core->url()::home(...));

            $this->core->url()->registerError($this->core->url()::default404(...));

            $this->core->url()->register('lang', '', '^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$', $this->core->url()::lang(...));
            $this->core->url()->register('posts', 'posts', '^posts(/.+)?$', $this->core->url()::home(...));
            $this->core->url()->register('post', 'post', '^post/(.+)$', $this->core->url()::post(...));
            $this->core->url()->register('preview', 'preview', '^preview/(.+)$', $this->core->url()::preview(...));
            $this->core->url()->register('category', 'category', '^category/(.+)$', $this->core->url()::category(...));
            $this->core->url()->register('archive', 'archive', '^archive(/.+)?$', $this->core->url()::archive(...));
            $this->core->url()->register('try', 'try', '^try/(.+)$', $this->core->url()::try(...));

            $this->core->url()->register('feed', 'feed', '^feed/(.+)$', $this->core->url()::feed(...));
            $this->core->url()->register('trackback', 'trackback', '^trackback/(.+)$', $this->core->url()::trackback(...));
            $this->core->url()->register('webmention', 'webmention', '^webmention(/.+)?$', $this->core->url()::webmention(...));
            $this->core->url()->register('xmlrpc', 'xmlrpc', '^xmlrpc/(.+)$', $this->core->url()::xmlrpc(...));

            $this->core->url()->register('wp-admin', 'wp-admin', '^wp-admin(?:/(.+))?$', $this->core->url()::wpfaker(...));
            $this->core->url()->register('wp-login', 'wp-login', '^wp-login.php(?:/(.+))?$', $this->core->url()::wpfaker(...));

            // Set post type for frontend instance with harcoded backend URL (but should not be required in backend before Utility instanciated)
            $this->core->postTypes()->set(new PostType('post', 'index.php?process=Post&id=%d', $this->core->url()->getURLFor('post', '%s'), 'Posts'));

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
        if ($utility_response && $this->utility::process()) {
            // Try to load utility process, the _REQUEST process as priority on method process.
            if (!empty($_REQUEST['process']) && preg_match('/^[A-Za-z]+$/', (string) $_REQUEST['process'])) {
                $process = $_REQUEST['process'];
            }
            if (!empty($process)) {
                $this->loadProcess($process);
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

        $this->context[$context] = true;

        // Deprecated since 2.28, use App::task()->checkContext(...) instead
        $constant = 'DC_CONTEXT_' . match ($context) {
            'BACKEND'  => 'ADMIN',
            'FRONTEND' => 'PUBLIC',
            default    => $context
        };
        if (!defined($constant)) {
            define($constant, true);
        }
    }

    public function loadProcess(string $process): void
    {
        if ($this->utility === '') {
            // Should never happened but hey
            throw new ProcessException(__('Utility not initialized'));
        }

        // Get Process full class name from Utility
        $class = $this->core->get($this->utility)->getProcess($process);

        // Call process in 3 steps: init, process, render.
        if ($class::init() !== false && $class::process() !== false) {
            $class::render();
        }
    }
}
