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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$is_admin = dcCore::app()->auth->check('admin,contentadmin', dcCore::app()->blog->id) || dcCore::app()->auth->isSuperAdmin();

dcCore::app()->blog->settings->addNameSpace('dclegacyeditor');
$dclegacyeditor_active = dcCore::app()->blog->settings->dclegacyeditor->active;

if (!empty($_POST['saveconfig'])) {
    try {
        $dclegacyeditor_active = (empty($_POST['dclegacyeditor_active'])) ? false : true;
        dcCore::app()->blog->settings->dclegacyeditor->put('active', $dclegacyeditor_active, 'boolean');

        dcPage::addSuccessNotice(__('The configuration has been updated.'));
        http::redirect($p_url);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

include __DIR__ . '/tpl/index.php';
