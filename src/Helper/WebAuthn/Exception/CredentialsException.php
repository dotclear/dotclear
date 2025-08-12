<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Exception;

use Exception, Throwable;

/**
 * @brief 	WebAuthn credntials option exception class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class CredentialsException extends Exception
{
	public function __construct(string $message = 'Invalid credentials option', int $code = 0, ?Throwable $previous = null)
	{
        parent::__construct($message, $code, $previous);
	}
}
