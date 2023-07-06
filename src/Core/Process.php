<?php
/**
 * @brief Modules handler
 *
 * Provides an object to handle process in three steps:
 * init ? => process ? => render
 * (Before as modules file in dcModules::loadNsFile)
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

abstract class Process
{
    /**
     * @deprecated since 2.27 Use self::status()
     *
     * @var     bool
     */
    protected static $init = false;

    /** @var    array<string,bool>  All process statuses */
    private static $statuses = [];

    /**
     * Get/set process status.
     *
     * @param   null|bool   $status     The status or null to read current value
     *
     * @return  bool    The process status, true for usable else false
     */
    final protected static function status(?bool $status = null): bool
    {
        if (is_bool($status)) {
            self::$statuses[static::class] = $status;
        }

        return self::$statuses[static::class] ?? false;
    }

    /**
     * Initialise class.
     *
     * This method SHOULD set self::status().
     *
     * @return bool  true if class can be used
     */
    abstract public static function init(): bool;

    /**
     * Performs action and/or prepares render.
     *
     * This method SHOULD check self::status().
     *
     * It must return:
     * - true to enable render
     * - false to disable
     *
     * @return bool  true if process succeed
     */
    public static function process(): bool
    {
        return self::status();
    }

    /**
     * Render process.
     *
     * This method SHOULD check self::status().
     *
     * This method is used to render something.
     * (echo something to std ouput, etc...)
     */
    public static function render(): void
    {
        /*
        if (!self::status()) {
            return;
        }

        echo 'well done!';
        */
    }
}
