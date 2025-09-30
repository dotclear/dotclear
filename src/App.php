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

use Dotclear\Core\Core;
use Dotclear\Exception\AppException;
use Throwable;

// Load Dotclear's root functions
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'Functions.php']);

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
            // Start output capture
            ob_start();

            // Load application services
            parent::__construct(dirname(__DIR__));

            // Run task
            $this->task()->run($utility, $process);

            // End output capture
            ob_end_flush();
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
}
