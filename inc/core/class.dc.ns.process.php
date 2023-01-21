<?php
/**
 * @brief Modules handler
 *
 * Provides an object to handle modules class (themes or plugins).
 * (Before as modules file in dcModules::loadNsFile)
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
abstract class dcNsProcess
{
    /**
     * Class is initialized and ok to be used.
     *
     * @var bool
     */
    protected static $init = false;

    /**
     * Initilise class.
     *
     * @return bool  true if class can be used
     */
    abstract public static function init(): bool;

    /**
     * Performs action and/or prepares render.
     * 
     * It must return:
     * - true to enable render
     * - false to disable
     *
     * @return bool  true if process succeed
     */
    public static function process(): bool
    {
        return self::$init;
    }

    /**
     * Render process.
     * 
     * This method is used to render something.
     * (echo something to std ouput, etc...)
     */
    public static function render(): void
    {
        /**
        if (!self::$init) {
            return;
        }

        echo 'well done!';
        */
    }
}