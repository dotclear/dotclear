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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class adminTagsRoute
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        if (($_REQUEST['m'] ?? 'tags') === 'tag_posts') {
            require __DIR__ . '/tag_posts.php';
        } else {
            require __DIR__ . '/tags.php';
        }
    }
}

adminTagsRoute::init();
