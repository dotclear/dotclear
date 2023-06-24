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

use dcCore;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
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
            dcCore::app()->admin->css_file = Path::real(dcCore::app()->blog->public_path) . '/custom_style.css';

            if (!is_file(dcCore::app()->admin->css_file) && !is_writable(dirname(dcCore::app()->admin->css_file))) {
                throw new Exception(
                    sprintf(
                        __('File %s does not exist and directory %s is not writable.'),
                        dcCore::app()->admin->css_file,
                        dirname(dcCore::app()->admin->css_file)
                    )
                );
            }
            static::$init = true;
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (isset($_POST['css'])) {
            @$fp = fopen(dcCore::app()->admin->css_file, 'wb');
            fwrite($fp, $_POST['css']);
            fclose($fp);

            Page::message(__('Style sheet upgraded.'), true, true);
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $css_content = is_file(dcCore::app()->admin->css_file) ? file_get_contents(dcCore::app()->admin->css_file) : '';

        echo
        '<p class="area"><label>' . __('Style sheet:') . '</label> ' .
        form::textarea('css', 60, 20, Html::escapeHTML($css_content)) . '</p>';
    }
}
