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
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        if (!isset(dcCore::app()->resources['help']['themeEditor'])) {
            dcCore::app()->resources['help']['themeEditor'] = __DIR__ . '/../help.html';
        }

        dcCore::app()->addBehaviors([
            'adminCurrentThemeDetailsV2'   => [BackendBehaviors::class, 'adminCurrentThemeDetails'],
            'adminBeforeUserOptionsUpdate' => [BackendBehaviors::class, 'adminBeforeUserUpdate'],
            'adminPreferencesFormV2'       => [BackendBehaviors::class, 'adminPreferencesForm'],
        ]);

        return true;
    }
}
