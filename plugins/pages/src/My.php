<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   The module helper.
 * @ingroup pages
 *
 * @since   2.27
 */
class My extends MyPlugin
{
    public const PERMISSION_PAGES = \initPages::PERMISSION_PAGES; // 'pages';

    protected static function checkCustomContext(int $context): ?bool
    {
        return in_array($context, [self::BACKEND, self::MANAGE, self::MENU]) ? // allow pages permissions
            App::task()->checkContext('BACKEND')
            && App::blog()->isDefined()
            && App::auth()->check(App::auth()->makePermissions([
                My::PERMISSION_PAGES,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())
            : null;
    }
}
