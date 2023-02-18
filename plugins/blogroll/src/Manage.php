<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Exception;
use dcCore;
use dcPage;
use dcNsProcess;
use initBlogroll;
use files;
use form;
use html;
use http;

class Manage extends dcNsProcess
{
    private static $edit = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcPage::check(dcCore::app()->auth->makePermissions([
                initBlogroll::PERMISSION_BLOGROLL,
            ]));

            dcCore::app()->admin->blogroll = new Blogroll(dcCore::app()->blog);

            if (!empty($_REQUEST['edit']) && !empty($_REQUEST['id'])) {
                self::$edit = ManageEdit::init();
            } else {
                dcCore::app()->admin->default_tab = '';
                dcCore::app()->admin->link_title  = '';
                dcCore::app()->admin->link_href   = '';
                dcCore::app()->admin->link_desc   = '';
                dcCore::app()->admin->link_lang   = '';
                dcCore::app()->admin->cat_title   = '';
            }

            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        if (self::$edit) {
            return ManageEdit::process();
        }

        if (!empty($_POST['import_links']) && !empty($_FILES['links_file'])) {
            // Import links - download file

            dcCore::app()->admin->default_tab = 'import-links';

            try {
                files::uploadStatus($_FILES['links_file']);
                $ifile = DC_TPL_CACHE . '/' . md5(uniqid());
                if (!move_uploaded_file($_FILES['links_file']['tmp_name'], $ifile)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }

                try {
                    dcCore::app()->admin->imported = UtilsImport::loadFile($ifile);
                    @unlink($ifile);
                } catch (Exception $e) {
                    @unlink($ifile);

                    throw $e;
                }

                if (empty(dcCore::app()->admin->imported)) {
                    unset(dcCore::app()->admin->imported);

                    throw new Exception(__('Nothing to import'));
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['import_links_do'])) {
            // Import links - import entries

            foreach ($_POST['entries'] as $idx) {
                dcCore::app()->admin->link_title = html::escapeHTML($_POST['title'][$idx]);
                dcCore::app()->admin->link_href  = html::escapeHTML($_POST['url'][$idx]);
                dcCore::app()->admin->link_desc  = html::escapeHTML($_POST['desc'][$idx]);

                try {
                    dcCore::app()->admin->blogroll->addLink(dcCore::app()->admin->link_title, dcCore::app()->admin->link_href, dcCore::app()->admin->link_desc, '');
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                    dcCore::app()->admin->default_tab = 'import-links';
                }
            }

            dcPage::addSuccessNotice(__('links have been successfully imported.'));
            http::redirect(dcCore::app()->admin->getPageURL());
        }

        if (!empty($_POST['cancel_import'])) {
            // Cancel import

            dcCore::app()->error->add(__('Import operation cancelled.'));
            dcCore::app()->admin->default_tab = 'import-links';
        }

        if (!empty($_POST['add_link'])) {
            // Add link

            dcCore::app()->admin->link_title = html::escapeHTML($_POST['link_title']);
            dcCore::app()->admin->link_href  = html::escapeHTML($_POST['link_href']);
            dcCore::app()->admin->link_desc  = html::escapeHTML($_POST['link_desc']);
            dcCore::app()->admin->link_lang  = html::escapeHTML($_POST['link_lang']);

            try {
                dcCore::app()->admin->blogroll->addLink(dcCore::app()->admin->link_title, dcCore::app()->admin->link_href, dcCore::app()->admin->link_desc, dcCore::app()->admin->link_lang);

                dcPage::addSuccessNotice(__('Link has been successfully created.'));
                http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
                dcCore::app()->admin->default_tab = 'add-link';
            }
        }

        if (!empty($_POST['add_cat'])) {
            // Add category

            dcCore::app()->admin->cat_title = html::escapeHTML($_POST['cat_title']);

            try {
                dcCore::app()->admin->blogroll->addCategory(dcCore::app()->admin->cat_title);
                dcPage::addSuccessNotice(__('category has been successfully created.'));
                http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
                dcCore::app()->admin->default_tab = 'add-cat';
            }
        }

        if (!empty($_POST['removeaction']) && !empty($_POST['remove'])) {
            // Delete link

            foreach ($_POST['remove'] as $k => $v) {
                try {
                    dcCore::app()->admin->blogroll->delItem($v);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());

                    break;
                }
            }

            if (!dcCore::app()->error->flag()) {
                dcPage::addSuccessNotice(__('Items have been successfully removed.'));
                http::redirect(dcCore::app()->admin->getPageURL());
            }
        }

