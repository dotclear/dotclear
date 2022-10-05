<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class installLegacyEditor
{
    /**
     * Installs the plugin.
     *
     * @return     mixed
     */
    public static function install()
    {
        $version = dcCore::app()->plugins->moduleInfo('dcLegacyEditor', 'version');
        if (version_compare(dcCore::app()->getVersion('dcLegacyEditor'), $version, '>=')) {
            return;
        }

        $settings = dcCore::app()->blog->settings;
        $settings->addNamespace('dclegacyeditor');
        $settings->dclegacyeditor->put('active', true, 'boolean', 'dcLegacyEditor plugin activated ?', false, true);

        dcCore::app()->setVersion('dcLegacyEditor', $version);

        return true;
    }
}

return installLegacyEditor::install();
