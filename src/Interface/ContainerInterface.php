<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface;

/**
 * @brief   Application container interface.
 *
 * Based on PSR-11 ContainerInterface
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md
 *
 * As complex exceptions handling is not set in Dotclear, only \Exception is thrown.
 *
 * @since 2.28
 */
interface ContainerInterface
{
    /**
     * Get instance of an object.
     *
     * By default, an object is instanciated once.
     *
     * @throws  \Exception
     *
     * @param   string  $id     The object ID
     *
     * @return  mixed   The object
     */
    public function get(string $id);

    /**
     * Check if core object exists.
     *
     * @param   string  $id The object ID.
     *
     * @return  bool    True if it exists
     */
    public function has(string $id): bool;
}
