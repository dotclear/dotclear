<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
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

dcCore::app()->addBehavior('initWidgets', [blogrollWidgets::class, 'initWidgets']);
dcCore::app()->addBehavior('initDefaultWidgets', [blogrollWidgets::class, 'initDefaultWidgets']);
