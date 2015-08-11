<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$_menu['Plugins']->addItem('dcCKEditor',
	$core->adminurl->get('admin.plugin.dcCKEditor'),
	dcPage::getPF('dcCKEditor/imgs/icon.png'),
	preg_match('/'.preg_quote($core->adminurl->get('admin.plugin.dcCKEditor')).'(&.*)?$/', $_SERVER['REQUEST_URI']),
	$core->auth->check('admin,contentadmin', $core->blog->id)
);

$self_ns = $core->blog->settings->addNamespace('dcckeditor');

if ($self_ns->active) {
    $core->addEditorFormater('dcCKEditor','xhtml',create_function('$s','return $s;'));

    $core->addBehavior('adminPostEditor',array('dcCKEditorBehaviors','adminPostEditor'));
    $core->addBehavior('adminPopupMedia',array('dcCKEditorBehaviors','adminPopupMedia'));
    $core->addBehavior('adminPopupLink',array('dcCKEditorBehaviors','adminPopupLink'));
    $core->addBehavior('adminPopupPosts',array('dcCKEditorBehaviors','adminPopupPosts'));

    $core->addBehavior('adminMediaURL',array('dcCKEditorBehaviors','adminMediaURL'));
}
