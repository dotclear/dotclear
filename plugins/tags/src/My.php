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

use Dotclear\Core\Core;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    public static function checkCustomContext(int $context): ?bool
    {
        return in_array($context, [self::MANAGE, self::MENU]) ?
            defined('DC_CONTEXT_ADMIN')
            && !is_null(Core::blog())
            && Core::auth()->check(Core::auth()->makePermissions([
                Core::auth()::PERMISSION_USAGE,
                Core::auth()::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id)
            : null;
    }
}
