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
    use Dotclear\Helper\L10n;
    use Dotclear\Helper\Network\Http;
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

            // Test database connection
            if (is_file(App::config()->configPath())) {
                try {
                    App::con();
                } catch (Exception $e) {
                    // Loading locales for detected language
                    $detected_languages = Http::getAcceptLanguages();
                    foreach ($detected_languages as $language) {
                        if ($language === 'en' || L10n::set(implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), $language, 'main'])) !== false) {
                            L10n::lang($language);

                            // We stop at first accepted language
                            break;
                        }
                    }
                    unset($detected_languages);

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
                            (App::config()->debugMode() ?
                                '<p>' . __('The following error was encountered while trying to read the database:') . '</p><ul><li>' . $e->getMessage() . '</li></ul>' :
                                ''),
                            (App::config()->dbHost() !== '' ? App::config()->dbHost() : 'localhost')
                        ) :
                        '',
                        Fault::DATABASE_ISSUE
                    );
                }
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
