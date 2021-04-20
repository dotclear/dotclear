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

$core->url->register('tag', 'tag', '^tag/(.+)$', ['urlTags', 'tag']);
$core->url->register('tags', 'tags', '^tags$', ['urlTags', 'tags']);
$core->url->register('tag_feed', 'feed/tag', '^feed/tag/(.+)$', ['urlTags', 'tagFeed']);

$__autoload['tagsBehaviors'] = dirname(__FILE__) . '/inc/tags.behaviors.php';

$core->addBehavior('coreInitWikiPost', ['tagsBehaviors', 'coreInitWikiPost']);
