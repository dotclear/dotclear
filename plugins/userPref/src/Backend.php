<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\userPref;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup userPref
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('user:preferences');
        __('Manage every user preference directive');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            My::addBackendMenuItem(App::backend()->menus()::MENU_SYSTEM);
        }

        return self::status();
    }
}
