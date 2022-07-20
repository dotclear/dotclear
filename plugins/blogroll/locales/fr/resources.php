<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['blogroll'])) {
    dcCore::app()->resources['help']['blogroll'] = __DIR__ . '/help/blogroll.html';
}
