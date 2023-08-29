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
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('themeEditor') . __('Theme Editor');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            dcCore::app()->behavior->addBehaviors([
                'adminCurrentThemeDetailsV2'   => BackendBehaviors::adminCurrentThemeDetails(...),
                'adminBeforeUserOptionsUpdate' => BackendBehaviors::adminBeforeUserUpdate(...),
                'adminPreferencesFormV2'       => BackendBehaviors::adminPreferencesForm(...),
            ]);
        }

        return self::status();
    }
}
