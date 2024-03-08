<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Exception;

/**
 * @brief   Exception interface.
 *
 * @since   2.28
 */
interface AppExceptionInterface
{
    /**
     * Get exception code.
     *
     * @return  int   The exception code
     */
    public static function code(): int;

    /**
     * Get exception label.
     *
     * @return  string  The exception label
     */
    public static function label(): string;
}
