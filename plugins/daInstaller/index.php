<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of daInstaller, a plugin for DotClear2.
# Copyright (c) 2008-2011 Tomtom, Pep and contributors, for DotAddict.org.
# All rights reserved.
#
# ***** END LICENSE BLOCK *****
if (!defined('DC_CONTEXT_ADMIN')) { return; }

# Settings compatibility test
if (!version_compare(DC_VERSION,'2.2-x','<')) {
	$core->blog->settings->addNamespace('dainstaller');
	$s = $core->blog->settings->dainstaller;
}
else {
	$core->blog->settings->setNamespace('dainstaller');
	$s = $core->blog->settings;
}

# Initialisation des variables
$p_url 		= 'plugin.php?p=daInstaller';
$default_tab 	= !empty($_GET['tab']) ? html::escapeHTML($_GET['tab']) : 'plugins';
$page		= !empty($_GET['page']) ? (integer)$_GET['page'] : 1;
$nb_per_page 	= 10;
$q			= !empty($_GET['q']) ? trim(html::escapeHTML($_GET['q'])) : '';
$default_tab 	= !empty($q) ? 'search' : $default_tab;
$mode 		= isset($_GET['mode']) ? html::escapeHTML($_GET['mode']) : 'plugins';
$ppaths 		= explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
$plugins_path	= array_pop($ppaths);
$daInstaller 	= new daInstaller($core);
unset($ppaths);

$pages['plugins'] = $pages['themes'] = $pages['search'] = 1;
$pages[$default_tab] = $page;

# Ajout d'un plugin
if (!empty($_POST['add_plugin']) && !empty($_POST['package_url'])) {
	try {
		$url = html::escapeHTML($_POST['package_url']);
		$dest = $plugins_path.'/'.basename($url);

		$ret_code = $daInstaller->processPackage($url,$dest,$core->plugins);
		http::redirect($p_url.'&p_added='.$ret_code);
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
		$default_tab = 'plugins';
	}
}
# Mise à jour de plugins
elseif (!empty($_POST['upd_plugins']) && !empty($_POST['plugins_id'])) {
	try {
		$daInstaller->check();
		$ids = $_POST['plugins_id'];
		$modules = $daInstaller->getModules('plugins',true);

		foreach ($modules as $module) {
			if (in_array($module['id'],$ids)) {
				if (!$s->dainstaller_allow_multi_install) {
					$dest = $module['root'].'/../'.basename($module['file']);
				}
				else {
					$dest = $plugins_path.'/'.basename($module['file']);
					if ($module['root'] != $dest) @file_put_contents($module['root'].'/_disabled','');
				}
				$ret_code[] = $daInstaller->processPackage($module['file'],$dest,$core->plugins);
			}
		}
		
		$arg = 'p_updated='.implode('|',$ids).'&p_status='.implode('|',$ret_code);

		http::redirect($p_url.'&tab=update&'.$arg);
		
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
		$default_tab = 'update';
	}
}
# Ajout d'un thème
elseif (!empty($_POST['add_theme']) && !empty($_POST['package_url'])) {
	try {
		$url = html::escapeHTML($_POST['package_url']);
		$dest = $core->blog->themes_path.'/'.basename($url);

		$core_themes = new dcModules($core);
		$core_themes->loadModules($core->blog->themes_path,null);
		$ret_code = $daInstaller->processPackage($url,$dest,$core_themes);
		http::redirect($p_url.'&tab=themes&t_added='.$ret_code);
	}
	catch (Exception $e) {
		unset($core_themes);
		$core->error->add($e->getMessage());
		$default_tab = 'themes';
	}
}
# Mise à jour de thèmes
elseif (!empty($_POST['upd_themes']) && !empty($_POST['themes_id'])) {
	try {
		$daInstaller->check();
		$core_themes = new dcModules($core);
		$core_themes->loadModules($core->blog->themes_path,null);
		$ids = $_POST['themes_id'];
		$modules = $daInstaller->getModules('themes',true);

		foreach ($modules as $module) {
			if (in_array($module['id'],$ids)) {
				$dest = $core->blog->themes_path.'/'.basename($module['file']);
				$ret_code[] = $daInstaller->processPackage($module['file'],$dest,$core_themes);
			}
		}

		$arg = 't_updated='.implode('|',$ids).'&t_status='.implode('|',$ret_code);

		http::redirect($p_url.'&tab=update&'.$arg);

	}
	catch (Exception $e) {
		unset($core_themes);
		$core->error->add($e->getMessage());
		$default_tab = 'update';
	}
}

