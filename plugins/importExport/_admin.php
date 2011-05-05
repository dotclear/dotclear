<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$_menu['Plugins']->addItem(__('Import/Export'),'plugin.php?p=importExport','index.php?pf=importExport/icon.png',
		preg_match('/plugin.php\?p=importExport(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('admin',$core->blog->id));

# Files needed by flat files import / export
$__autoload['backupFile'] = dirname(__FILE__).'/inc/flat/class.backupFile.php';
$__autoload['dcImport'] = dirname(__FILE__).'/inc/flat/class.dc.import.php';
$__autoload['dbExport'] = dirname(__FILE__).'/inc/flat/class.db.export.php';

$core->addBehavior('adminDashboardFavs','importExportDashboardFavs');

function importExportDashboardFavs($core,$favs)
{
	$favs['importExport'] = new ArrayObject(array('importExport',__('Import/Export'),'plugin.php?p=importExport',
		'index.php?pf=importExport/icon.png','index.php?pf=importExport/icon-big.png',
		'admin',null,null));
}
?>