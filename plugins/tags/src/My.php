<?php
/**
 * @brief Plugin tags My module class.
 *
 * A theme My class must extend this class.
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

namespace Dotclear\Plugin\tags;

use dcCore;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    public static function checkCustomContext(int $context): ?bool
    {
        return in_array($context, [self::MANAGE, self::MENU]) ?
            defined('DC_CONTEXT_ADMIN')
            && !is_null(dcCore::app()->auth)
            && !is_null(dcCore::app()->blog)
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_USAGE,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)
            : null;
    }
}
