<?php
/**
 * @brief Theme My module class.
 *
 * A theme My class must extend this class.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.27
 */
declare(strict_types=1);

namespace Dotclear\Module;

use dcCore;
use dcModuleDefine;
use dcThemes;

/**
 * Theme module helper.
 *
 * My class of module of type "theme" SHOULD extends this class.
 */
abstract class MyTheme extends MyModule
{
    protected static function define(): dcModuleDefine
    {
        // load once themes
        if (is_null(dcCore::app()->themes)) {
            dcCore::app()->themes = new dcThemes();
            if (!is_null(dcCore::app()->blog)) {
                dcCore::app()->loadModules(dcCore::app()->blog->themes_path, null);
            }
        }

        return static::getDefineFromNamespace(dcCore::app()->themes);
    }

    protected static function checkCustomContext(int $context): ?bool
    {
        // themes specific context permissions
        switch ($context) {
            case self::BACKEND: // Backend context
            case self::CONFIG: // Config page of module
                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission, allowed to blog admin for themes
                    && !is_null(dcCore::app()->auth)
                    && !is_null(dcCore::app()->blog)
                    && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,
                    ]), dcCore::app()->blog->id);

            break;
        }

        return null;
    }
}