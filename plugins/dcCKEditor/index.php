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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$is_admin = dcCore::app()->auth->check('admin,contentadmin', dcCore::app()->blog->id) || dcCore::app()->auth->isSuperAdmin();

dcCore::app()->blog->settings->addNameSpace('dcckeditor');
$dcckeditor_active                      = dcCore::app()->blog->settings->dcckeditor->active;
$dcckeditor_alignment_buttons           = dcCore::app()->blog->settings->dcckeditor->alignment_buttons;
$dcckeditor_list_buttons                = dcCore::app()->blog->settings->dcckeditor->list_buttons;
$dcckeditor_textcolor_button            = dcCore::app()->blog->settings->dcckeditor->textcolor_button;
$dcckeditor_background_textcolor_button = dcCore::app()->blog->settings->dcckeditor->background_textcolor_button;
$dcckeditor_custom_color_list           = dcCore::app()->blog->settings->dcckeditor->custom_color_list;
$dcckeditor_colors_per_row              = dcCore::app()->blog->settings->dcckeditor->colors_per_row;
$dcckeditor_cancollapse_button          = dcCore::app()->blog->settings->dcckeditor->cancollapse_button;
$dcckeditor_format_select               = dcCore::app()->blog->settings->dcckeditor->format_select;
$dcckeditor_format_tags                 = dcCore::app()->blog->settings->dcckeditor->format_tags;
$dcckeditor_table_button                = dcCore::app()->blog->settings->dcckeditor->table_button;
$dcckeditor_clipboard_buttons           = dcCore::app()->blog->settings->dcckeditor->clipboard_buttons;
$dcckeditor_action_buttons              = dcCore::app()->blog->settings->dcckeditor->action_buttons;
$dcckeditor_disable_native_spellchecker = dcCore::app()->blog->settings->dcckeditor->disable_native_spellchecker;

if (!empty($_GET['config'])) {
    // text/javascript response stop stream just after including file
    include_once __DIR__ . '/_post_config.php';
    exit();
}

include_once __DIR__ . '/inc/_config.php';
