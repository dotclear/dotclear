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

use dcAuth;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Network\Http;
use Exception;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            return false;
        }

        dcCore::app()->admin->editor_is_admin = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id) || dcCore::app()->auth->isSuperAdmin();

        dcCore::app()->admin->editor_cke_active                      = dcCore::app()->blog->settings->dcckeditor->active;
        dcCore::app()->admin->editor_cke_alignment_buttons           = dcCore::app()->blog->settings->dcckeditor->alignment_buttons;
        dcCore::app()->admin->editor_cke_list_buttons                = dcCore::app()->blog->settings->dcckeditor->list_buttons;
        dcCore::app()->admin->editor_cke_textcolor_button            = dcCore::app()->blog->settings->dcckeditor->textcolor_button;
        dcCore::app()->admin->editor_cke_background_textcolor_button = dcCore::app()->blog->settings->dcckeditor->background_textcolor_button;
        dcCore::app()->admin->editor_cke_custom_color_list           = dcCore::app()->blog->settings->dcckeditor->custom_color_list;
        dcCore::app()->admin->editor_cke_colors_per_row              = dcCore::app()->blog->settings->dcckeditor->colors_per_row;
        dcCore::app()->admin->editor_cke_cancollapse_button          = dcCore::app()->blog->settings->dcckeditor->cancollapse_button;
        dcCore::app()->admin->editor_cke_format_select               = dcCore::app()->blog->settings->dcckeditor->format_select;
        dcCore::app()->admin->editor_cke_format_tags                 = dcCore::app()->blog->settings->dcckeditor->format_tags;
        dcCore::app()->admin->editor_cke_table_button                = dcCore::app()->blog->settings->dcckeditor->table_button;
        dcCore::app()->admin->editor_cke_clipboard_buttons           = dcCore::app()->blog->settings->dcckeditor->clipboard_buttons;
        dcCore::app()->admin->editor_cke_action_buttons              = dcCore::app()->blog->settings->dcckeditor->action_buttons;
        dcCore::app()->admin->editor_cke_disable_native_spellchecker = dcCore::app()->blog->settings->dcckeditor->disable_native_spellchecker;

        if (!empty($_GET['config'])) {
            // text/javascript response stop stream just after including file
            require_once __DIR__ . '/ManagePostConfig.php';
            exit();
        }

        dcCore::app()->admin->editor_cke_was_actived = dcCore::app()->admin->editor_cke_active;

        static::$init = true;

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                dcCore::app()->admin->editor_cke_active = (empty($_POST['dcckeditor_active'])) ? false : true;
                dcCore::app()->blog->settings->dcckeditor->put('active', dcCore::app()->admin->editor_cke_active, 'boolean');

                // change other settings only if they were in HTML page
                if (dcCore::app()->admin->editor_cke_was_actived) {
                    dcCore::app()->admin->editor_cke_alignement_buttons = (empty($_POST['dcckeditor_alignment_buttons'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('alignment_buttons', dcCore::app()->admin->editor_cke_alignement_buttons, 'boolean');

                    dcCore::app()->admin->editor_cke_list_buttons = (empty($_POST['dcckeditor_list_buttons'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('list_buttons', dcCore::app()->admin->editor_cke_list_buttons, 'boolean');

                    dcCore::app()->admin->editor_cke_textcolor_button = (empty($_POST['dcckeditor_textcolor_button'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('textcolor_button', dcCore::app()->admin->editor_cke_textcolor_button, 'boolean');

                    dcCore::app()->admin->editor_cke_background_textcolor_button = (empty($_POST['dcckeditor_background_textcolor_button'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('background_textcolor_button', dcCore::app()->admin->editor_cke_background_textcolor_button, 'boolean');

                    dcCore::app()->admin->editor_cke_custom_color_list = str_replace(['#', ' '], '', $_POST['dcckeditor_custom_color_list']);
                    dcCore::app()->blog->settings->dcckeditor->put('custom_color_list', dcCore::app()->admin->editor_cke_custom_color_list, 'string');

                    dcCore::app()->admin->editor_cke_colors_per_row = abs((int) $_POST['dcckeditor_colors_per_row']);
                    dcCore::app()->blog->settings->dcckeditor->put('colors_per_row', dcCore::app()->admin->editor_cke_colors_per_row);

                    dcCore::app()->admin->editor_cke_cancollapse_button = (empty($_POST['dcckeditor_cancollapse_button'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('cancollapse_button', dcCore::app()->admin->editor_cke_cancollapse_button, 'boolean');

                    dcCore::app()->admin->editor_cke_format_select = (empty($_POST['dcckeditor_format_select'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('format_select', dcCore::app()->admin->editor_cke_format_select, 'boolean');

                    // default tags : p;h1;h2;h3;h4;h5;h6;pre;address
                    dcCore::app()->admin->editor_cke_format_tags = 'p;h1;h2;h3;h4;h5;h6;pre;address';

                    $allowed_tags = explode(';', dcCore::app()->admin->editor_cke_format_tags);
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
                            dcCore::app()->admin->editor_cke_format_tags = $_POST['dcckeditor_format_tags'];
                        }
                    }
                    dcCore::app()->blog->settings->dcckeditor->put('format_tags', dcCore::app()->admin->editor_cke_format_tags, 'string');

                    dcCore::app()->admin->editor_cke_table_button = (empty($_POST['dcckeditor_table_button'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('table_button', dcCore::app()->admin->editor_cke_table_button, 'boolean');

                    dcCore::app()->admin->editor_cke_clipboard_buttons = (empty($_POST['dcckeditor_clipboard_buttons'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('clipboard_buttons', dcCore::app()->admin->editor_cke_clipboard_buttons, 'boolean');

                    dcCore::app()->admin->editor_cke_action_buttons = (empty($_POST['dcckeditor_action_buttons'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('action_buttons', dcCore::app()->admin->editor_cke_action_buttons, 'boolean');

                    dcCore::app()->admin->editor_cke_disable_native_spellchecker = (empty($_POST['dcckeditor_disable_native_spellchecker'])) ? false : true;
                    dcCore::app()->blog->settings->dcckeditor->put('disable_native_spellchecker', dcCore::app()->admin->editor_cke_disable_native_spellchecker, 'boolean');
                }

                dcPage::addSuccessNotice(__('The configuration has been updated.'));
                Http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        dcPage::openModule(__('dcCKEditor'));

        require __DIR__ . '/../tpl/index.php';

        dcPage::closeModule();
    }
}
