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

$_menu['Plugins']->addItem('dcLegacyEditor',
	$core->adminurl->get('admin.plugin.dcLegacyEditor'),
	dcPage::getPF('dcLegacyEditor/icon.png'),
	preg_match('/'.preg_quote($core->adminurl->get('admin.plugin.dcLegacyEditor')).'/', $_SERVER['REQUEST_URI']),
	$core->auth->check('admin,contentadmin', $core->blog->id)
);

$self_ns = $core->blog->settings->addNamespace('dclegacyeditor');

if ($self_ns->active) {
    if (!($core->wiki2xhtml instanceof wiki2xhtml)) {
		$core->initWikiPost();
	}

	$core->addEditorFormater('dcLegacyEditor','xhtml',create_function('$s','return $s;'));
	$core->addEditorFormater('dcLegacyEditor','wiki',array($core->wiki2xhtml,'transform'));

	$core->addBehavior('adminPostEditor',array('dcLegacyEditorBehaviors','adminPostEditor'));
	$core->addBehavior('adminPopupMedia',array('dcLegacyEditorBehaviors','adminPopupMedia'));
	$core->addBehavior('adminPopupLink',array('dcLegacyEditorBehaviors','adminPopupLink'));
	$core->addBehavior('adminPopupPosts',array('dcLegacyEditorBehaviors','adminPopupPosts'));
}
