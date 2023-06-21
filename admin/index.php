<?php
/**
 * This page serves all admin pages.
 * Note: since 2.27 Use name "admin.home" on dcCore::app()->adminurl methods
 *
 * If no process found in _REQUEST, will use admin.home
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
define('APP_PROCESS', 'Home');

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'inc', 'admin', 'prepend.php']);
