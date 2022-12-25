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

dcCore::app()->addBehaviors([
    'adminCurrentThemeDetailsV2'   => [themeEditorBehaviors::class, 'adminCurrentThemeDetails'],
    'adminBeforeUserOptionsUpdate' => [themeEditorBehaviors::class, 'adminBeforeUserUpdate'],
    'adminPreferencesFormV2'       => [themeEditorBehaviors::class, 'adminPreferencesForm'],
]);
