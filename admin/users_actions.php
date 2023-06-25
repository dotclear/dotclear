<?php
/**
 * @deprecated since 2.27 Use name "admin.user.actions" on dcCore::app()->adminurl methods
 *
 * @todo Move to backend Actions
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'App.php']);

Dotclear\App::bootstrap('Backend', 'UsersActions');
