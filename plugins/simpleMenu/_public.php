<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
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

require __DIR__ . '/_widgets.php';

// Simple menu template functions
dcCore::app()->tpl->addValue('SimpleMenu', [tplSimpleMenu::class, 'simpleMenu']);
