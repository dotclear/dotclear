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

/**
 * Application.
 */
final class App
{
    private static $autoload;

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
}
