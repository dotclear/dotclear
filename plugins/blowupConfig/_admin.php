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
    'adminCurrentThemeDetails',
    function (dcCore $core, $id) {
        if ($id == 'default' && dcCore::app()->auth->check('admin', dcCore::app()->blog->id)) {
            return '<p><a href="' . dcCore::app()->adminurl->get('admin.plugin.blowupConfig') . '" class="button submit">' . __('Configure theme') . '</a></p>';
        }
    }
);

if (!isset($__resources['help']['blowupConfig'])) {
    $__resources['help']['blowupConfig'] = __DIR__ . '/help.html';
}