# Et c'est parti !
$plugins_install = $core->plugins->installModules();
$daInstaller->check(false,true);

/**
 * This function returns all error / success messages
 *
 * @return string
 */
function infoMessages()
{
	$res = '';
	$p_msg = '<p class="success">%s</p>';
	$p_err = '<p class="error">%s</p>';
	
	# Plugins install message
	if (!empty($_GET['p_added']) && $_GET['p_added'] != 2) {
		$res .= sprintf($p_msg,__('The plugin has been successfully installed.'));
	}
	# Themes install message
	if (!empty($_GET['t_added']) && $_GET['t_added'] != 2) {
		$res .= sprintf($p_msg,__('The theme has been successfully installed.'));
	}
	# Plugins update message
	if (!empty($_GET['p_updated'])) {
		$err = $upd = '';
		$ids = explode('|',html::escapeHTML($_GET['p_updated']));
		$status = explode('|',html::escapeHTML($_GET['p_status']));
		foreach ($ids as $k => $v) {
			if ($status[$k] != 2) {
				$err .= '<li>'.$v.'</li>';
			}
			else {
				$upd .= '<li>'.$v.'</li>';
			}
		}
		if (!empty($err)) {
			$res .= '<div class="error">'.__('Following plugins have not been updated:').'<ul>'.$err.'</ul></div>';
		}
		else {
			$res .= '<div class="success">'.__('Following plugins have been updated:').'<ul>'.$upd.'</ul></div>';
		}
	}
	# Themes update message
	if (!empty($_GET['t_updated'])) {
		$err = $upd = '';
		$ids = explode('|',html::escapeHTML($_GET['t_updated']));
		$status = explode('|',html::escapeHTML($_GET['t_status']));
		foreach ($ids as $k => $v) {
			if ($status[$k] != 2) {
				$err .= '<li>'.$v.'</li>';
			}
			else {
				$upd .= '<li>'.$v.'</li>';
			}
		}
		if (!empty($err)) {
			$res .= '<div class="error">'.__('Following themes have not been updated:').'<ul>'.$err.'</ul></div>';
		}
		else {
			$res .= '<div class="success">'.__('Following themes have been updated:').'<ul>'.$upd.'</ul></div>';
		}
	}
	# Plugins install settings messages
	if (!empty($plugins_install['success'])) {
		$res .= '<div class="success">'.__('Following plugins have been installed:').'<ul>';
		foreach ($plugins_install['success'] as $k => $v) {
			$res .= '<li>'.$k.'</li>';
		}
		$res .= '</ul></div>';
	}
	if (!empty($plugins_install['failure'])) {
		$res .= '<div class="error">'.__('Following plugins have not been installed:').'<ul>';
		foreach ($plugins_install['failure'] as $k => $v) {
			$res .= '<li>'.$k.' ('.$v.')</li>';
		}
		$res .= '</ul></div>';
	}
	
	return $res;
}

# Récupération de la liste des mises à jour des plugins
# et préparation de l'objet d'affichage
$u_p_rs = $daInstaller->getModules('plugins',true);
$u_p_nb = count($u_p_rs);
$u_p_rs = staticRecord::newFromArray($u_p_rs);
$u_p_list = new daModulesUpdateList($core,$u_p_rs,$u_p_nb);

# Récupération de la liste des mises à jour des thèmes
# et préparation de l'objet d'affichage
$u_t_rs = $daInstaller->getModules('themes',true);
$u_t_nb = count($u_t_rs);
$u_t_rs = staticRecord::newFromArray($u_t_rs);
$u_t_list = new daModulesUpdateList($core,$u_t_rs,$u_t_nb);

# Récupération de la liste des plugins disponibles
# et préparation de l'objet d'affichage
$avail_plugins = $daInstaller->getModules('plugins');
$a_p_nb = count($avail_plugins);
$a_p_rs = staticRecord::newFromArray($avail_plugins);
$a_p_list = new daModulesList($core,$a_p_rs,$a_p_nb);

