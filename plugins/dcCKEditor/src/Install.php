<?php
/**
 * @brief dcCKEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcCKEditor;

use Dotclear\App;
use Dotclear\Core\Process;

class Install extends Process
{
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
        $s->put('active', true, 'boolean', 'dcCKEditor plugin activated?', false, true);
        $s->put('alignment_buttons', true, 'boolean', 'Add alignment buttons?', false, true);
        $s->put('list_buttons', true, 'boolean', 'Add list buttons?', false, true);
        $s->put('textcolor_button', false, 'boolean', 'Add text color button?', false, true);
        $s->put('background_textcolor_button', false, 'boolean', 'Add background text color button?', false, true);
        $s->put('cancollapse_button', false, 'boolean', 'Add collapse button?', false, true);
        $s->put('format_select', true, 'boolean', 'Add format selection?', false, true);
        $s->put('format_tags', 'p;h1;h2;h3;h4;h5;h6;pre;address', 'string', 'Custom formats', false, true);
        $s->put('table_button', false, 'boolean', 'Add table button?', false, true);
        $s->put('clipboard_buttons', false, 'boolean', 'Add clipboard buttons?', false, true);
        $s->put('action_buttons', true, 'boolean', 'Add undo/redo buttons?', false, true);
        $s->put('disable_native_spellchecker', true, 'boolean', 'Disables the built-in spell checker if the browser provides one?', false, true);

        return true;
    }
}
