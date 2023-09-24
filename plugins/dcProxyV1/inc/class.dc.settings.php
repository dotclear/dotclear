<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcSettings instance
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
class dcSettings extends Dotclear\Core\BlogSettings
{
	public function __construct(?string $blog_id = null)
	{
		parent::__construct(App::blogWorkspace(), App::con(), App::deprecated(), $blog_id);
	}
}
