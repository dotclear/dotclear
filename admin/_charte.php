<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminChartePage
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::check(
            dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ])
        );

        dcCore::app()->auth->user_prefs->addWorkspace('interface');

        dcCore::app()->admin->data_theme = dcCore::app()->auth->user_prefs->interface->theme;
        dcCore::app()->admin->js         = ['htmlFontSize' => dcCore::app()->auth->user_prefs->interface->htmlfontsize];
    }

    /**
     * Gets the theme.
     *
     * @return     string  The theme.
     */
    public static function getTheme(): string
    {
        return dcCore::app()->admin->theme ?? '';
    }

    /**
     * Gets the JS variables.
     *
     * @return     array  The js.
     */
    public static function getJS(): array
    {
        return dcCore::app()->admin->js ?? [];
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        require_once __DIR__ . '/tpl/' . basename(__FILE__);
    }
}

adminChartePage::init();
adminChartePage::render();
