<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\breadcrumb;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup breadcrumb
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Breadcrumb');
        __('Breadcrumb for Dotclear');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminBlogPreferencesFormV2'    => BackendBehaviors::adminBlogPreferencesForm(...),
            'adminBeforeBlogSettingsUpdate' => BackendBehaviors::adminBeforeBlogSettingsUpdate(...),
        ]);

        return true;
    }
}
