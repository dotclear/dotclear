<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
dcCore::app()->admin->resources
    ->set('rss_news', 'Dotclear', 'https://fr.dotclear.org/blog/feed/category/News/atom')
    ->reset('doc') // remove previously set "en" doc
    ->set('doc', 'Dotclear-dokumentaatio', 'https://dotclear.org/documentation/2.0')
    ->set('doc', 'Käyttäjän opas', 'https://dotclear.org/documentation/2.0/overview/tour')
    ->set('doc', 'Käyttäjän opas', 'https://dotclear.org/documentation/2.0/usage')
    ->set('doc', 'Asennus- ja hallintaoppaat', 'https://dotclear.org/documentation/2.0/admin')
    ->set('doc', 'Dotclearin tukipalstat', 'https://forum.dotclear.net/')
    ;
