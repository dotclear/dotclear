<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
Clearbricks::lib()->autoload([
    'tagsBehaviors'       => __DIR__ . '/inc/admin.behaviors.php',
    'publicBehaviorsTags' => __DIR__ . '/inc/public.behaviors.php',
    'urlTags'             => __DIR__ . '/inc/public.url.php',
    'tplTags'             => __DIR__ . '/inc/public.tpl.php',
    'tagsWidgets'         => __DIR__ . '/inc/widgets.php',
]);

dcCore::app()->url->register('tag', 'tag', '^tag/(.+)$', [urlTags::class, 'tag']);
dcCore::app()->url->register('tags', 'tags', '^tags$', [urlTags::class, 'tags']);
dcCore::app()->url->register('tag_feed', 'feed/tag', '^feed/tag/(.+)$', [urlTags::class, 'tagFeed']);

dcCore::app()->addBehavior('coreInitWikiPost', [tagsBehaviors::class, 'coreInitWikiPost']);
