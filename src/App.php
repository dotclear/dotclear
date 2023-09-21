<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear
 * @brief       Dotclear application root
 */

namespace Dotclear {
    use Autoloader;
    use Dotclear\Core\Core;
    use Dotclear\Helper\Container\Factories;
    use Exception;

    // Load Autoloader file
    require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'Autoloader.php']);

    // Add root folder for namespaced and autoloaded classes
    Autoloader::me()->addNamespace('Dotclear', __DIR__);

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
         * Application boostrap.
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
            // Start tick
            define('DC_START_TIME', microtime(true));

            try {
                // Instanciate container
                new App(
                    new Config(dirname(__DIR__)),
                    Factories::getFactory(Core::CONTAINER_ID)
                );
            } catch (Exception $e) {
                new Fault(
                    'Server error',
                    'Site temporarily unavailable',
                    Fault::SETUP_ISSUE
                );
                exit;
            }

            try {
                // Run task
                App::task()->run($utility, $process);
            } catch (Exception $e) {
                new Fault(
                    'Server error',
                    App::task()->checkContext('BACKEND') ? $e->getMessage() : 'Site temporarily unavailable',
                    Fault::SETUP_ISSUE
                );
                exit;
            }
        }

        /// @name Deprecated methods
        //@{
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
        //@}
    }
}

namespace {
    use Dotclear\Fault;

    /**
     * @brief   Error handling function.
     *
     * @deprecated  since 2.27, use class Dotclear\Fault instead
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
