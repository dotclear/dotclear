<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcRestServer instance
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
class dcRestServer extends Dotclear\Core\Rest
{
	public function __construct()
	{
		parent::__construct(App::config());
	}
}
