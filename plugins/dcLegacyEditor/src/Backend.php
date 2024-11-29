<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup dcLegacyEditor
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('dcLegacyEditor') . __('dotclear legacy editor');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(App::backend()->menus()::MENU_PLUGINS, [], '');

        if (My::settings()->active) {
            if (!App::filter()->wiki()) {
                App::filter()->initWikiPost();
            }

            App::formater()->addEditorFormater(My::id(), 'xhtml', fn ($s) => $s);
            App::formater()->addFormaterName('xhtml', __('HTML'));

            if (App::filter()->wiki()) {
                App::formater()->addEditorFormater(My::id(), 'wiki', [App::filter()->wiki(), 'transform']);
                App::formater()->addFormaterName('wiki', __('Dotclear wiki'));
            }

            App::behavior()->addBehaviors([
                'adminPostEditor' => BackendBehaviors::adminPostEditor(...),
                'adminPopupMedia' => BackendBehaviors::adminPopupMedia(...),
                'adminPopupLink'  => BackendBehaviors::adminPopupLink(...),
                'adminPopupPosts' => BackendBehaviors::adminPopupPosts(...),
            ]);

            // Register REST methods
            App::rest()->addFunction('wikiConvert', Rest::convert(...));
        }

        return true;
    }
}
