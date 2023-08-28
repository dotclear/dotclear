<?php
/**
 * @brief Plugin blogroll My module class.
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

namespace Dotclear\Plugin\blogroll;

use dcCore;
use Dotclear\Core\Core;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        return in_array($context, [self::MANAGE, self::MENU]) ? 
            defined('DC_CONTEXT_ADMIN')
            && !is_null(Core::blog())
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                Blogroll::PERMISSION_BLOGROLL,
                dcCore::app()->auth::PERMISSION_ADMIN,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id)
            : null;
    }
}
