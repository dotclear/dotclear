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

$_menu['Plugins']->addItem('dcLegacyEditor',
    $core->adminurl->get('admin.plugin.dcLegacyEditor'),
    dcPage::getPF('dcLegacyEditor/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.dcLegacyEditor')) . '/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin,contentadmin', $core->blog->id)
);

$self_ns = $core->blog->settings->addNamespace('dclegacyeditor');

if ($self_ns->active) {
    if (!($core->wiki2xhtml instanceof wiki2xhtml)) {
        $core->initWikiPost();
    }

    $core->addEditorFormater('dcLegacyEditor', 'xhtml', function ($s) {return $s;});
    $core->addEditorFormater('dcLegacyEditor', 'wiki', array($core->wiki2xhtml, 'transform'));

    $core->addBehavior('adminPostEditor', array('dcLegacyEditorBehaviors', 'adminPostEditor'));
    $core->addBehavior('adminPopupMedia', array('dcLegacyEditorBehaviors', 'adminPopupMedia'));
    $core->addBehavior('adminPopupLink', array('dcLegacyEditorBehaviors', 'adminPopupLink'));
    $core->addBehavior('adminPopupPosts', array('dcLegacyEditorBehaviors', 'adminPopupPosts'));
}
