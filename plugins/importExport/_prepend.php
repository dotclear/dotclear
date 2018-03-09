<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$__autoload['dcIeModule'] = dirname(__FILE__) . '/inc/class.dc.ieModule.php';

$__autoload['dcImportFlat'] = dirname(__FILE__) . '/inc/class.dc.import.flat.php';
$__autoload['dcImportFeed'] = dirname(__FILE__) . '/inc/class.dc.import.feed.php';

$__autoload['dcExportFlat'] = dirname(__FILE__) . '/inc/class.dc.export.flat.php';

$__autoload['dcImportDC1'] = dirname(__FILE__) . '/inc/class.dc.import.dc1.php';
$__autoload['dcImportWP']  = dirname(__FILE__) . '/inc/class.dc.import.wp.php';

$__autoload['flatBackup'] = dirname(__FILE__) . '/inc/flat/class.flat.backup.php';
$__autoload['flatImport'] = dirname(__FILE__) . '/inc/flat/class.flat.import.php';
$__autoload['flatExport'] = dirname(__FILE__) . '/inc/flat/class.flat.export.php';

$core->addBehavior('importExportModules', 'registerIeModules');

function registerIeModules($modules, $core)
{
    $modules['import'] = array_merge($modules['import'], array('dcImportFlat'));
    $modules['import'] = array_merge($modules['import'], array('dcImportFeed'));

    $modules['export'] = array_merge($modules['export'], array('dcExportFlat'));

    if ($core->auth->isSuperAdmin()) {
        $modules['import'] = array_merge($modules['import'], array('dcImportDC1'));
        $modules['import'] = array_merge($modules['import'], array('dcImportWP'));
    }
}

$__autoload['ieMaintenanceExportblog'] = dirname(__FILE__) . '/inc/lib.ie.maintenance.php';
$__autoload['ieMaintenanceExportfull'] = dirname(__FILE__) . '/inc/lib.ie.maintenance.php';
