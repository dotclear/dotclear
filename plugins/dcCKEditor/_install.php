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

$version = $core->plugins->moduleInfo('dcCKEditor', 'version');
if (version_compare($core->getVersion('dcCKEditor'), $version, '>=')) {
    return;
}

$settings = $core->blog->settings;
$settings->addNamespace('dcckeditor');

$settings->dcckeditor->put('active', true, 'boolean', 'dcCKEditor plugin activated?', false, true);
$settings->dcckeditor->put('alignment_buttons', true, 'boolean', 'Add alignment buttons?', false, true);
$settings->dcckeditor->put('list_buttons', true, 'boolean', 'Add list buttons?', false, true);
$settings->dcckeditor->put('textcolor_button', false, 'boolean', 'Add text color button?', false, true);
$settings->dcckeditor->put('background_textcolor_button', false, 'boolean', 'Add background text color button?', false, true);
$settings->dcckeditor->put('cancollapse_button', false, 'boolean', 'Add collapse button?', false, true);
$settings->dcckeditor->put('format_select', true, 'boolean', 'Add format selection?', false, true);
$settings->dcckeditor->put('format_tags', 'p;h1;h2;h3;h4;h5;h6;pre;address', 'string', 'Custom formats', false, true);
$settings->dcckeditor->put('table_button', false, 'boolean', 'Add table button?', false, true);
$settings->dcckeditor->put('clipboard_buttons', false, 'boolean', 'Add clipboard buttons?', false, true);
$settings->dcckeditor->put('disable_native_spellchecker', true, 'boolean', 'Disables the built-in spell checker if the browser provides one?', false, true);

$core->setVersion('dcCKEditor', $version);
return true;
