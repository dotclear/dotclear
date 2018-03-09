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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$core->addBehavior('adminCurrentThemeDetails', 'blowup_config_details');

if (!isset($__resources['help']['blowupConfig'])) {
    $__resources['help']['blowupConfig'] = dirname(__FILE__) . '/help.html';
}

function blowup_config_details($core, $id)
{
    if ($id == 'default' && $core->auth->check('admin', $core->blog->id)) {
        return '<p><a href="' . $core->adminurl->get('admin.plugin.blowupConfig') . '" class="button submit">' . __('Configure theme') . '</a></p>';
    }
}
