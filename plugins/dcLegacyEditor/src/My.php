<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   The module helper.
 * @ingroup dcLegacyEditor
 */
class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            // Allow MANAGE and MENU to also content admin
            self::MANAGE, self::MENU => App::task()->checkContext('BACKEND')
            && App::blog()->isDefined()
            && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()),

            default => null,
        };
    }
}
