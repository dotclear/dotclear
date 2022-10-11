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
class importExportBehaviors
{
    /**
     * Register import/export modules
     *
     * @param      ArrayObject  $modules  The modules
     */
    public static function registerIeModules(ArrayObject $modules): void
    {
        $modules['import'] = array_merge($modules['import'], ['dcImportFlat']);
        $modules['import'] = array_merge($modules['import'], ['dcImportFeed']);

        $modules['export'] = array_merge($modules['export'], ['dcExportFlat']);

        if (dcCore::app()->auth->isSuperAdmin()) {
            $modules['import'] = array_merge($modules['import'], ['dcImportDC1']);
            $modules['import'] = array_merge($modules['import'], ['dcImportWP']);
        }
    }
}
