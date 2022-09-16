<?php
/**
 * @brief dcCKEditor, a plugin for Dotclear 2
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

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    'dcCKEditor',
    dcCore::app()->adminurl->get('admin.plugin.dcCKEditor'),
    [dcPage::getPF('dcCKEditor/icon.svg'), dcPage::getPF('dcCKEditor/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.dcCKEditor')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_ADMIN,
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)
);

$self_ns = dcCore::app()->blog->settings->addNamespace('dcckeditor');

if ($self_ns->active) {
    dcCore::app()->addEditorFormater('dcCKEditor', 'xhtml', fn ($s) => $s);

    dcCore::app()->addBehavior('adminPostEditor', ['dcCKEditorBehaviors', 'adminPostEditor']);
    dcCore::app()->addBehavior('adminPopupMedia', ['dcCKEditorBehaviors', 'adminPopupMedia']);
    dcCore::app()->addBehavior('adminPopupLink', ['dcCKEditorBehaviors', 'adminPopupLink']);
    dcCore::app()->addBehavior('adminPopupPosts', ['dcCKEditorBehaviors', 'adminPopupPosts']);

    dcCore::app()->addBehavior('adminMediaURL', ['dcCKEditorBehaviors', 'adminMediaURL']);

    dcCore::app()->addBehavior('adminPageHTTPHeaderCSP', ['dcCKEditorBehaviors', 'adminPageHTTPHeaderCSP']);
}
