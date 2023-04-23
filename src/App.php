<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear;

use Autoloader;
use Dotclear\Helper\Date;
use Dotclear\Helper\L10n;

/**
 * Application.
 */
final class App
{
    private static ?\Autoloader $autoload = null;

    /**
     * Call Dotclear autoloader.
     *
     * @return Autoloader $autoload The autoload instance
     */
    public static function autoload(): Autoloader
    {
        if (!self::$autoload) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';
            self::$autoload = new Autoloader('', '', true);
        }

        return self::$autoload;
    }

    /**
     * Initializes the object.
     */
    public static function init(): void
    {
        // We may need l10n __() function
        L10n::bootstrap();

        // We set default timezone to avoid warning
        Date::setTZ('UTC');
    }
}
