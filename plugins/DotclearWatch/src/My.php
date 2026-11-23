<?php

/**
 * @brief       DotclearWatch My helper.
 * @ingroup     DotclearWatch
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\DotclearWatch;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            // The compatibility audit checks the modules of the whole installation: super admin only
            self::MENU, self::MANAGE => App::task()->checkContext('BACKEND')
                && App::blog()->isDefined()
                && App::auth()->isSuperAdmin(),

            default => null,
        };
    }
}
