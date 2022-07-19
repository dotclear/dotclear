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
if (!defined('DC_RC_PATH')) {
    return;
}

$__autoload['dcIeModule'] = __DIR__ . '/inc/class.dc.ieModule.php';

$__autoload['dcImportFlat'] = __DIR__ . '/inc/class.dc.import.flat.php';
$__autoload['dcImportFeed'] = __DIR__ . '/inc/class.dc.import.feed.php';

$__autoload['dcExportFlat'] = __DIR__ . '/inc/class.dc.export.flat.php';

$__autoload['dcImportDC1'] = __DIR__ . '/inc/class.dc.import.dc1.php';
$__autoload['dcImportWP']  = __DIR__ . '/inc/class.dc.import.wp.php';

$__autoload['flatBackup'] = __DIR__ . '/inc/flat/class.flat.backup.php';
$__autoload['flatImport'] = __DIR__ . '/inc/flat/class.flat.import.php';
$__autoload['flatExport'] = __DIR__ . '/inc/flat/class.flat.export.php';

dcCore::app()->addBehavior('importExportModules', 'registerIeModules');

function registerIeModules($modules, dcCore $core = null)
{
    $modules['import'] = array_merge($modules['import'], ['dcImportFlat']);
    $modules['import'] = array_merge($modules['import'], ['dcImportFeed']);

    $modules['export'] = array_merge($modules['export'], ['dcExportFlat']);

    if (dcCore::app()->auth->isSuperAdmin()) {
        $modules['import'] = array_merge($modules['import'], ['dcImportDC1']);
        $modules['import'] = array_merge($modules['import'], ['dcImportWP']);
    }
}

$__autoload['ieMaintenanceExportblog'] = __DIR__ . '/inc/lib.ie.maintenance.php';
$__autoload['ieMaintenanceExportfull'] = __DIR__ . '/inc/lib.ie.maintenance.php';
