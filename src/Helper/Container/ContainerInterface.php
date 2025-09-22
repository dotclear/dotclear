<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Container;

/**
 * @brief   Application container interface.
 *
 * Based on PSR-11 ContainerInterface
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md
 *
 * @since   2.28
 */
interface ContainerInterface
{
    /**
     * Container ID.
     *
     * @var     string  CONTAINER_ID
     */
    public const CONTAINER_ID = 'undefined';

    /**
     * Container singleton mode.
     *
     * @var     bool    CONTAINER_SINGLETON
     */
    public const CONTAINER_SINGLETON = true;

    /**
     * Get instance of a service.
     *
     * By default, an object is instanciated once.
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @param   string  $id     The object ID
     *
     * @return  mixed   The object
     */
    public function get(string $id);

    /**
     * Check if service exists.
     *
     * @param   string  $id The object ID.
     *
     * @return  bool    True if it exists
     */
    public function has(string $id): bool;

    /**
     * Dump services definitons stack.
     *
     * This sets container factory method dump() as public
     *
     * @return  array<string,string|callable>   The stack
     */
    public function dump(): array;

    /**
     * Get containers requests count.
     */
    public static function getRequestsCount(): int;

    /**
     * Get containers loads count.
     */
    public static function getLoadsCount(): int;

    /**
     * Get containers services requests count.
     *
     * @return  array<string, int>
     */
    public static function getServicesCount(): array;
}
