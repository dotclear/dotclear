<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcNamespace instance
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
class dcNamespace extends Dotclear\Core\BlogWorkspace
{
	public function __construct(?string $blog_id = null, ?string $workspace = null, ?MetaRecord $rs = null)
	{
		parent::__construct(App::con(), App::deprecated(), $blog_id, $workspace, $rs);
	}
}
