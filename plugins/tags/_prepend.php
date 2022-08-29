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
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->url->register('tag', 'tag', '^tag/(.+)$', ['urlTags', 'tag']);
dcCore::app()->url->register('tags', 'tags', '^tags$', ['urlTags', 'tags']);
dcCore::app()->url->register('tag_feed', 'feed/tag', '^feed/tag/(.+)$', ['urlTags', 'tagFeed']);

Clearbricks::lib()->autoload(['tagsBehaviors' => __DIR__ . '/inc/tags.behaviors.php']);

dcCore::app()->addBehavior('coreInitWikiPost', ['tagsBehaviors', 'coreInitWikiPost']);