        // Prepare order links

        $order = [];
        if (empty($_POST['links_order']) && !empty($_POST['order'])) {
            $order = $_POST['order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['links_order'])) {
            $order = explode(',', $_POST['links_order']);
        }

        if (!empty($_POST['saveorder']) && !empty($order)) {
            // Order links

            foreach ($order as $pos => $l) {
                $pos = ((int) $pos) + 1;

                try {
                    dcCore::app()->admin->blogroll->updateOrder($l, (string) $pos);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            if (!dcCore::app()->error->flag()) {
                dcPage::addSuccessNotice(__('Items order has been successfully updated'));
                http::redirect(dcCore::app()->admin->getPageURL());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::$init) {
            return;
        }

        if (self::$edit) {
            ManageEdit::render();

            return;
        }

        // Get links
        $rs = null;

        try {
            $rs = dcCore::app()->admin->blogroll->getLinks();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        echo
        '<html>' .
        '<head>' .
        '<title>' . __('Blogroll') . '</title>' .
        dcPage::jsConfirmClose('links-form', 'add-link-form', 'add-category-form');

        if (!dcCore::app()->auth->user_prefs->accessibility->nodragdrop) {
            echo
            dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
            dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            dcPage::jsModuleLoad('blogroll/js/blogroll.js');
        }
        echo
        dcPage::jsPageTabs(dcCore::app()->admin->default_tab) .
        '</head>' .
        '<body>' .
        dcPage::breadcrumb(
            [
                html::escapeHTML(dcCore::app()->blog->name) => '',
                __('Blogroll')                              => '',
            ]
        ) .
        dcPage::notices() .

        '<div class="multi-part" id="main-list" title="' . __('Blogroll') . '">';

        if (!$rs->isEmpty()) {
            echo
            '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="links-form">' .
            '<div class="table-outer">' .
            '<table class="dragable">' .
            '<thead>' .
            '<tr>' .
            '<th colspan="3">' . __('Title') . '</th>' .
            '<th>' . __('Description') . '</th>' .
            '<th>' . __('URL') . '</th>' .
            '<th>' . __('Lang') . '</th>' .
            '</tr>' .
            '</thead>' .
            '<tbody id="links-list">';

            while ($rs->fetch()) {
                $position = (string) ($rs->index() + 1);

                echo
                '<tr class="line" id="l_' . $rs->link_id . '">' .
                '<td class="handle minimal">' . form::number(['order[' . $rs->link_id . ']'], [
                    'min'        => 1,
                    'max'        => $rs->count(),
                    'default'    => $position,
                    'class'      => 'position',
                    'extra_html' => 'title="' . __('position') . '"',
                ]) .
                '</td>' .
                '<td class="minimal">' . form::checkbox(
                    ['remove[]'],
                    $rs->link_id,
                    [
                        'extra_html' => 'title="' . __('select this link') . '"',
                    ]
                ) . '</td>';

                if ($rs->is_cat) {
                    echo
                    '<td colspan="5"><strong><a href="' . dcCore::app()->admin->getPageURL() . '&amp;edit=1&amp;id=' . $rs->link_id . '">' .
                    html::escapeHTML($rs->link_desc) . '</a></strong></td>';
                } else {
                    echo
                    '<td><a href="' . dcCore::app()->admin->getPageURL() . '&amp;edit=1&amp;id=' . $rs->link_id . '">' .
                    html::escapeHTML($rs->link_title) . '</a></td>' .
                    '<td>' . html::escapeHTML($rs->link_desc) . '</td>' .
                    '<td>' . html::escapeHTML($rs->link_href) . '</td>' .
                    '<td>' . html::escapeHTML($rs->link_lang) . '</td>';
                }
                echo
                '</tr>';
            }

            echo
            '</tbody>' .
            '</table></div>' .

            '<div class="two-cols">' .
            '<p class="col">' .

            form::hidden('links_order', '') .
            form::hidden(['p'], 'blogroll') .
            dcCore::app()->formNonce() .

            '<input type="submit" name="saveorder" value="' . __('Save order') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '<p class="col right"><input id="remove-action" type="submit" class="delete" name="removeaction" value="' . __('Delete selected links') . ' "onclick="return window.confirm(' . html::escapeJS(__('Are you sure you want to delete selected links?')) . ');" /></p>' .
            '</div>' .
            '</form>';
        } else {
            echo
            '<div><p>' . __('The link list is empty.') . '</p></div>';
        }

        echo
        '</div>' .

        '<div class="multi-part clear" id="add-link" title="' . __('Add a link') . '">' .
        '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="add-link-form">' .
        '<h3>' . __('Add a new link') . '</h3>' .
        '<p class="col"><label for="link_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
        form::field('link_title', 30, 255, [
            'default'    => dcCore::app()->admin->link_title,
            'extra_html' => 'required placeholder="' . __('Title') . '"',
        ]) .
        '</p>' .

        '<p class="col"><label for="link_href" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('URL:') . '</label> ' .
        form::field('link_href', 30, 255, [
            'default'    => dcCore::app()->admin->link_href,
            'extra_html' => 'required placeholder="' . __('URL') . '"',
        ]) .
        '</p>' .

        '<p class="col"><label for="link_desc">' . __('Description:') . '</label> ' .
        form::field('link_desc', 30, 255, dcCore::app()->admin->link_desc) .
        '</p>' .

        '<p class="col"><label for="link_lang">' . __('Language:') . '</label> ' .
        form::field('link_lang', 5, 5, dcCore::app()->admin->link_lang) .
        '</p>' .
        '<p>' . form::hidden(['p'], 'blogroll') .
        dcCore::app()->formNonce() .
        '<input type="submit" name="add_link" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '</p>' .
        '</form>' .
        '</div>' .

        '<div class="multi-part" id="add-cat" title="' . __('Add a category') . '">' .
        '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="add-category-form">' .
        '<h3>' . __('Add a new category') . '</h3>' .
        '<p><label for="cat_title" class=" classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
        form::field('cat_title', 30, 255, [
            'default'    => dcCore::app()->admin->cat_title,
            'extra_html' => 'required placeholder="' . __('Title') . '"',
        ]) .
        '</p>' .
        '<p>' . form::hidden(['p'], 'blogroll') .
        dcCore::app()->formNonce() .
        '<input type="submit" name="add_cat" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '</p>' .
        '</form>' .
        '</div>' .

        '<div class="multi-part" id="import-links" title="' . __('Import links') . '">';

        if (!isset(dcCore::app()->admin->imported)) {
            echo
            '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="import-links-form" enctype="multipart/form-data">' .
            '<h3>' . __('Import links') . '</h3>' .
            '<p><label for="links_file" class=" classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('OPML or XBEL File:') . '</label> ' .
            '<input type="file" id="links_file" name="links_file" required /></p>' .
            '<p>' . form::hidden(['p'], 'blogroll') .
            dcCore::app()->formNonce() .
            '<input type="submit" name="import_links" value="' . __('Import') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '</form>';
        } else {
            echo
            '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="import-links-form">' .
            '<h3>' . __('Import links') . '</h3>';
            if (empty(dcCore::app()->admin->imported)) {
                echo
                '<p>' . __('Nothing to import') . '</p>';
            } else {
                echo
                '<table class="clear maximal"><tr>' .
                '<th colspan="2">' . __('Title') . '</th>' .
                '<th>' . __('Description') . '</th>' .
                '</tr>';

                $i = 0;
                foreach (dcCore::app()->admin->imported as $entry) {
                    $url   = html::escapeHTML($entry->link);
                    $title = html::escapeHTML($entry->title);
                    $desc  = html::escapeHTML($entry->desc);

                    echo
                    '<tr><td>' . form::checkbox(['entries[]'], $i) . '</td>' .
                    '<td nowrap><a href="' . $url . '">' . $title . '</a>' .
                    '<input type="hidden" name="url[' . $i . ']" value="' . $url . '" />' .
                    '<input type="hidden" name="title[' . $i . ']" value="' . $title . '" />' .
                    '</td>' .
                    '<td>' . $desc .
                    '<input type="hidden" name="desc[' . $i . ']" value="' . $desc . '" />' .
                    '</td></tr>' . "\n";
                    $i++;
                }
                echo
                '</table>' .
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right">' .
                form::hidden(['p'], 'blogroll') .
                dcCore::app()->formNonce() .
                '<input type="submit" name="cancel_import" value="' . __('Cancel') . '" />&nbsp;' .
                '<input type="submit" name="import_links_do" value="' . __('Import') . '" /></p>' .
                '</div>';
            }
            echo
            '</form>';
        }
        echo '</div>';

        dcPage::helpBlock('blogroll');

        echo
        '</body>' .
        '</html>';
    }
}
