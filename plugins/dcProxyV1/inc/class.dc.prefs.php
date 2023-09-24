<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcPrefs instance
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
class dcPrefs extends Dotclear\Core\UserPreferences
{
	public function __construct(?string $user_id = null, ?string $user_workspace = null)
	{
		parent::__construct(App::con(), App::userWorkspace(), $user_id, $user_workspace);
	}
}
