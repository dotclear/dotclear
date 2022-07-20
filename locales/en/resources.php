<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->resources['rss_news'] = 'https://dotclear.org/blog/feed/category/News/atom';

dcCore::app()->resources['doc'] = [
    'Dotclear documentation'                 => 'https://dotclear.org/documentation/2.0',
    'Dotclear presentation'                  => 'https://dotclear.org/documentation/2.0/overview/tour',
    'User manual'                            => 'https://dotclear.org/documentation/2.0/usage',
    'Installation and administration guides' => 'https://dotclear.org/documentation/2.0/admin',
    'Dotclear support forum'                 => 'https://forum.dotclear.net/',
];
