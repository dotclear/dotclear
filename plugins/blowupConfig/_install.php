<?php
/**
 * @brief blowupConfig, a plugin for Dotclear 2
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

class installBlowupConfig
{
    /**
     * Installs the plugin.
     *
     * @return     mixed
     */
    public static function install()
    {
        $version = dcCore::app()->plugins->moduleInfo('blowupConfig', 'version');
        if (version_compare((string) dcCore::app()->getVersion('blowupConfig'), $version, '>=')) {
            return;
        }

        dcCore::app()->blog->settings->addNamespace('themes');
        dcCore::app()->blog->settings->themes->put('blowup_style', '', 'string', 'Blow Up custom style', false);

        dcCore::app()->setVersion('blowupConfig', $version);

        return true;
    }
}

return installBlowupConfig::install();
