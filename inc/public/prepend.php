<?php
/**
 * @file
 * @brief 		Deprecated public prepend.
 *
 * Keep this file for backward compatibility with existing blogs index.php
 *
 * @deprecated 	since 2.27, use Dotclear\App::boostrap('Frontend'); instead
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'App.php']);

Dotclear\App::bootstrap('Frontend');
