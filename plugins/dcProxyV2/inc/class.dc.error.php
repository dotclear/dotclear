<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcError instance
 * @ingroup     dcProxyV2
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

use Dotclear\App;

/**
 * @deprecated 	since 2.28
 */
class dcError extends Dotclear\Core\Error
{
	public function __construct()
	{
		parent::__construct(App::deprecated());
	}
}
