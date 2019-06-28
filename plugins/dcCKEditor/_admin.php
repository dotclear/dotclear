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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$_menu['Plugins']->addItem('dcCKEditor',
    $core->adminurl->get('admin.plugin.dcCKEditor'),
    dcPage::getPF('dcCKEditor/imgs/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.dcCKEditor')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin,contentadmin', $core->blog->id)
);

$self_ns = $core->blog->settings->addNamespace('dcckeditor');

if ($self_ns->active) {
    $core->addEditorFormater('dcCKEditor', 'xhtml', function ($s) {return $s;});

    $core->addBehavior('adminPostEditor', ['dcCKEditorBehaviors', 'adminPostEditor']);
    $core->addBehavior('adminPopupMedia', ['dcCKEditorBehaviors', 'adminPopupMedia']);
    $core->addBehavior('adminPopupLink', ['dcCKEditorBehaviors', 'adminPopupLink']);
    $core->addBehavior('adminPopupPosts', ['dcCKEditorBehaviors', 'adminPopupPosts']);

    $core->addBehavior('adminMediaURL', ['dcCKEditorBehaviors', 'adminMediaURL']);

    $core->addBehavior('adminPageHTTPHeaderCSP', ['dcCKEditorBehaviors', 'adminPageHTTPHeaderCSP']);
}
