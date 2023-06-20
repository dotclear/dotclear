<?php
/**
 * This page serves all admin pages.
 * Note: since 2.27 Use name "admin.home" on dcCore::app()->adminurl methods
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
// if no process found in _REQUEST, use admin.home
define('APP_PROCESS', 'Index');

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'inc', 'admin', 'prepend.php']);
