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
 * @brief 	WebAuthn store exception class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class StoreException extends Exception
{
	public function __construct(string $message = 'Invalid store query', int $code = 0, ?Throwable $previous = null)
	{
        parent::__construct($message, $code, $previous);
	}
}
