<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
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

class installSimpleMenu
{
    /**
     * Installs the plugin.
     *
     * @return     mixed
     */
    public static function install()
    {
        $version = dcCore::app()->plugins->moduleInfo('simpleMenu', 'version');
        if (version_compare(dcCore::app()->getVersion('simpleMenu'), $version, '>=')) {
            return;
        }

        # Menu par défaut
        $blog_url     = html::stripHostURL(dcCore::app()->blog->url);
        $menu_default = [
            ['label' => 'Home', 'descr' => 'Recent posts', 'url' => $blog_url, 'targetBlank' => false],
            ['label' => 'Archives', 'descr' => '', 'url' => $blog_url . dcCore::app()->url->getURLFor('archive'), 'targetBlank' => false],
        ];
        dcCore::app()->blog->settings->system->put('simpleMenu', $menu_default, 'array', 'simpleMenu default menu', false, true);
        dcCore::app()->blog->settings->system->put('simpleMenu_active', true, 'boolean', 'Active', false, true);

        dcCore::app()->setVersion('simpleMenu', $version);

        return true;
    }
}

return installSimpleMenu::install();
