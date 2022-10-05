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
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->addBehavior('initWidgets', [pagesWidgets::class, 'initWidgets']);
dcCore::app()->addBehavior('initDefaultWidgets', [pagesWidgets::class, 'initDefaultWidgets']);
