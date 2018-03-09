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

if (!defined('DC_RC_PATH')) {return;}

$core->url->register('tag', 'tag', '^tag/(.+)$', array('urlTags', 'tag'));
$core->url->register('tags', 'tags', '^tags$', array('urlTags', 'tags'));
$core->url->register('tag_feed', 'feed/tag', '^feed/tag/(.+)$', array('urlTags', 'tagFeed'));

$__autoload['tagsBehaviors'] = dirname(__FILE__) . '/inc/tags.behaviors.php';

$core->addBehavior('coreInitWikiPost', array('tagsBehaviors', 'coreInitWikiPost'));
