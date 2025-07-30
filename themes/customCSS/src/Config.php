<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\customCSS;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module configuration.
 * @ingroup customCSS
 */
class Config extends Process
{
    public static function init(): bool
    {
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

        if (isset($_POST['css'])) {
            // Save configuration
            try {
                if ($fp = fopen(App::backend()->css_file, 'wb')) {
                    fwrite($fp, (string) $_POST['css']);
                    fclose($fp);
                }

                Notices::addSuccessNotice(__('Style sheet upgraded.'));
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

        echo (new Para())
            ->class('area')
            ->items([
                (new Textarea('css'))
                    ->value(Html::escapeHTML((string) $css_content))
                    ->rows(25)
                    ->cols(72)
                    ->label((new Label(__('Style sheet:'), Label::OL_TF))),
            ])
        ->render();

        if (App::auth()->prefs()->interface->colorsyntax) {
            echo
            Page::jsRunCodeMirror(
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
