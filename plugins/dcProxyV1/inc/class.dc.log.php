<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcLog instance
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
class dcLog extends Dotclear\Core\Log
{
	public function __construct()
	{
		parent::__construct(App::auth(), App::behavior(), App::blog(), App::con(), App::deprecated());
	}
}
