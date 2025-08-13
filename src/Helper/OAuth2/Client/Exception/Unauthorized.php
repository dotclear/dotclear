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
 * @brief 	oAuth2 client auth exception class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Unauthorized extends Exception
{
	public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
	{
        parent::__construct(empty($message) ? __('Unauthorized') : sprintf(__('Unauthorized: %s'), $message), $code, $previous);
	}
}
