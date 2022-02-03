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

$core->addBehavior(
    'adminCurrentThemeDetails',
    function ($core, $id) {
        if ($id == 'default' && $core->auth->check('admin', $core->blog->id)) {
            return '<p><a href="' . $core->adminurl->get('admin.plugin.blowupConfig') . '" class="button submit">' . __('Configure theme') . '</a></p>';
        }
    }
);

if (!isset($__resources['help']['blowupConfig'])) {
    $__resources['help']['blowupConfig'] = __DIR__ . '/help.html';
}
