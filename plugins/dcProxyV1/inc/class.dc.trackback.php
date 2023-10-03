<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcTrackback instance
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
class dcTrackback extends Dotclear\Core\Trackback
{
	public function __construct()
	{
		parent::__construct(App::behavior(), APP::blog(), App::config(), App::con(), App::postTypes());
	}
}
