<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
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

$core->addBehavior('adminDashboardFavorites','antispamDashboardFavorites');
$core->addBehavior('adminDashboardFavsIcon','antispamDashboardFavsIcon');

function antispamDashboardFavorites($core,$favs)
{
	$favs->register('antispam', array(
		'title' => __('Antispam'),
		'url' => 'plugin.php?p=antispam',
		'small-icon' => 'index.php?pf=antispam/icon.png',
		'large-icon' => 'index.php?pf=antispam/icon-big.png',
		'permissions' => 'admin')
	);
}

function antispamDashboardFavsIcon($core,$name,$icon)
{
	// Check if it is comments favs
	if ($name == 'comments') {
		// Hack comments title if there is at least one spam
		$str = dcAntispam::dashboardIconTitle($core);
		if ($str != '') {
			$icon[0] .= $str;
		}
	}
}

if (!DC_ANTISPAM_CONF_SUPER || $core->auth->isSuperAdmin()) {
	$core->addBehavior('adminBlogPreferencesForm',array('antispamBehaviors','adminBlogPreferencesForm'));
	$core->addBehavior('adminBeforeBlogSettingsUpdate',array('antispamBehaviors','adminBeforeBlogSettingsUpdate'));
	$core->addBehavior('adminCommentsSpamForm',array('antispamBehaviors','adminCommentsSpamForm'));
	$core->addBehavior('adminPageHelpBlock',array('antispamBehaviors','adminPageHelpBlock'));
}

class antispamBehaviors
{
	function adminPageHelpBlock($blocks)
	{
		$found = false;
		foreach($blocks as $block) {
			if ($block == 'core_comments') {
				$found = true;
				break;
			}
		}
		if (!$found) {
			return null;
		}
		$blocks[] = 'antispam_comments';
	}

	public static function adminCommentsSpamForm($core)
	{
		$ttl = $core->blog->settings->antispam->antispam_moderation_ttl;
		if ($ttl != null && $ttl >=0) {
			echo '<p>'.sprintf(__('All spam comments older than %s day(s) will be automatically deleted.'), $ttl).' '.
			sprintf(__('You can modify this duration in the %s'),'<a href="blog_pref.php#antispam_moderation_ttl"> '.__('Blog settings').'</a>').
			'.</p>';
		}
	}

	public static function adminBlogPreferencesForm($core,$settings)
	{
		$ttl = $settings->antispam->antispam_moderation_ttl;
		echo
		'<div class="fieldset"><h4>Antispam</h4>'.
		'<p><label for="antispam_moderation_ttl" class="classic">'.__('Delete junk comments older than').' '.
		form::field('antispam_moderation_ttl', 3, 3, $ttl).
		' '.__('days').
		'</label></p>'.
		'<p><a href="plugin.php?p=antispam">'.__('Set spam filters.').'</a></p>'.
		'</div>';
	}
	
	public static function adminBeforeBlogSettingsUpdate($settings)
	{
		$settings->addNamespace('antispam');
		$settings->antispam->put('antispam_moderation_ttl',(integer)$_POST['antispam_moderation_ttl']);
	}
}
?>