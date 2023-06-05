<?php
/**
 * @brief themeEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (static::$init) {
            dcCore::app()->addBehaviors([
                'adminCurrentThemeDetailsV2'   => [BackendBehaviors::class, 'adminCurrentThemeDetails'],
                'adminBeforeUserOptionsUpdate' => [BackendBehaviors::class, 'adminBeforeUserUpdate'],
                'adminPreferencesFormV2'       => [BackendBehaviors::class, 'adminPreferencesForm'],
            ]);
        }

        return static::$init;
    }
}
