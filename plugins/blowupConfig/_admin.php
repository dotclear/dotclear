<?php
/**
 * @brief blowupConfig, a plugin for Dotclear 2
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

dcCore::app()->addBehavior(
    'adminCurrentThemeDetailsV2',
    function (string $id) {
        if ($id === 'default' && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]), dcCore::app()->blog->id)) {
            return '<p><a href="' . dcCore::app()->adminurl->get('admin.plugin.blowupConfig') . '" class="button submit">' . __('Configure theme') . '</a></p>';
        }
    }
);

if (!isset(dcCore::app()->resources['help']['blowupConfig'])) {
    dcCore::app()->resources['help']['blowupConfig'] = __DIR__ . '/help.html';
}
