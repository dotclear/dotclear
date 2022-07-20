<?php
/**
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!isset(dcCore::app()->resources['help']['tags'])) {
    dcCore::app()->resources['help']['tags'] = __DIR__ . '/help/tags.html';
}
if (!isset(dcCore::app()->resources['help']['tag_posts'])) {
    dcCore::app()->resources['help']['tag_posts'] = __DIR__ . '/help/tag_posts.html';
}
if (!isset(dcCore::app()->resources['help']['tag_post'])) {
    dcCore::app()->resources['help']['tag_post'] = __DIR__ . '/help/tag_post.html';
}
