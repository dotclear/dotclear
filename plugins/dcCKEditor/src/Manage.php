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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Exception;

class Manage extends Process
{
    public static function init(): bool
    {
        Core::backend()->editor_is_admin = self::status(My::checkContext(My::MANAGE));

        if (!self::status()) {
            return false;
        }

        Core::backend()->editor_cke_active                      = My::settings()->active;
        Core::backend()->editor_cke_alignment_buttons           = My::settings()->alignment_buttons;
        Core::backend()->editor_cke_list_buttons                = My::settings()->list_buttons;
        Core::backend()->editor_cke_textcolor_button            = My::settings()->textcolor_button;
        Core::backend()->editor_cke_background_textcolor_button = My::settings()->background_textcolor_button;
        Core::backend()->editor_cke_custom_color_list           = My::settings()->custom_color_list;
        Core::backend()->editor_cke_colors_per_row              = My::settings()->colors_per_row;
        Core::backend()->editor_cke_cancollapse_button          = My::settings()->cancollapse_button;
        Core::backend()->editor_cke_format_select               = My::settings()->format_select;
        Core::backend()->editor_cke_format_tags                 = My::settings()->format_tags;
        Core::backend()->editor_cke_table_button                = My::settings()->table_button;
        Core::backend()->editor_cke_clipboard_buttons           = My::settings()->clipboard_buttons;
        Core::backend()->editor_cke_action_buttons              = My::settings()->action_buttons;
        Core::backend()->editor_cke_disable_native_spellchecker = My::settings()->disable_native_spellchecker;

        if (!empty($_GET['config'])) {
            // text/javascript response stop stream just after including file
            require_once __DIR__ . '/ManagePostConfig.php';
            exit();
        }

        Core::backend()->editor_cke_was_actived = Core::backend()->editor_cke_active;

        return self::status(true);
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                Core::backend()->editor_cke_active = (empty($_POST['dcckeditor_active'])) ? false : true;
                My::settings()->put('active', Core::backend()->editor_cke_active, 'boolean');

                // change other settings only if they were in HTML page
                if (Core::backend()->editor_cke_was_actived) {
                    Core::backend()->editor_cke_alignement_buttons = (empty($_POST['dcckeditor_alignment_buttons'])) ? false : true;
                    My::settings()->put('alignment_buttons', Core::backend()->editor_cke_alignement_buttons, 'boolean');

                    Core::backend()->editor_cke_list_buttons = (empty($_POST['dcckeditor_list_buttons'])) ? false : true;
                    My::settings()->put('list_buttons', Core::backend()->editor_cke_list_buttons, 'boolean');

                    Core::backend()->editor_cke_textcolor_button = (empty($_POST['dcckeditor_textcolor_button'])) ? false : true;
                    My::settings()->put('textcolor_button', Core::backend()->editor_cke_textcolor_button, 'boolean');

                    Core::backend()->editor_cke_background_textcolor_button = (empty($_POST['dcckeditor_background_textcolor_button'])) ? false : true;
                    My::settings()->put('background_textcolor_button', Core::backend()->editor_cke_background_textcolor_button, 'boolean');

                    Core::backend()->editor_cke_custom_color_list = str_replace(['#', ' '], '', $_POST['dcckeditor_custom_color_list']);
                    My::settings()->put('custom_color_list', Core::backend()->editor_cke_custom_color_list, 'string');

                    Core::backend()->editor_cke_colors_per_row = abs((int) $_POST['dcckeditor_colors_per_row']);
                    My::settings()->put('colors_per_row', Core::backend()->editor_cke_colors_per_row);

                    Core::backend()->editor_cke_cancollapse_button = (empty($_POST['dcckeditor_cancollapse_button'])) ? false : true;
                    My::settings()->put('cancollapse_button', Core::backend()->editor_cke_cancollapse_button, 'boolean');

                    Core::backend()->editor_cke_format_select = (empty($_POST['dcckeditor_format_select'])) ? false : true;
                    My::settings()->put('format_select', Core::backend()->editor_cke_format_select, 'boolean');

                    // default tags : p;h1;h2;h3;h4;h5;h6;pre;address
                    Core::backend()->editor_cke_format_tags = 'p;h1;h2;h3;h4;h5;h6;pre;address';

                    $allowed_tags = explode(';', Core::backend()->editor_cke_format_tags);
                    if (!empty($_POST['dcckeditor_format_tags'])) {
                        $tags     = explode(';', $_POST['dcckeditor_format_tags']);
                        $new_tags = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $allowed_tags)) {
                                $new_tags = false;

                                break;
                            }
                        }
                        if ($new_tags) {
                            Core::backend()->editor_cke_format_tags = $_POST['dcckeditor_format_tags'];
                        }
                    }
                    My::settings()->put('format_tags', Core::backend()->editor_cke_format_tags, 'string');

                    Core::backend()->editor_cke_table_button = (empty($_POST['dcckeditor_table_button'])) ? false : true;
                    My::settings()->put('table_button', Core::backend()->editor_cke_table_button, 'boolean');

                    Core::backend()->editor_cke_clipboard_buttons = (empty($_POST['dcckeditor_clipboard_buttons'])) ? false : true;
                    My::settings()->put('clipboard_buttons', Core::backend()->editor_cke_clipboard_buttons, 'boolean');

                    Core::backend()->editor_cke_action_buttons = (empty($_POST['dcckeditor_action_buttons'])) ? false : true;
                    My::settings()->put('action_buttons', Core::backend()->editor_cke_action_buttons, 'boolean');

                    Core::backend()->editor_cke_disable_native_spellchecker = (empty($_POST['dcckeditor_disable_native_spellchecker'])) ? false : true;
                    My::settings()->put('disable_native_spellchecker', Core::backend()->editor_cke_disable_native_spellchecker, 'boolean');
                }

                Notices::addSuccessNotice(__('The configuration has been updated.'));
                My::redirect();
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(My::name());

        require My::path() . '/tpl/index.php';

        Page::closeModule();
    }
}
