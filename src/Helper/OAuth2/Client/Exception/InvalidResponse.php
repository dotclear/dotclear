<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client\Exception;

use Exception, Throwable;

/**
 * @brief 	oAuth2 client response exception class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class InvalidResponse extends Exception
{
	public function __construct(string $message = 'Invalid provider response.', int $code = 0, ?Throwable $previous = null)
	{
        parent::__construct($message, $code, $previous);
	}
}
