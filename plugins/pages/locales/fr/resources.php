<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['pages'])) {
    dcCore::app()->resources['help']['pages'] = __DIR__ . '/help/pages.html';
}
if (!isset(dcCore::app()->resources['help']['page'])) {
    dcCore::app()->resources['help']['page'] = __DIR__ . '/help/page.html';
}
