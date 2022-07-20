<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['userPref'])) {
    dcCore::app()->resources['help']['userPref'] = __DIR__ . '/help/help.html';
}
