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

use Dotclear\App;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    public static function checkCustomContext(int $context): ?bool
    {
        return in_array($context, [self::MANAGE, self::MENU]) ?
            App::task()->checkContext('BACKEND')
            && App::blog()->isDefined()
            && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())
            : null;
    }
}
