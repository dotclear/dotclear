<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use Dotclear\App;
use Dotclear\Module\MyPlugin;
use Dotclear\Plugin\pages\Pages;

/**
 * @brief   The module helper.
 * @ingroup attachments
 *
 * @since 2.27
 */
class My extends MyPlugin
{
    public static function checkCustomContext(int $context): ?bool
    {
        return match($context) {
            // Whole module: Limit backend to registered user and pages user
            self::MODULE => !App::task()->checkContext('BACKEND')
                || (
                    App::blog()->isDefined()
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_USAGE,
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                        Pages::PERMISSION_PAGES,
                    ]), App::blog()->id())
                ),

            default => null,
        };
    }
}
