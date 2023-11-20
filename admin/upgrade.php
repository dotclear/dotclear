<?php
/**
 * @file
 * @brief 		The upgrade procedure.
 * @ingroup     Endpoint
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

new Dotclear\App('Upgrade', 'Auth');
