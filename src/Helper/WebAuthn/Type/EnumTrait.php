<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Type;

/**
 * @brief   WebAuthn enum trait helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
trait EnumTrait
{
    /**
     * @return  array<int, int|string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');;
    }
}
