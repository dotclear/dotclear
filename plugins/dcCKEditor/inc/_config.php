<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2014 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$dcckeditor_was_actived = $dcckeditor_active;

if (!empty($_POST['saveconfig'])) {
    try {
        $dcckeditor_active = (empty($_POST['dcckeditor_active']))?false:true;
        $core->blog->settings->dcckeditor->put('active', $dcckeditor_active, 'boolean');

        // change other settings only if they were in html page
        if ($dcckeditor_was_actived) {
            $dcckeditor_alignement_buttons = (empty($_POST['dcckeditor_alignment_buttons']))?false:true;
            $core->blog->settings->dcckeditor->put('alignment_buttons', $dcckeditor_alignement_buttons, 'boolean');

            $dcckeditor_list_buttons = (empty($_POST['dcckeditor_list_buttons']))?false:true;
            $core->blog->settings->dcckeditor->put('list_buttons', $dcckeditor_list_buttons, 'boolean');

            $dcckeditor_textcolor_button = (empty($_POST['dcckeditor_textcolor_button']))?false:true;
            $core->blog->settings->dcckeditor->put('textcolor_button', $dcckeditor_textcolor_button, 'boolean');

            $dcckeditor_cancollapse_button = (empty($_POST['dcckeditor_cancollapse_button']))?false:true;
            $core->blog->settings->dcckeditor->put('cancollapse_button', $dcckeditor_cancollapse_button, 'boolean');

            $dcckeditor_format_select = (empty($_POST['dcckeditor_format_select']))?false:true;
            $core->blog->settings->dcckeditor->put('format_select', $dcckeditor_format_select, 'boolean');

            $dcckeditor_table_button = (empty($_POST['dcckeditor_table_button']))?false:true;
            $core->blog->settings->dcckeditor->put('table_button', $dcckeditor_table_button, 'boolean');

            $dcckeditor_clipboard_buttons = (empty($_POST['dcckeditor_clipboard_buttons']))?false:true;
            $core->blog->settings->dcckeditor->put('clipboard_buttons', $dcckeditor_clipboard_buttons, 'boolean');
        }

        dcPage::addSuccessNotice(__('The configuration has been updated.'));
        http::redirect($p_url);
    } catch(Exception $e) {
		$core->error->add($e->getMessage());
    }
}

include dirname(__FILE__).'/../tpl/index.tpl';
