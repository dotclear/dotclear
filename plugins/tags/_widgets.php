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

dcCore::app()->addBehavior('initWidgets', [tagsWidgets::class, 'initWidgets']);
dcCore::app()->addBehavior('initDefaultWidgets', [tagsWidgets::class, 'initDefaultWidgets']);
