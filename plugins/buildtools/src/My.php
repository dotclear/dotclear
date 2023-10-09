<?php
/**
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\buildtools;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   The module helper.
 * @ingroup buildtools
 *
 * @since 	2.27
 */
class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
    	// Limit to backend and admin
        return  $context === self::INSTALL ? null :
        	App::task()->checkContext('BACKEND')
            && App::blog()->isDefined()
            && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]), App::blog()->id());
    }
}
