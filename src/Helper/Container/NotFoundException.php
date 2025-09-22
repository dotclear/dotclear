<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Container;

use Dotclear\Exception\InternalServerException;

/**
 * @brief   Container exception.
 *
 * Based on PSR-11 ContainerInterface
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md
 *
 * @since   2.28
 */
class NotFoundException extends InternalServerException implements NotFoundExceptionInterface
{
}
