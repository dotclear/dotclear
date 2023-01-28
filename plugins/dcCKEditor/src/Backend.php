<?php
/**
 * @brief dcCKEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcCKEditor;

use dcAdmin;
use dcAuth;
use dcCore;
use dcNsProcess;
use dcPage;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            'dcCKEditor',
            dcCore::app()->adminurl->get('admin.plugin.dcCKEditor'),
            [dcPage::getPF('dcCKEditor/icon.svg'), dcPage::getPF('dcCKEditor/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.dcCKEditor')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)
        );

        if (dcCore::app()->blog->settings->dcckeditor->active) {
            dcCore::app()->addEditorFormater('dcCKEditor', 'xhtml', fn ($s) => $s);
            dcCore::app()->addFormaterName('xhtml', __('HTML'));

            dcCore::app()->addBehaviors([
                'adminPostEditor'        => [BackendBehaviors::class, 'adminPostEditor'],
                'adminPopupMedia'        => [BackendBehaviors::class, 'adminPopupMedia'],
                'adminPopupLink'         => [BackendBehaviors::class, 'adminPopupLink'],
                'adminPopupPosts'        => [BackendBehaviors::class, 'adminPopupPosts'],
                'adminPageHTTPHeaderCSP' => [BackendBehaviors::class, 'adminPageHTTPHeaderCSP'],
            ]);
        }

        return true;
    }
}
