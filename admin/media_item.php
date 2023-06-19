<?php
/**
 * @deprecated since 2.27 Use name "admin.media.item" on dcCore::app()->adminurl methods
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
define('APP_PROCESS', 'MediaItem');

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'inc', 'admin', 'prepend.php']);
