<?php
/**
 * @deprecated since 2.27 Use name "admin.users" on dcCore::app()->admin->url methods
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

Dotclear\App::bootstrap('Backend', 'Users');
