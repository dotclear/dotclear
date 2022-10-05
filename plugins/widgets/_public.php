<?php
/**
 * @brief widgets, a plugin for Dotclear 2
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

defaultWidgets::init();

dcCore::app()->tpl->addValue('Widgets', [publicWidgets::class, 'tplWidgets']);
dcCore::app()->tpl->addBlock('Widget', [publicWidgets::class, 'tplWidget']);
dcCore::app()->tpl->addBlock('IfWidgets', [publicWidgets::class, 'tplIfWidgets']);
