<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

/**
 * @brief   Process class structure.
 *
 * Provides an object to handle process in three steps:
 * init ? => process ? => render
 * (Before as modules file in dcModules::loadNsFile)
 */
abstract class Process
{
    /**
     * Keep track of parent status.
     *
     * @deprecated since 2.27, use self::status()
     *
     * @var     bool    $init
     */
    protected static $init = false;

    /**
     * All process statuses.
     *
     * @var     array<string,bool>  $statuses
     */
    private static array $statuses = [];

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
     * This method SHOULD stay as small as possible for modules
     * as it can be called multiple times to check only class status.
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
     *
     * Example:
     *
     * ```php
     * if (!self::status()) {
     *     return;
     * }
     *
     * echo 'well done!';
     * ```
     */
    public static function render(): void
    {
    }
}
