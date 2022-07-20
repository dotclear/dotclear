<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['import'])) {
    dcCore::app()->resources['help']['import'] = __DIR__ . '/help/import.html';
}
