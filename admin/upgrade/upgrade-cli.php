#!/usr/bin/env php
<?php
/**
 * @file
 * @brief 		The upgrade procedure (CLI).
 * @ingroup     Endpoint
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'App.php']);

// no process is required here as utility load it
Dotclear\App::bootstrap('Upgrade');
