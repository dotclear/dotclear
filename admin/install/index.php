<?php
/**
 * @file
 * @brief       The install endpoint.
 * @ingroup     Endpoint
 * 
 * This page serves installation process.
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'App.php']);

// no process is required here as utility load it
new Dotclear\App('Install');
