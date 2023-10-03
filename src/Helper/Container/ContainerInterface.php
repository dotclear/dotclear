<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
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
}
