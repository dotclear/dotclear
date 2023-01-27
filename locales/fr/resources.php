<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
dcCore::app()->resources['rss_news'] = 'https://fr.dotclear.org/blog/feed/category/News/atom';

dcCore::app()->resources['doc'] = [
    "Accueil de l'aide Dotclear"               => 'https://fr.dotclear.org/documentation/2.0',
    'Présentation de Dotclear'                 => 'https://fr.dotclear.org/documentation/2.0/overview/tour',
    "Manuel de l'utilisateur"                  => 'https://fr.dotclear.org/documentation/2.0/usage',
    "Guide d'installation et d'administration" => 'https://fr.dotclear.org/documentation/2.0/admin',
    'Forum de support de Dotclear'             => 'https://forum.dotclear.net/',
];
