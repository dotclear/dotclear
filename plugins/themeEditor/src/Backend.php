<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup themeEditor
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('themeEditor');
        __('Theme Editor');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            App::behavior()->addBehaviors([
                'adminCurrentThemeDetailsV2'   => BackendBehaviors::adminCurrentThemeDetails(...),
                'adminBeforeUserOptionsUpdate' => BackendBehaviors::adminBeforeUserUpdate(...),
                'adminPreferencesFormV2'       => BackendBehaviors::adminPreferencesForm(...),
            ]);
        }

        return self::status();
    }
}
