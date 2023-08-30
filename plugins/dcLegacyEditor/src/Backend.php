<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\Core\Backend\Menus;
use Dotclear\App;
use Dotclear\Core\Process;

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

        My::addBackendMenuItem(Menus::MENU_PLUGINS, [], '');

        if (My::settings()->active) {
            if (!isset(App::filter()->wiki)) {
                App::filter()->initWikiPost();
            }

            App::formater()->addEditorFormater(My::id(), 'xhtml', fn ($s) => $s);
            App::formater()->addFormaterName('xhtml', __('HTML'));

            App::formater()->addEditorFormater(My::id(), 'wiki', [App::filter()->wiki, 'transform']);
            App::formater()->addFormaterName('wiki', __('Dotclear wiki'));

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
