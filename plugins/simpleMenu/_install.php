<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of simpleMenu, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$version = $core->plugins->moduleInfo('simpleMenu','version');
if (version_compare($core->getVersion('simpleMenu'),$version,'>=')) {
	return;
}

# Menu par défaut
$blog_url = html::stripHostURL($core->blog->url);
$menu_default = array(
	array('label' => 'Home', 'descr' => 'Recent posts', 'url' => $blog_url),
	array('label' => 'Archives', 'descr' => '', 'url' => $blog_url.$core->url->getURLFor('archive'))
);
$core->blog->settings->system->put('simpleMenu',serialize($menu_default),'string','simpleMenu default menu',false,true);

$core->setVersion('simpleMenu',$version);
return true;
?>