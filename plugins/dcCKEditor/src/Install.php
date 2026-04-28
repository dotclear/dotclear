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
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module install process.
 * @ingroup dcCKEditor
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Rename settings namespace
        if (version_compare(App::version()->getVersion(My::id()), '2.0', '<=')
            && App::blog()->settings()->exists('dcckeditor')
        ) {
            App::blog()->settings()->delWorkspace(My::id());
            App::blog()->settings()->renWorkspace('dcckeditor', My::id());
        }

        $s = My::settings();
        $s->put('active', true, App::blogWorkspace()::NS_BOOL, 'CKEditor plugin activated?', false, true);
        $s->put('alignment_buttons', true, App::blogWorkspace()::NS_BOOL, 'Add alignment buttons?', false, true);
        $s->put('list_buttons', true, App::blogWorkspace()::NS_BOOL, 'Add list buttons?', false, true);
        $s->put('textcolor_button', false, App::blogWorkspace()::NS_BOOL, 'Add text color button?', false, true);
        $s->put('background_textcolor_button', false, App::blogWorkspace()::NS_BOOL, 'Add background text color button?', false, true);
        $s->put('cancollapse_button', false, App::blogWorkspace()::NS_BOOL, 'Add collapse button?', false, true);
        $s->put('format_select', true, App::blogWorkspace()::NS_BOOL, 'Add format selection?', false, true);
        $s->put('format_tags', 'p;h1;h2;h3;h4;h5;h6;pre;address', App::blogWorkspace()::NS_STRING, 'Custom formats', false, true);
        $s->put('table_button', false, App::blogWorkspace()::NS_BOOL, 'Add table button?', false, true);
        $s->put('clipboard_buttons', false, App::blogWorkspace()::NS_BOOL, 'Add clipboard buttons?', false, true);
        $s->put('action_buttons', true, App::blogWorkspace()::NS_BOOL, 'Add undo/redo buttons?', false, true);
        $s->put('disable_native_spellchecker', true, App::blogWorkspace()::NS_BOOL, 'Disables the built-in spell checker if the browser provides one?', false, true);

        return true;
    }
}
