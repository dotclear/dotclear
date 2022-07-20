<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['dcCKEditor'])) {
    dcCore::app()->resources['help']['dcCKEditor'] = __DIR__ . '/help/config_help.html';
}
