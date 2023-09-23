<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcMeta instance
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
class dcMeta extends Dotclear\Core\Meta
{
	public function __construct()
	{
		parent::__construct(App::auth(), App::blog(), App::con());
	}
}
