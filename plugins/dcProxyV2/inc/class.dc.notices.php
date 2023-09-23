<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcNotices instance
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
class dcNotices extends Dotclear\Core\Notice
{
	public function __construct()
	{
		parent::__construct(App::behavior(), App::con());
	}
}
