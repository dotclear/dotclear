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

$_menu['Blog']->addItem(__('Tags'),
	$core->adminurl->get('admin.plugin.tags',array('m' => 'tags')),
	dcPage::getPF('tags/icon.png'),
	preg_match('/'.preg_quote($core->adminurl->get('admin.plugin.tags')).'&m=tag(s|_posts)?(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('usage,contentadmin',$core->blog->id));

require dirname(__FILE__).'/_widgets.php';

$core->addBehavior('adminPostFormItems',array('tagsBehaviors','tagsField'));

$core->addBehavior('adminAfterPostCreate',array('tagsBehaviors','setTags'));
$core->addBehavior('adminAfterPostUpdate',array('tagsBehaviors','setTags'));

$core->addBehavior('adminPostHeaders',array('tagsBehaviors','postHeaders'));

$core->addBehavior('adminPostsActionsPage',array('tagsBehaviors','adminPostsActionsPage'));

$core->addBehavior('adminPreferencesForm',array('tagsBehaviors','adminUserForm'));
$core->addBehavior('adminBeforeUserOptionsUpdate',array('tagsBehaviors','setTagListFormat'));

$core->addBehavior('adminUserForm',array('tagsBehaviors','adminUserForm'));
$core->addBehavior('adminBeforeUserCreate',array('tagsBehaviors','setTagListFormat'));
$core->addBehavior('adminBeforeUserUpdate',array('tagsBehaviors','setTagListFormat'));

$core->addBehavior('adminDashboardFavorites',array('tagsBehaviors','dashboardFavorites'));

$core->addBehavior('adminPageHelpBlock', array('tagsBehaviors', 'adminPageHelpBlock'));

$core->addBehavior('adminPostEditor', array('tagsBehaviors','adminPostEditor'));
$core->addBehavior('ckeditorExtraPlugins', array('tagsBehaviors', 'ckeditorExtraPlugins'));
