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

$is_admin = $core->auth->check('admin,contentadmin', $core->blog->id) || $core->auth->isSuperAdmin();

$core->blog->settings->addNameSpace('dcckeditor');
$dcckeditor_active                      = $core->blog->settings->dcckeditor->active;
$dcckeditor_alignment_buttons           = $core->blog->settings->dcckeditor->alignment_buttons;
$dcckeditor_list_buttons                = $core->blog->settings->dcckeditor->list_buttons;
$dcckeditor_textcolor_button            = $core->blog->settings->dcckeditor->textcolor_button;
$dcckeditor_background_textcolor_button = $core->blog->settings->dcckeditor->background_textcolor_button;
$dcckeditor_cancollapse_button          = $core->blog->settings->dcckeditor->cancollapse_button;
$dcckeditor_format_select               = $core->blog->settings->dcckeditor->format_select;
$dcckeditor_format_tags                 = $core->blog->settings->dcckeditor->format_tags;
$dcckeditor_table_button                = $core->blog->settings->dcckeditor->table_button;
$dcckeditor_clipboard_buttons           = $core->blog->settings->dcckeditor->clipboard_buttons;
$dcckeditor_disable_native_spellchecker = $core->blog->settings->dcckeditor->disable_native_spellchecker;

if (!empty($_GET['config'])) {
    // text/javascript response stop stream just after including file
    include_once dirname(__FILE__) . '/_post_config.php';
    exit();
} else {
    include_once dirname(__FILE__) . '/inc/_config.php';
}
