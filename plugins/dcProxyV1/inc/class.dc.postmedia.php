<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcPostMedia instance
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
class dcPostMedia extends Dotclear\Core\PostMedia
{
	public function __construct()
	{
		parent::__construct(App::con());
		$this->loadFromBlog(App::blog());
	}
}
