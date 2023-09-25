<?php
/**
 * @file
 * @brief       The core dcProxyV2 dcBlog instance
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
class dcBlog extends Dotclear\Core\Blog
{
	public function __construct(string $blog_id = '')
	{
		parent::__construct(App::auth(), App::behavior(), App::blogSettings(), App::categories(), App::config(), App::con(), App::filter(), App::formater(), App::postMedia(), $blog_id);
	}
}
