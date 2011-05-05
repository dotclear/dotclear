<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

if (!defined('DC_ANTISPAM_CONF_SUPER')) {
	define('DC_ANTISPAM_CONF_SUPER',false);
}

$_menu['Plugins']->addItem(__('Antispam'),'plugin.php?p=antispam','index.php?pf=antispam/icon.png',
		preg_match('/plugin.php\?p=antispam(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('admin',$core->blog->id));

$core->addBehavior('coreAfterCommentUpdate',array('dcAntispam','trainFilters'));
$core->addBehavior('adminAfterCommentDesc',array('dcAntispam','statusMessage'));
$core->addBehavior('adminDashboardIcons',array('dcAntispam','dashboardIcon'));

$core->addBehavior('adminDashboardFavs','antispamDashboardFavs');

function antispamDashboardFavs($core,$favs)
{
	$favs['antispam'] = new ArrayObject(array('antispam',__('Antispam'),'plugin.php?p=antispam',
		'index.php?pf=antispam/icon.png','index.php?pf=antispam/icon-big.png',
		'admin',null,null));
}

if (!DC_ANTISPAM_CONF_SUPER || $core->auth->isSuperAdmin()) {
	$core->addBehavior('adminBlogPreferencesForm',array('antispamBehaviors','adminBlogPreferencesForm'));
	$core->addBehavior('adminBeforeBlogSettingsUpdate',array('antispamBehaviors','adminBeforeBlogSettingsUpdate'));
}

class antispamBehaviors
{
	public static function adminBlogPreferencesForm($core,$settings)
	{
		$ttl = $settings->antispam->antispam_moderation_ttl;
		echo
		'<fieldset><legend>Antispam</legend>'.
		'<p><label for="antispam_moderation_ttl" class="classic">'.__('Delete junk comments older than').' '.
		form::field('antispam_moderation_ttl', 3, 3, $ttl).
		' '.__('days').
		'</label></p>'.
		'</fieldset>';
	}
	
	public static function adminBeforeBlogSettingsUpdate($settings)
	{
		$settings->addNamespace('antispam');
		$settings->antispam->put('antispam_moderation_ttl',(integer)$_POST['antispam_moderation_ttl']);
	}
}
?>