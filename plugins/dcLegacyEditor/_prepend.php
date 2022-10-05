<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
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
    'dcLegacyEditorBehaviors' => __DIR__ . '/inc/admin.behaviors.php',
    'dcLegacyEditorRest'      => __DIR__ . '/_services.php',
]);
