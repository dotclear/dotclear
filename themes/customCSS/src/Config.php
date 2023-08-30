<?php
/**
 * @brief Custom, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

namespace Dotclear\Theme\customCSS;

use Dotclear\Core\Backend\Notices;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Config extends Process
{
    public static function init(): bool
    {
        // limit to backend permissions
        if (My::checkContext(My::CONFIG)) {
            // load locales
            My::l10n('main');
            App::backend()->css_file = Path::real(App::blog()->public_path) . '/custom_style.css';

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
            @$fp = fopen(App::backend()->css_file, 'wb');
            fwrite($fp, $_POST['css']);
            fclose($fp);

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
        form::textarea('css', 60, 20, Html::escapeHTML($css_content)) . '</p>';
    }
}
