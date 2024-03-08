<?php
/**
 * @file
 * @brief 		The upgrade procedure.
 * @ingroup     Endpoint
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

new Dotclear\App('Upgrade', 'Auth');
