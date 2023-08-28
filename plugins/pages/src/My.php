<?php
/**
 * @brief Plugin pages My module class.
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.27
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use dcCore;
use Dotclear\Core\Core;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    public const PERMISSION_PAGES = \initPages::PERMISSION_PAGES; // 'pages';

    protected static function checkCustomContext(int $context): ?bool
    {
        return in_array($context, [self::BACKEND, self::MANAGE, self::MENU]) ? // allow pages permissions
            defined('DC_CONTEXT_ADMIN')
            && !is_null(Core::blog())
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                My::PERMISSION_PAGES,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id)
            : null;
    }
}
