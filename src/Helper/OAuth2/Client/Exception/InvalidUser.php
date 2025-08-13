<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client\Exception;

use Exception;
use Throwable;

/**
 * @brief 	oAuth2 client user exception class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class InvalidUser extends Exception
{
    public function __construct(string $message = 'Invalid user.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
