<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['antispam'])) {
    dcCore::app()->resources['help']['antispam'] = __DIR__ . '/help/help.html';
}
if (!isset(dcCore::app()->resources['help']['antispam-filters'])) {
    dcCore::app()->resources['help']['antispam-filters'] = __DIR__ . '/help/filters.html';
}
if (!isset(dcCore::app()->resources['help']['ip-filter'])) {
    dcCore::app()->resources['help']['ip-filter'] = __DIR__ . '/help/ip.html';
}
if (!isset(dcCore::app()->resources['help']['iplookup-filter'])) {
    dcCore::app()->resources['help']['iplookup-filter'] = __DIR__ . '/help/iplookup.html';
}
if (!isset(dcCore::app()->resources['help']['words-filter'])) {
    dcCore::app()->resources['help']['words-filter'] = __DIR__ . '/help/words.html';
}
if (!isset(dcCore::app()->resources['help']['antispam_comments'])) {
    dcCore::app()->resources['help']['antispam_comments'] = __DIR__ . '/help/comments.html';
}
