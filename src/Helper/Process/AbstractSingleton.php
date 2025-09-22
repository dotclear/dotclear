<?php

/**
 * @package     Dotclear
 * @subpackage Core
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Process;

use Dotclear\Exception\ContextException;
use ReflectionClass;

/**
 * @brief   Singleton watchdog helper.
 *
 * This add method that prevent a class to be started twice.
 * The method can be called from a contructor or a class method.
 *
 * Note: This is not a singleton instance helper !
 *
 * @since   2.36
 */
class AbstractSingleton
{
    /**
     * Singleton watchdog stack.
     *
     * @var     string[]    $singleton
     */
    private static array $singleton = [];

    /**
     * Check singleton watchdog.
     *
     * @throws  ContextException    If called twice
     */
    final protected function checkSingleton(): void
    {
        if (in_array(static::class, self::$singleton)) {
            throw new ContextException(sprintf('%s can not be started twice.', (new ReflectionClass(static::class))->getShortName()));
        }

        self::$singleton[] = static::class;
    }
}