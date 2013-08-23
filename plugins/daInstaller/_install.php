<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of daInstaller, a plugin for DotClear2.
# Copyright (c) 2008-2011 Tomtom, Pep and contributors, for DotAddict.org.
# All rights reserved.
#
# ***** END LICENSE BLOCK *****
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$m_version = $core->plugins->moduleInfo('daInstaller','version');
$i_version = $core->getVersion('daInstaller');
if (version_compare($i_version,$m_version,'>=')) {
	return;
}

# Settings compatibility test
if (!version_compare(DC_VERSION,'2.2-x','<')) {
	$core->blog->settings->addNamespace('dainstaller');
	$s = $core->blog->settings->dainstaller;
}
else {
	$core->blog->settings->setNamespace('dainstaller');
	$s = $core->blog->settings;
}

# Création du setting
$s->put(
	'dainstaller_plugins_xml',
	'http://update.dotaddict.org/dc2/plugins.xml',
	'string','Plugins XML feed location',true,true
);
$s->put(
	'dainstaller_themes_xml',
	'http://update.dotaddict.org/dc2/themes.xml',
	'string','Themes XML feed location',true,true
);
$s->put(
	'dainstaller_allow_multi_install',
	false,
	'boolean','Allow the multi-installation',true,true
);

$daInstaller = new daInstaller($core);
$daInstaller->check(true);
unset($daInstaller);

$core->setVersion('daInstaller',$m_version);
return true;

?>