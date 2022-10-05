<?php
/**
 * @brief themeEditor, a plugin for Dotclear 2
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

Clearbricks::lib()->autoload([
    'themeEditorBehaviors' => __DIR__ . '/inc/admin.behaviors.php',
    'dcThemeEditor'        => __DIR__ . '/inc/themeEditor.php',
]);
