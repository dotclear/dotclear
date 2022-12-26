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

class adminLegacyEditor
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcCore::app()->admin->editor_is_admin = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id) || dcCore::app()->auth->isSuperAdmin();

        dcCore::app()->admin->editor_std_active = dcCore::app()->blog->settings->dclegacyeditor->active;
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        if (!empty($_POST['saveconfig'])) {
            try {
                dcCore::app()->admin->editor_std_active = (empty($_POST['dclegacyeditor_active'])) ? false : true;
                dcCore::app()->blog->settings->dclegacyeditor->put('active', dcCore::app()->admin->editor_std_active, 'boolean');

                dcPage::addSuccessNotice(__('The configuration has been updated.'));
                http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        require __DIR__ . '/tpl/' . basename(__FILE__);
    }
}

adminLegacyEditor::init();
adminLegacyEditor::process();
adminLegacyEditor::render();
