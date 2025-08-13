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
 * @brief 	oAuth2 client config exception class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class InvalidClient extends Exception
{
	public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
	{
        parent::__construct(empty($message) ? __('Invalid client configuration') : sprintf(__('Missing client configuration key "%s"'), $message), $code, $previous);
	}
}
