<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['maintenance'])) {
    dcCore::app()->resources['help']['maintenance'] = __DIR__ . '/help/maintenance.html';
}
