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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (!isset(dcCore::app()->resources['help']['themeEditor'])) {
    dcCore::app()->resources['help']['themeEditor'] = __DIR__ . '/help.html';
}

dcCore::app()->addBehavior('adminCurrentThemeDetailsV2', [themeEditorBehaviors::class, 'adminCurrentThemeDetails']);

dcCore::app()->addBehavior('adminBeforeUserOptionsUpdate', [themeEditorBehaviors::class, 'adminBeforeUserUpdate']);
dcCore::app()->addBehavior('adminPreferencesFormV2', [themeEditorBehaviors::class, 'adminPreferencesForm']);
