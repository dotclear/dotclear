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
use Dotclear\Helper\Html\Html;
use Exception;
use form;

/**
 * @brief   The module configuration.
 * @ingroup customCSS
 *
 * @todo switch Helper/Html/Form/...
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
                        __('File %s does not exist and directory %s is not writable.'),
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
            if ($fp = fopen(App::backend()->css_file, 'wb')) {
                fwrite($fp, (string) $_POST['css']);
                fclose($fp);
            }

            Notices::message(__('Style sheet upgraded.'), true, true);
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

        echo
        '<p class="area"><label>' . __('Style sheet:') . '</label> ' .
        form::textarea('css', 72, 25, Html::escapeHTML((string) $css_content)) . '</p>';

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
