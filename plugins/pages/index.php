<?php
/**
 * @brief pages, a plugin for Dotclear 2
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

class adminPagesRoute
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        if (($_REQUEST['act'] ?? 'list') === 'page') {
            require __DIR__ . '/page.php';
        } else {
            require __DIR__ . '/list.php';
        }
    }
}

adminPagesRoute::init();
