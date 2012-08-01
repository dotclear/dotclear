<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of importExport, a plugin for DotClear2.
#
# Copyright (c) 2003-2012 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

global $__autoload,$core;

$__autoload['dcIeModule'] = 	dirname(__FILE__).'/inc/class.dc.ieModule.php';

$__autoload['dcImportFlat'] = 	dirname(__FILE__).'/inc/class.dc.import.flat.php';
$__autoload['dcImportFeed'] = 	dirname(__FILE__).'/inc/class.dc.import.feed.php';

$__autoload['dcExportFlat'] = 	dirname(__FILE__).'/inc/class.dc.export.flat.php';

$__autoload['dcImportDC1'] = 	dirname(__FILE__).'/inc/class.dc.import.dc1.php';
$__autoload['dcImportWP'] = 	dirname(__FILE__).'/inc/class.dc.import.wp.php';

$__autoload['flatBackup'] = 	dirname(__FILE__).'/inc/flat/class.flat.backup.php';
$__autoload['flatImport'] = 	dirname(__FILE__).'/inc/flat/class.flat.import.php';
$__autoload['flatExport'] = 	dirname(__FILE__).'/inc/flat/class.flat.export.php';

$core->addBehavior('importExportModules','registerIeModules');

function registerIeModules($modules)
{
	$modules['import'][] = 'dcImportFlat';
	$modules['import'][] = 'dcImportFeed';
	
	$modules['export'][] = 'dcExportFlat';
	
	if ($GLOBALS['core']->auth->isSuperAdmin()) {
		$modules['import'][] = 'dcImportDC1';
		$modules['import'][] = 'dcImportWP';
	}
}
?>