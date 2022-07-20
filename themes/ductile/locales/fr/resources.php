<?php
/**
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['ductile'])) {
    dcCore::app()->resources['help']['ductile'] = __DIR__ . '/help/help.html';
}
