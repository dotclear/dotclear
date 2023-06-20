<?php
/**
 * @deprecated since 2.27 Use name "admin.users" on dcCore::app()->adminurl methods
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
define('APP_PROCESS', 'Users');

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'inc', 'admin', 'prepend.php']);
