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

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    'dcLegacyEditor',
    dcCore::app()->adminurl->get('admin.plugin.dcLegacyEditor'),
    [dcPage::getPF('dcLegacyEditor/icon.svg'), dcPage::getPF('dcLegacyEditor/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.dcLegacyEditor')) . '/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_ADMIN,
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)
);

if (dcCore::app()->blog->settings->dclegacyeditor->active) {
    if (!(dcCore::app()->wiki2xhtml instanceof wiki2xhtml)) {
        dcCore::app()->initWikiPost();
    }

    dcCore::app()->addEditorFormater('dcLegacyEditor', 'xhtml', fn ($s) => $s);
    dcCore::app()->addFormaterName('xhtml', __('HTML'));

    dcCore::app()->addEditorFormater('dcLegacyEditor', 'wiki', [dcCore::app()->wiki2xhtml, 'transform']);
    dcCore::app()->addFormaterName('wiki', __('Dotclear wiki'));

    dcCore::app()->addBehaviors([
        'adminPostEditor' => [dcLegacyEditorBehaviors::class, 'adminPostEditor'],
        'adminPopupMedia' => [dcLegacyEditorBehaviors::class, 'adminPopupMedia'],
        'adminPopupLink'  => [dcLegacyEditorBehaviors::class, 'adminPopupLink'],
        'adminPopupPosts' => [dcLegacyEditorBehaviors::class, 'adminPopupPosts'],
    ]);

    // Register REST methods
    dcCore::app()->rest->addFunction('wikiConvert', [dcLegacyEditorRest::class, 'convert']);
}
