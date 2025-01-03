<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcCKEditor;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup dcCKEditor
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('dcCKEditor');
        __('dotclear CKEditor integration');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        if (My::settings()->active) {
            App::formater()->addEditorFormater(My::id(), 'xhtml', fn ($s) => $s);
            App::formater()->addFormaterName('xhtml', __('HTML'));

            App::behavior()->addBehaviors([
                'adminPostEditor'        => BackendBehaviors::adminPostEditor(...),
                'adminPopupMedia'        => BackendBehaviors::adminPopupMedia(...),
                'adminPopupLink'         => BackendBehaviors::adminPopupLink(...),
                'adminPopupPosts'        => BackendBehaviors::adminPopupPosts(...),
                'adminPageHTTPHeaderCSP' => BackendBehaviors::adminPageHTTPHeaderCSP(...),
            ]);
        }

        return true;
    }
}
