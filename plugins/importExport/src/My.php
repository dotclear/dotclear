<?php
/**
 * @brief Plugin importExport My module class.
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

namespace Dotclear\Plugin\importExport;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        return in_array($context, [self::MANAGE, self::MENU]) ?
            defined('DC_CONTEXT_ADMIN')
            && App::blog()->isDefined()
            && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())
            : null;
    }
}
