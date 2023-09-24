<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcMedia instance
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
class dcMedia extends Dotclear\Core\Media
{
	public const MEDIA_TABLE_NAME = 'media';

	public function __construct(string $type = '')
	{
		parent::__construct(App::auth(), App::behavior(), App::blog(), App::config(), App::con(), App::postMedia());
		$this->setFilterMimeType($type);
	}
}
