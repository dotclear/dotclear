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
 * @brief   Container exception.
 *
 * Based on PSR-11 ContainerInterface
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md
 *
 * @since   2.28
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
