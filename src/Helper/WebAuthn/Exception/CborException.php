<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Exception;

use Exception;
use Throwable;

/**
 * @brief   WebAuthn CBOR exception class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class CborException extends Exception
{
    public function __construct(string $message = 'Invalid CBOR value', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
