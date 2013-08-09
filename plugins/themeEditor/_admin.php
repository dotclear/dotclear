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

if (!isset($__resources['help']['themeEditor'])) {
	$__resources['help']['themeEditor'] = dirname(__FILE__).'/help.html';
}

$core->addBehavior('adminCurrentThemeDetails', array('themeEditorBehaviors','theme_editor_details'));

$core->addBehavior('adminBeforeUserOptionsUpdate',array('themeEditorBehaviors','adminBeforeUserUpdate'));
$core->addBehavior('adminPreferencesForm',array('themeEditorBehaviors','adminPreferencesForm'));

class themeEditorBehaviors
{
	public static function theme_editor_details($core,$id)
	{
		if ($id != 'default' && $core->auth->isSuperAdmin()) {
			return '<p><a href="plugin.php?p=themeEditor" class="button">'.__('Theme Editor').'</a></p>';
		}
	}

	public static function adminBeforeUserUpdate($cur,$userID)
	{
		global $core;

		// Get and store user's prefs for plugin options
		$core->auth->user_prefs->addWorkspace('interface');
		try {
			$core->auth->user_prefs->interface->put('colorsyntax',!empty($_POST['colorsyntax']),'boolean');
		} 
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	
	public static function adminPreferencesForm($core)
	{
		// Add fieldset for plugin options
		$core->auth->user_prefs->addWorkspace('interface');

		echo
		'<fieldset><legend>'.__('Theme Editor').'</legend>'.
		
		'<p><label for="colorsyntax" class="classic">'.
		form::checkbox('colorsyntax',1,$core->auth->user_prefs->interface->colorsyntax).' '.
		__('Syntax color').'</label></p>'.

		'<br class="clear" />'. //Opera sucks
		'</fieldset>';
	}
}
?>