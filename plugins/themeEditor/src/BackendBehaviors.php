<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use Exception;
use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Text;

/**
 * @brief   The module backend behaviors.
 * @ingroup themeEditor
 */
class BackendBehaviors
{
    /**
     * Add an editor button (if possible).
     *
     * @param   string  $id     The identifier
     *
     * @return  string
     */
    public static function adminCurrentThemeDetails(string $id): string
    {
        if (App::auth()->isSuperAdmin()) {
            // Check if it's not an officially distributed theme
            if (App::blog()->settings()->system->themes_path !== App::blog()->settings()->system->getGlobal('themes_path')
                || !App::themes()->getDefine($id)->get('distributed')
            ) {
                return (new Para())
                    ->items([
                        (new Link())
                            ->href(My::manageUrl())
                            ->class('button')
                            ->text(__('Edit theme files')),
                    ])
                ->render();
            }
        }

        return '';
    }

    /**
     * Save user preferences, color syntax activation and its theme.
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
     * Display user preferences, color syntax activation and theme selection.
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

        $sample = '
<textarea id="codemirror" name="codemirror" readonly="true">
// program to convert celsius to fahrenheit
// ask the celsius value to the user
const celsius = prompt("Enter a celsius value: ");

// calculate fahrenheit
const fahrenheit = (celsius * 1.8) + 32

// display the result
console.log(`${celsius} degree celsius is equal to ${fahrenheit} degree fahrenheit.`);
</textarea>';

        $codemirror = Page::jsLoadCodeMirror('', false, ['javascript']);
        if ($current_theme !== 'default') {
            $codemirror .= Page::cssLoad('js/codemirror/theme/' . $current_theme . '.css');
        }
        $codemirror .= Page::jsJson('theme_editor_current', ['theme' => $current_theme]) . My::jsLoad('theme');

        echo (new Fieldset())
            ->id('themeEditor_prefs')
            ->legend(new Legend(__('Syntax highlighting')))
            ->fields([
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('col30')
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Checkbox('colorsyntax', App::auth()->prefs()->interface->colorsyntax))
                                            ->value(1)
                                            ->label(new Label(__('Syntax highlighting in theme editor'), Label::IL_FT)),
                                    ]),
                                (
                                    count($themes_combo) > 1 ?
                                    (new Para())->items([
                                        (new Select('colorsyntax_theme'))
                                            ->default($current_theme)
                                            ->items($themes_combo)
                                            ->label(new Label(__('Theme:'), Label::IL_TF)),
                                    ]) :
                                    (new Hidden('colorsyntax_theme', ''))
                                ),
                            ]),
                        (new Div())
                            ->class('col70')
                            ->items([
                                (new Text(null, $codemirror)),
                                (new Text(null, trim($sample))),
                            ]),
                    ]),
            ])
        ->render();
    }
}
