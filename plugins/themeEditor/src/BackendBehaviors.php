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

use Exception;
use Dotclear\App;
use Dotclear\Core\Backend\Page;
use form;

class BackendBehaviors
{
    /**
     * Add an editor button (if possible)
     *
     * @param      string  $id     The identifier
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function adminCurrentThemeDetails(string $id): string
    {
        if (App::auth()->isSuperAdmin()) {
            // Check if it's not an officially distributed theme
            if (App::blog()->settings->system->themes_path !== App::blog()->settings->system->getGlobal('themes_path')
                || !App::themes()->getDefine($id)->distributed
            ) {
                return '<p><a href="' . My::manageUrl() . '" class="button">' . __('Edit theme files') . '</a></p>';
            }
        }

        return '';
    }

    /**
     * Save user preferences, color syntax activation and its theme
     */
    public static function adminBeforeUserUpdate(): void
    {
        // Get and store user's prefs for plugin options
        try {
            App::auth()->prefs()->interface->put('colorsyntax', !empty($_POST['colorsyntax']), 'boolean');
            App::auth()->prefs()->interface->put(
                'colorsyntax_theme',
                (!empty($_POST['colorsyntax_theme']) ? $_POST['colorsyntax_theme'] : '')
            );
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }
    }

    /**
     * Display suer preferences, color syntax activation and theme selection
     */
    public static function adminPreferencesForm(): void
    {
        // Add fieldset for plugin options
        $current_theme = App::auth()->prefs()->interface->colorsyntax_theme ?? 'default';

        $themes_list  = Page::getCodeMirrorThemes();
        $themes_combo = [__('Default') => ''];
        foreach ($themes_list as $theme) {
            $themes_combo[$theme] = $theme;
        }

        echo
        '<fieldset id="themeEditor_prefs" class="two-cols">' .
        '<legend>' . __('Syntax highlighting') . '</legend>';
        echo
        '<div class="col">' .
        '<p><label for="colorsyntax" class="classic">' .
        form::checkbox('colorsyntax', 1, App::auth()->prefs()->interface->colorsyntax) . '</label>' .
        __('Syntax highlighting in theme editor') .
            '</p>';
        if (count($themes_combo) > 1) {
            echo
            '<p><label for="colorsyntax_theme" class="classic">' . __('Theme:') . '</label> ' .
            form::combo(
                'colorsyntax_theme',
                $themes_combo,
                [
                    'default' => $current_theme,
                ]
            ) .
                '</p>';
        } else {
            echo form::hidden('colorsyntax_theme', '');
        }
        echo '</div>';
        echo '<div class="col">';
        echo Page::jsLoadCodeMirror('', false, ['javascript']);
        if ($current_theme !== 'default') {
            echo Page::cssLoad('js/codemirror/theme/' . $current_theme . '.css');
        }
        // Display sample Javascript code
        echo '
<textarea id="codemirror" name="codemirror" readonly="true">
// program to convert celsius to fahrenheit
// ask the celsius value to the user
const celsius = prompt("Enter a celsius value: ");

// calculate fahrenheit
const fahrenheit = (celsius * 1.8) + 32

// display the result
console.log(`${celsius} degree celsius is equal to ${fahrenheit} degree fahrenheit.`);
</textarea>';
        echo
        Page::jsJson('theme_editor_current', ['theme' => $current_theme]) .
        My::jsLoad('theme');
        echo '</div>';
        echo '</fieldset>';
    }
}
