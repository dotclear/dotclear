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
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use dcAdmin;
use dcAuth;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\WikiToHtml;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            'dcLegacyEditor',
            dcCore::app()->adminurl->get('admin.plugin.dcLegacyEditor'),
            [dcPage::getPF('dcLegacyEditor/icon.svg'), dcPage::getPF('dcLegacyEditor/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.dcLegacyEditor')) . '/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)
        );

        if (dcCore::app()->blog->settings->dclegacyeditor->active) {
            if (!(dcCore::app()->wiki instanceof WikiToHtml)) {
                dcCore::app()->initWikiPost();
            }

            dcCore::app()->addEditorFormater('dcLegacyEditor', 'xhtml', fn ($s) => $s);
            dcCore::app()->addFormaterName('xhtml', __('HTML'));

            dcCore::app()->addEditorFormater('dcLegacyEditor', 'wiki', [dcCore::app()->wiki, 'transform']);
            dcCore::app()->addFormaterName('wiki', __('Dotclear wiki'));

            dcCore::app()->addBehaviors([
                'adminPostEditor' => [BackendBehaviors::class, 'adminPostEditor'],
                'adminPopupMedia' => [BackendBehaviors::class, 'adminPopupMedia'],
                'adminPopupLink'  => [BackendBehaviors::class, 'adminPopupLink'],
                'adminPopupPosts' => [BackendBehaviors::class, 'adminPopupPosts'],
            ]);

            // Register REST methods
            dcCore::app()->rest->addFunction('wikiConvert', [Rest::class, 'convert']);
        }

        return true;
    }
}
