<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcWorkspace instance
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
class dcWorkspace extends Dotclear\Core\UserWorkspace
{
	public function __construct(?string $user_id = null, ?string $workspace = null, ?MetaRecord $rs = null)
	{
		parent::__construct(App::con(), $user_id, $workspace, $rs);
	}
}
