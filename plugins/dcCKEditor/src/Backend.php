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

use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('dcCKEditor') . __('dotclear CKEditor integration');

        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        My::addBackendMenuItem();

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
