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
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\AbstractSingleton;
use Dotclear\Helper\Process\AbstractUtility;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Exception\AppException;
use Dotclear\Exception\ProcessException;
use Dotclear\Interface\Core\TaskInterface;
use ReflectionClass;
use Throwable;

/**
 * @brief   Application task launcher.
 *
 * @since   2.28, preload events has been grouped in this class
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Task extends AbstractSingleton implements TaskInterface
{
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
        // Set Exception handler
        $this->core->fault();

        // Singleton watchdog
        $this->checkSingleton();

        // Initialize lang definition
        $this->core->lang();

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
            if (is_string($service) && is_subclass_of($service, AbstractUtility::class) && $service::CONTAINER_ID === $utility) {
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
        if ($this->core->config()->hasConfig()) {
            try {
                // Check database connection
                $this->core->db()->con();
            } catch (Throwable $e) {
                // Give a pretty message for this one
                throw new AppException($e->getMessage(), (int) $e->getCode(), new AppException(
                    sprintf(
                        __('<p>This either means that the username and password information in your <strong>config.php</strong> file is incorrect or we can\'t contact the database server at "<em>%1$s</em>". This could mean your ' .
                        'host\'s database server is down.</p><ul><li>Are you sure you have the correct username and password?</li><li>Are you sure that you have typed the correct hostname?</li><li>Are you sure that the database server is running?</li></ul><p>If you\'re unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%2$s">Dotclear Support Forums</a>.</p>'),
                        $this->core->config()->dbHost() ?: 'localhost',
                        'https://matrix.to/#/#dotclear:matrix.org'
                    ),
                    (int) $e->getCode(),
                    $e
                ));
            }

            try {
                // deprecated since 2.23, use App:: instead
                $GLOBALS['core'] = new dcCore();
            } catch (AppException $e) {
                throw $e;
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
        } elseif ($this->core->config()->cliMode()) {
            // Config file does not exist, do nothing in CLI mode as we could not do redirection
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

    public function isProcessClass(?string $class): bool
    {
        if (is_string($class) && class_exists($class)) {
            $reflection = new ReflectionClass($class);

            return array_key_exists(TraitProcess::class, $reflection->getTraits())
                || ($parent = $reflection->getParentClass()) !== false && array_key_exists(TraitProcess::class, $parent->getTraits());
        }

        return false;
    }
}
