<?php
/**
 * Core.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use Dotclear\Core\Backend\Utility as Backend;
use Dotclear\Core\Frontend\Utility as Frontend;

final class Core extends CoreContainer
{
    /** @var    string  Session table name */
    public const SESSION_TABLE_NAME = 'session';

    /** @var    Backend  Backend Utility instance  */
    private static Backend $backend;

    /** @var    Frontend  Frontend Utility instance  */
    private static Frontend $frontend;

    /** @var string     The current lang */
    private static string $lang = 'en';

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

            // deprecated since 2.28, use Core::backend() instead
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

            // deprecated since 2.28, use Core::frontend() instead
            dcCore::app()->public = self::$frontend;
        }

        return self::$frontend;
    }

    /**
     * Get current lang.
     *
     * @return string
     */
    public static function lang(): string
    {
        return self::$lang;
    }

    /**
     * Set the lang to use.
     *
     * @param      string  $id     The lang ID
     */
    public static function setLang($id): void
    {
        self::$lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $id) ? $id : 'en';

        // deprecated since 2.28, use Core::setLoang() instead
        dcCore::app()->lang = self::$lang;
    }
}
