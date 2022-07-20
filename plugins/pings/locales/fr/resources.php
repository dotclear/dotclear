<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['pings'])) {
    dcCore::app()->resources['help']['pings'] = __DIR__ . '/help/pings.html';
}
if (!isset(dcCore::app()->resources['help']['pings_post'])) {
    dcCore::app()->resources['help']['pings_post'] = __DIR__ . '/help/pings_post.html';
}
