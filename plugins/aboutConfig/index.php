<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

class adminPageAboutConfig
{
	public static $p_url = 'plugin.php?p=aboutConfig';
	
	# Update local settings
	public static function updLocal($form)
	{
		self::updSettings($form);
	}
	
	# Update global settings
	public static function updGlobal($form)
	{
		self::updSettings($form,true);
	}
	
	# Update settings
	protected static function updSettings($form,$global=false)
	{
		global $core,$_ctx;
		
		$part = $global ? 'global' : 'local';
		$prefix = $part.'_';
		
		try {
			foreach ($core->blog->settings->dumpNamespaces() as $ns => $namespace) {
				$core->blog->settings->addNamespace($ns);
				$ns_settings = $global ? 
					$namespace->dumpGlobalSettings() : $namespace->dumpSettings();
				
				foreach ($ns_settings as $k => $v) {
					// need to cast type
					$f = (string) $form->{$prefix.$ns.'_'.$k};
					settype($f,$v['type']);
					
					$core->blog->settings->$ns->put($k,$f,null,null,true,$global);
					$form->{$prefix.$ns.'_'.$k} = $f;
				}
			}
			$core->blog->triggerBlog();
			
			http::redirect(self::$p_url.'&upd=1&part='.$part);
		}
		catch (Exception $e) {
			$_ctx->addError($e->getMessage());
		}
	}
	
	# Set nav and settings forms
	public static function setForms($global=false)
	{
		global $core, $_ctx;
		
		$prefix = $global ? 'global_' : 'local_';
		$action = $global ? 'updGlobal' : 'updLocal';
		
		if (!empty($_POST[$prefix.'nav'])) {
			http::redirect(self::$p_url.$_POST[$prefix.'nav']);
			exit;
		}
		
		$nav_form = new dcForm($core,$prefix.'nav_form','plugin.php');
		$settings_form = new dcForm($core,$prefix.'settings_form','plugin.php');
		
		$settings = $combo = array();
		foreach ($core->blog->settings->dumpNamespaces() as $ns => $namespace) {
			$ns_settings = $global ? 
				$namespace->dumpGlobalSettings() : $namespace->dumpSettings();
			
			foreach ($ns_settings as $k => $v) {
				$settings[$ns][$k] = $v;
			}
		}
		
		ksort($settings);
		foreach ($settings as $ns => $s) {
			$combo['#'.$prefix.$ns] = $ns;
			ksort($s);
			foreach ($s as $k => $v)	{
				if ($v['type'] == 'boolean') {
					$settings_form->addField(
						new dcFieldCombo($prefix.$ns.'_'.$k,
							'',array(1 => __('yes'),0 => __('no'))));
				}
				else {
					$settings_form->addField(
						new dcFieldText($prefix.$ns.'_'.$k,''));
				}
				$settings_form->{$prefix.$ns.'_'.$k} = $v['value'];
			}
		}
		
		$nav_form
			->addField(
				new dcFieldCombo($prefix.'nav','',$combo,array(
					"label" => __('Goto:'))))
			->addField(
				new dcFieldSubmit($prefix.'nav_submit',__('OK')))
			->addField(
				new dcFieldHidden ('p','aboutConfig'))
			;
		
		$settings_form
			->addField(
				new dcFieldSubmit($prefix.'submit',__('Save'),array(
					'action' => array('adminPageAboutConfig',$action))))
			->addField(
				new dcFieldHidden ('p','aboutConfig'))
			;
		
		$_ctx->{$prefix.'settings'} = $settings;
		
		$nav_form->setup();
		$settings_form->setup();
	}
}

# Local settings forms
adminPageAboutConfig::setForms();

# Global settings forms
adminPageAboutConfig::setForms(true);

# Commons
if (!empty($_GET['upd'])) {
	$_ctx->setAlert(__('Configuration successfully updated'));
}
if (!empty($_GET['upda'])) {
	$_ctx->setAlert(__('Settings definition successfully updated'));
}
$_ctx->default_tab = !empty($_GET['part']) && $_GET['part'] == 'global' ? 'global' : 'local';
$_ctx->setBreadCrumb('about:config');
$core->tpl->display('@aboutConfig/index.html.twig');
?>