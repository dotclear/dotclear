<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['aboutConfig'])) {
    dcCore::app()->resources['help']['aboutConfig'] = __DIR__ . '/help/help.html';
}
