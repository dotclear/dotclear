<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcCKEditor;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   The module backend process.
 * @ingroup dcCKEditor
 *
 * @since   2.27
 */
class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            // Mandatory to serve CKEditor js config stream in all authorized cases
            self::MANAGE => App::task()->checkContext('BACKEND')
            && App::blog()->isDefined()
            && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
            ]), App::blog()->id()),

            // Allow access to CKEditor configuration
            self::MENU => App::task()->checkContext('BACKEND')
            && App::blog()->isDefined()
            && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()),

            default => null,
        };
    }
}
