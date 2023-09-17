<?php
/**
 * @deprecated 	since 2.27, use name "admin.help" on App::backend()->url methods instead
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

Dotclear\App::bootstrap('Backend', 'Help');
