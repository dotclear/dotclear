<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['simpleMenu'])) {
    dcCore::app()->resources['help']['simpleMenu'] = __DIR__ . '/help/help.html';
}