# Récupération de la liste des thèmes disponibles
# et préparation de l'objet d'affichage
$avail_themes = $daInstaller->getModules('themes');
$a_t_nb = count($avail_themes);
$a_t_rs = staticRecord::newFromArray($avail_themes);
$a_t_list = new daModulesList($core,$a_t_rs,$a_t_nb);

# Récupération de la liste des plugins recherchés
if (!empty($q))
{
	$default_tab = 'search';
	$search_modules = $daInstaller->search($q,$mode);
	$s_m_nb = count($search_modules);
	$s_m_rs = staticRecord::newFromArray($search_modules);
	$s_m_list = new daModulesList($core,$s_m_rs,$s_m_nb);
}

# DISPLAY
# -------
echo
'<html>'.
'<head>'.
	'<title>'.__('DotAddict.org Installer').'</title>'.
	dcPage::jsModal().
	dcPage::jsPageTabs($default_tab).
	dcPage::jsLoad('index.php?pf=daInstaller/js/_dainstaller.js').
	'<link rel="stylesheet" href="index.php?pf=daInstaller/style.css" type="text/css" />'.
'</head>'.
'<body>'.

dcPage::breadcrumb(
	array(
		__('System') => '',
		'<span class="page-title">'.__('DotAddict.org Installer').'</span>' => ''
	)).

infoMessages();

echo
'<p>'.__('Install and update your extensions live from DotAddict.org').'</p>';

echo
'<!-- Available updates -->'.
'<div class="multi-part" id="update" title="'.__('Available updates').'">';
if ($u_p_nb > 0 || $u_t_nb > 0) {
	echo
	'<p><strong>'.__('Detected updates for your system').'</strong></p>'.
	'<p class="form-note warn">'.
		__('Changes can be required after installation of updates. Click on a support link before to be aware about').
	'</p>';
}
else {
	echo '<p><strong>'.__('No update available').'</strong></p>';
}
	$u_p_list->display('plugins',$p_url);
	$u_t_list->display('themes',$p_url);
echo
'</div>'.
'<!-- Get more plugins -->'.
'<div class="multi-part" id="plugins" title="'.__('Get more plugins').'">'.
	'<p><strong>'.($a_p_nb > 0 ? sprintf(__('Available plugins (%s)'),$a_p_nb) : '').'</strong></p>';
	$a_p_list->display($pages['plugins'],$nb_per_page,'plugins',$p_url);
echo
'</div>'.
'<!-- Get more themes -->'.
'<div class="multi-part" id="themes" title="'.__('Get more themes').'">'.
	'<p><strong>'.($a_t_nb > 0 ? sprintf(__('Available themes (%s)'),$a_t_nb) : '').'</strong></p>';
	$a_t_list->display($pages['themes'],5,'themes',$p_url);
echo
'</div>'.
'<!-- Search -->'.
'<div class="multi-part" id="search" title="'.__('Search').'">'.
	'<form method="get" action="'.$p_url.'" class="fieldset">'.
	'<h3>'.__('Search').'</h3>'.
	'<p>'.form::hidden('p','daInstaller').
	'<label for="q" class="classic">'.__('Query:').'&nbsp;</label> '.
	form::field('q',30,255,html::escapeHTML($q)).'</p>'.
	'<p><label for="mode" class="classic">'.
	form::radio(array('mode','mode'),'plugins',$mode == 'plugins').
	' '.__('Plugins').'&nbsp;</label> '.
	'<label for="mode2" class="classic">'.
	form::radio(array('mode','mode2'),'themes',$mode == 'themes').
	' '.__('Themes').'&nbsp;</label></p>'.
	'<p><input type="submit" value="'.__('Search').'" /></p>'.
	'</form>';
if (!empty($q)) {
	echo '<p><strong>'.sprintf(__('%u %s found'),$s_m_nb,__($mode)).'</strong></p>';
	if ($s_m_nb > 0) {
		$s_m_list->display($pages['search'],$nb_per_page,'search-'.$mode,$p_url);
	}
}
echo
'</div>';

dcPage::helpBlock('da_installer');

echo
'</body>'.
'</html>';