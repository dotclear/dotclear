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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$dcckeditor_was_actived = $dcckeditor_active;

if (!empty($_POST['saveconfig'])) {
    try {
        $dcckeditor_active = (empty($_POST['dcckeditor_active'])) ? false : true;
        $core->blog->settings->dcckeditor->put('active', $dcckeditor_active, 'boolean');

        // change other settings only if they were in html page
        if ($dcckeditor_was_actived) {
            $dcckeditor_alignement_buttons = (empty($_POST['dcckeditor_alignment_buttons'])) ? false : true;
            $core->blog->settings->dcckeditor->put('alignment_buttons', $dcckeditor_alignement_buttons, 'boolean');

            $dcckeditor_list_buttons = (empty($_POST['dcckeditor_list_buttons'])) ? false : true;
            $core->blog->settings->dcckeditor->put('list_buttons', $dcckeditor_list_buttons, 'boolean');

            $dcckeditor_textcolor_button = (empty($_POST['dcckeditor_textcolor_button'])) ? false : true;
            $core->blog->settings->dcckeditor->put('textcolor_button', $dcckeditor_textcolor_button, 'boolean');

            $dcckeditor_background_textcolor_button = (empty($_POST['dcckeditor_background_textcolor_button'])) ? false : true;
            $core->blog->settings->dcckeditor->put('background_textcolor_button', $dcckeditor_background_textcolor_button, 'boolean');

            $dcckeditor_cancollapse_button = (empty($_POST['dcckeditor_cancollapse_button'])) ? false : true;
            $core->blog->settings->dcckeditor->put('cancollapse_button', $dcckeditor_cancollapse_button, 'boolean');

            $dcckeditor_format_select = (empty($_POST['dcckeditor_format_select'])) ? false : true;
            $core->blog->settings->dcckeditor->put('format_select', $dcckeditor_format_select, 'boolean');

            // default tags : p;h1;h2;h3;h4;h5;h6;pre;address
            $allowed_tags = array('p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'address');
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
                    $dcckeditor_format_tags = $_POST['dcckeditor_format_tags'];
                }
            } else {
                $dcckeditor_format_tags = 'p;h1;h2;h3;h4;h5;h6;pre;address';
            }
            $core->blog->settings->dcckeditor->put('format_tags', $dcckeditor_format_tags, 'string');

            $dcckeditor_table_button = (empty($_POST['dcckeditor_table_button'])) ? false : true;
            $core->blog->settings->dcckeditor->put('table_button', $dcckeditor_table_button, 'boolean');

            $dcckeditor_clipboard_buttons = (empty($_POST['dcckeditor_clipboard_buttons'])) ? false : true;
            $core->blog->settings->dcckeditor->put('clipboard_buttons', $dcckeditor_clipboard_buttons, 'boolean');

            $dcckeditor_disable_native_spellchecker = (empty($_POST['dcckeditor_disable_native_spellchecker'])) ? false : true;
            $core->blog->settings->dcckeditor->put('disable_native_spellchecker', $dcckeditor_disable_native_spellchecker, 'boolean');
        }

        dcPage::addSuccessNotice(__('The configuration has been updated.'));
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

include dirname(__FILE__) . '/../tpl/index.tpl';
