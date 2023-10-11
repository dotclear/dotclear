<?php
/**
 * @file
 * @brief       The backend endpoint.
 * @ingroup     Endpoint
 * 
 * This page serves all admin pages.
 * Note: since 2.27 Use name "admin.home" on App::backend()->url methods
 *
 * If no process found in _REQUEST, will use admin.home
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

new Dotclear\App('Backend', 'Home');
