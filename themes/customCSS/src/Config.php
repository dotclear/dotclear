<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\customCSS;

use Dotclear\App;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Exception;

/**
 * @brief   The module configuration.
 * @ingroup customCSS
 */
class Config
{
    use TraitProcess;

    public static function init(): bool
    {
        // load locales
        My::l10n('admin');

        // limit to backend permissions
        if (My::checkContext(My::CONFIG)) {
            $id = My::id();

            App::backend()->css_file = Path::real(App::blog()->publicPath()) . DIRECTORY_SEPARATOR . $id . '.css';
            // Cope with old way of customize this theme
            if ($id === 'customCSS' && !is_file(App::backend()->css_file)) {
                $old_file = Path::real(App::blog()->publicPath()) . '/custom_style.css';
                if (is_file($old_file) && is_writable(dirname(App::backend()->css_file))) {
                    // Try to copy old file to new one
                    $content = file_get_contents($old_file);
                    if ($content !== false) {
                        try {
                            if ($fp = fopen(App::backend()->css_file, 'wb')) {
                                fwrite($fp, $content);
                                fclose($fp);
                            }
                        } catch (Exception) {
                        }
                    }
                }
            }

            if (!is_file(App::backend()->css_file) && !is_writable(dirname(App::backend()->css_file))) {
                throw new Exception(
                    sprintf(
                        __('File %1$s does not exist and directory %2$s is not writable.'),
                        App::backend()->css_file,
                        dirname(App::backend()->css_file)
                    )
                );
            }
            self::status(true);
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (isset($_POST['css']) || isset($_POST['tplset'])) {
            try {
                if (isset($_POST['css'])) {
                    // Save configuration
                    if ($fp = fopen(App::backend()->css_file, 'wb')) {
                        fwrite($fp, (string) $_POST['css']);
                        fclose($fp);
                    }
                }
                if (isset($_POST['tplset'])) {
                    $tplset = is_string($tplset = $_POST['tplset']) ? $tplset : '';
                    App::blog()->settings()->themes->put(My::id() . '_tplset', $tplset, BlogWorkspaceInterface::NS_STRING);
                }

                App::backend()->notices()->addSuccessNotice(__('Style sheet upgraded.'));
                App::backend()->url()->redirect('admin.blog.theme', ['conf' => '1']);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $css_content = is_file(App::backend()->css_file) ? file_get_contents(App::backend()->css_file) : '';

        /**
         * List of template sets
         *
         * @todo To be updated if modified in Dotclear
         */
        $tplsets = ['mustek', 'dotty'];
        $combo   = array_map(fn ($tplset): Option => new Option(ucwords($tplset), $tplset), $tplsets);

        $tplset  = is_string($tplset = App::blog()->settings()->themes->get(My::id() . '_tplset')) ? $tplset : App::config()->defaultTplset();
        $default = sprintf(__('(the default one is <strong>%s</strong>).'), ucwords(App::config()->defaultTplset()));

        echo (new Div())
            ->items([
                (new Para())
                    ->class('area')
                    ->items([
                        (new Textarea('css'))
                            ->value(Html::escapeHTML((string) $css_content))
                            ->rows(25)
                            ->cols(72)
                            ->label((new Label(__('Style sheet:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->items([
                        (new Select('tplset'))
                            ->items($combo)
                            ->default($tplset)
                            ->label((new Label(__('Select the template set to use with this theme:'), Label::IL_TF))->suffix($default)),
                    ]),
            ])
        ->render();

        if (App::auth()->prefs()->interface->colorsyntax) {
            echo
            App::backend()->page()->jsRunCodeMirror(
                [
                    [
                        'name'  => 'editor_css',
                        'id'    => 'css',
                        'mode'  => 'css',
                        'theme' => App::auth()->prefs()->interface->colorsyntax_theme,
                    ],
                ]
            );
        }
    }
}
