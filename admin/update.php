<?php
/**
 * @deprecated 	since 2.27, use name "upgrade.home" on App::backend()->url() methods instead
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

new Dotclear\App('Upgrade', 'Home');
