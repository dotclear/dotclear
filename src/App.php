<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/*
 * @namespace   Dotclear
 * @brief       Dotclear application root
 */

namespace Dotclear;

use Autoloader;
use Dotclear\Core\Core;
use Dotclear\Exception\AppException;
use Throwable;

// Load Autoloader file
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'Autoloader.php']);

// Add root folder for namespaced and autoloaded classes
Autoloader::me()->addNamespace('Dotclear', __DIR__);

// Load PHPGlobal helper
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'PHPGlobal.php']);

/**
 * @brief   Application.
 *
 * Note this class includes all core container methods.
 * Container search factory for requested methods.
 *
 * Dotclear default factory will be used at least.
 *
 * @see     Factories
 *
 * @since   2.27
 */
final class App extends Core
{
    /**
     * Application.
     *
     * Load application with their utility and process, if any.
     *
     * Usage:
     * ```php
     * require_once path/to/App.php
     * new Dotclear\App(Utility, Process);
     * ```
     *
     * utility and process MUST use Dotclear::Helper::Process::TraitProcess.
     *
     * Supported utilities are Backend, Frontend, Install, Upgrade (CLI)
     *
     * @param   string  $utility    The optionnal app utility (Backend or Frontend)
     * @param   string  $process    The optionnal app utility default process
     */
    public function __construct(string $utility = '', string $process = '')
    {
        // Start tick
        define('DC_START_TIME', microtime(true));

        try {
            // Load application services
            parent::__construct(dirname(__DIR__));

            // Run task
            $this->task()->run($utility, $process);
        } catch (AppException $e) {
            // Throw application exception as is. See Dotclear.Core.Fault handler.
            throw $e;
        } catch (Throwable $e) {
            // Throw uncaught exception as application exception. See Dotclear.Core.Fault handler.
            throw new AppException('Site temporarily unavailable', (int) $e->getCode(), $e);
        }

        // Disable doing anything after app
        dotclear_exit();
    }

    /// @name Deprecated methods
    ///@{
    /**
     * Application boostrap.
     *
     * @deprecated  Since 2.28, use new App('Utility', 'Process');
     *
     * @param   string  $utility    The optionnal app utility (Backend or Frontend)
     * @param   string  $process    The optionnal app utility default process
     */
    public static function bootstrap(string $utility = '', string $process = ''): void
    {
        new self($utility, $process);
    }

    /**
     * Read Dotclear release config.
     *
     * @deprecated  Since 2.28, use App:config()->release(xxx) or App:config()->yyy() instead.
     *
     * @param   string  $key The release key
     *
     * @return  string  The release value
     */
    public static function release(string $key): string
    {
        return App::config()->release($key);
    }

    /**
     * Call Dotclear autoloader.
     *
     * @deprecated  Since 2.27, use Autoloader::me() instead
     *
     * @return  Autoloader  $autoload   The autoload instance
     */
    public static function autoload(): Autoloader
    {
        return Autoloader::me();
    }
    ///@}
}
