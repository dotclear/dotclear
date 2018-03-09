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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$is_admin = $core->auth->check('admin,contentadmin', $core->blog->id) || $core->auth->isSuperAdmin();

$core->blog->settings->addNameSpace('dclegacyeditor');
$dclegacyeditor_active = $core->blog->settings->dclegacyeditor->active;

if (!empty($_POST['saveconfig'])) {
    try {
        $dclegacyeditor_active = (empty($_POST['dclegacyeditor_active'])) ? false : true;
        $core->blog->settings->dclegacyeditor->put('active', $dclegacyeditor_active, 'boolean');

        dcPage::addSuccessNotice(__('The configuration has been updated.'));
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

include dirname(__FILE__) . '/tpl/index.tpl';
