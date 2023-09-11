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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Html;
use form;

class Manage extends Process
{
    private static bool $edit = false;

    public static function init(): bool
    {
        if (self::status(My::checkContext(My::MANAGE))) {
            App::backend()->blogroll = new Blogroll(App::blog());

            if (!empty($_REQUEST['edit']) && !empty($_REQUEST['id'])) {
                self::$edit = ManageEdit::init();
            } else {
                App::backend()->default_tab = '';
                App::backend()->link_title  = '';
                App::backend()->link_href   = '';
                App::backend()->link_desc   = '';
                App::backend()->link_lang   = '';
                App::backend()->cat_title   = '';
            }
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (self::$edit) {
            return ManageEdit::process();
        }

        if (!empty($_POST['import_links']) && !empty($_FILES['links_file'])) {
            // Import links - download file

            App::backend()->default_tab = 'import-links';

            try {
                Files::uploadStatus($_FILES['links_file']);
                $ifile = App::config()->cacheRoot() . '/' . md5(uniqid());
                if (!move_uploaded_file($_FILES['links_file']['tmp_name'], $ifile)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }

                try {
                    App::backend()->imported = UtilsImport::loadFile($ifile);
                    @unlink($ifile);
                } catch (Exception $e) {
                    @unlink($ifile);

                    throw $e;
                }

                if (empty(App::backend()->imported)) {
                    unset(App::backend()->imported);

                    throw new Exception(__('Nothing to import'));
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['import_links_do'])) {
            // Import links - import entries

            foreach ($_POST['entries'] as $idx) {
                App::backend()->link_title = Html::escapeHTML($_POST['title'][$idx]);
                App::backend()->link_href  = Html::escapeHTML($_POST['url'][$idx]);
                App::backend()->link_desc  = Html::escapeHTML($_POST['desc'][$idx]);

                try {
                    App::backend()->blogroll->addLink(App::backend()->link_title, App::backend()->link_href, App::backend()->link_desc, '');
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                    App::backend()->default_tab = 'import-links';
                }
            }

            Notices::addSuccessNotice(__('links have been successfully imported.'));
            My::redirect();
        }

        if (!empty($_POST['cancel_import'])) {
            // Cancel import

            App::error()->add(__('Import operation cancelled.'));
            App::backend()->default_tab = 'import-links';
        }

        if (!empty($_POST['add_link'])) {
            // Add link

            App::backend()->link_title = Html::escapeHTML($_POST['link_title']);
            App::backend()->link_href  = Html::escapeHTML($_POST['link_href']);
            App::backend()->link_desc  = Html::escapeHTML($_POST['link_desc']);
            App::backend()->link_lang  = Html::escapeHTML($_POST['link_lang']);

            try {
                App::backend()->blogroll->addLink(App::backend()->link_title, App::backend()->link_href, App::backend()->link_desc, App::backend()->link_lang);

                Notices::addSuccessNotice(__('Link has been successfully created.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
                App::backend()->default_tab = 'add-link';
            }
        }

        if (!empty($_POST['add_cat'])) {
            // Add category

            App::backend()->cat_title = Html::escapeHTML($_POST['cat_title']);

            try {
                App::backend()->blogroll->addCategory(App::backend()->cat_title);
                Notices::addSuccessNotice(__('category has been successfully created.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
                App::backend()->default_tab = 'add-cat';
            }
        }

        if (!empty($_POST['removeaction']) && !empty($_POST['remove'])) {
            // Delete link

            foreach ($_POST['remove'] as $k => $v) {
                try {
                    App::backend()->blogroll->delItem($v);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());

                    break;
                }
            }

            if (!App::error()->flag()) {
                Notices::addSuccessNotice(__('Items have been successfully removed.'));
                My::redirect();
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
                    App::backend()->blogroll->updateOrder($l, (string) $pos);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            if (!App::error()->flag()) {
                Notices::addSuccessNotice(__('Items order has been successfully updated'));
                My::redirect();
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (self::$edit) {
            ManageEdit::render();

            return;
        }

        // Get links
        $rs = null;

        try {
            $rs = App::backend()->blogroll->getLinks();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        $head = Page::jsConfirmClose('links-form', 'add-link-form', 'add-category-form');
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $head .= Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
                My::jsLoad('blogroll');
        }
        $head .= Page::jsPageTabs(App::backend()->default_tab);

        Page::openModule(My::name(), $head);

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                          => '',
            ]
        ) .
        Notices::getNotices() .

        '<div class="multi-part" id="main-list" title="' . My::name() . '">';

        if (!$rs->isEmpty()) {
            echo
            '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" id="links-form">' .
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
                    '<td colspan="5"><strong><a href="' . App::backend()->getPageURL() . '&amp;edit=1&amp;id=' . $rs->link_id . '">' .
                    Html::escapeHTML($rs->link_desc) . '</a></strong></td>';
                } else {
                    echo
                    '<td><a href="' . App::backend()->getPageURL() . '&amp;edit=1&amp;id=' . $rs->link_id . '">' .
                    Html::escapeHTML($rs->link_title) . '</a></td>' .
                    '<td>' . Html::escapeHTML($rs->link_desc) . '</td>' .
                    '<td>' . Html::escapeHTML($rs->link_href) . '</td>' .
                    '<td>' . Html::escapeHTML($rs->link_lang) . '</td>';
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
            form::hidden(['p'], My::id()) .
            App::nonce()->getFormNonce() .

            '<input type="submit" name="saveorder" value="' . __('Save order') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '<p class="col right"><input id="remove-action" type="submit" class="delete" name="removeaction" value="' . __('Delete selected links') . ' "onclick="return window.confirm(' . Html::escapeJS(__('Are you sure you want to delete selected links?')) . ');" /></p>' .
            '</div>' .
            '</form>';
        } else {
            echo
            '<div><p>' . __('The link list is empty.') . '</p></div>';
        }

        echo
        '</div>' .

        '<div class="multi-part clear" id="add-link" title="' . __('Add a link') . '">' .
        '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" id="add-link-form">' .
        '<h3>' . __('Add a new link') . '</h3>' .
        '<p class="col"><label for="link_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
        form::field('link_title', 30, 255, [
            'default'    => App::backend()->link_title,
            'extra_html' => 'required placeholder="' . __('Title') . '"',
        ]) .
        '</p>' .

        '<p class="col"><label for="link_href" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('URL:') . '</label> ' .
        form::field('link_href', 30, 255, [
            'default'    => App::backend()->link_href,
            'extra_html' => 'required placeholder="' . __('URL') . '"',
        ]) .
        '</p>' .

        '<p class="col"><label for="link_desc">' . __('Description:') . '</label> ' .
        form::field('link_desc', 30, 255, App::backend()->link_desc) .
        '</p>' .

        '<p class="col"><label for="link_lang">' . __('Language:') . '</label> ' .
        form::field('link_lang', 5, 5, App::backend()->link_lang) .
        '</p>' .
        '<p>' . form::hidden(['p'], 'blogroll') .
        App::nonce()->getFormNonce() .
        '<input type="submit" name="add_link" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '</p>' .
        '</form>' .
        '</div>' .

        '<div class="multi-part" id="add-cat" title="' . __('Add a category') . '">' .
        '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" id="add-category-form">' .
        '<h3>' . __('Add a new category') . '</h3>' .
        '<p><label for="cat_title" class=" classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
        form::field('cat_title', 30, 255, [
            'default'    => App::backend()->cat_title,
            'extra_html' => 'required placeholder="' . __('Title') . '"',
        ]) .
        '</p>' .
        '<p>' . form::hidden(['p'], My::id()) .
        App::nonce()->getFormNonce() .
        '<input type="submit" name="add_cat" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '</p>' .
        '</form>' .
        '</div>' .

        '<div class="multi-part" id="import-links" title="' . __('Import links') . '">';

        if (!isset(App::backend()->imported)) {
            echo
            '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" id="import-links-form" enctype="multipart/form-data">' .
            '<h3>' . __('Import links') . '</h3>' .
            '<p><label for="links_file" class=" classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('OPML or XBEL File:') . '</label> ' .
            '<input type="file" id="links_file" name="links_file" required /></p>' .
            '<p>' . form::hidden(['p'], My::id()) .
            App::nonce()->getFormNonce() .
            '<input type="submit" name="import_links" value="' . __('Import') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '</form>';
        } else {
            echo
            '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" id="import-links-form">' .
            '<h3>' . __('Import links') . '</h3>';
            if (empty(App::backend()->imported)) {
                echo
                '<p>' . __('Nothing to import') . '</p>';
            } else {
                echo
                '<table class="clear maximal"><tr>' .
                '<th colspan="2">' . __('Title') . '</th>' .
                '<th>' . __('Description') . '</th>' .
                '</tr>';

                $i = 0;
                foreach (App::backend()->imported as $entry) {
                    $url   = Html::escapeHTML($entry->link);
                    $title = Html::escapeHTML($entry->title);
                    $desc  = Html::escapeHTML($entry->desc);

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
                form::hidden(['p'], My::id()) .
                App::nonce()->getFormNonce() .
                '<input type="submit" name="cancel_import" value="' . __('Cancel') . '" />&nbsp;' .
                '<input type="submit" name="import_links_do" value="' . __('Import') . '" /></p>' .
                '</div>';
            }
            echo
            '</form>';
        }
        echo '</div>';

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
