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
use dcAdminCombos;
use dcCore;
use dcPage;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use form;

class ManageEdit extends Process
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE) && !empty($_REQUEST['edit']) && !empty($_REQUEST['id']);

        if (static::$init) {
            dcCore::app()->admin->id = Html::escapeHTML($_REQUEST['id']);

            dcCore::app()->admin->rs = null;

            try {
                dcCore::app()->admin->rs = dcCore::app()->admin->blogroll->getLink(dcCore::app()->admin->id);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if (!dcCore::app()->error->flag() && dcCore::app()->admin->rs->isEmpty()) {
                dcCore::app()->admin->link_title = '';
                dcCore::app()->admin->link_href  = '';
                dcCore::app()->admin->link_desc  = '';
                dcCore::app()->admin->link_lang  = '';
                dcCore::app()->admin->link_xfn   = '';
                dcCore::app()->error->add(__('No such link or title'));
            } else {
                dcCore::app()->admin->link_title = dcCore::app()->admin->rs->link_title;
                dcCore::app()->admin->link_href  = dcCore::app()->admin->rs->link_href;
                dcCore::app()->admin->link_desc  = dcCore::app()->admin->rs->link_desc;
                dcCore::app()->admin->link_lang  = dcCore::app()->admin->rs->link_lang;
                dcCore::app()->admin->link_xfn   = dcCore::app()->admin->rs->link_xfn;
            }

            static::$init = true;
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (isset(dcCore::app()->admin->rs) && !dcCore::app()->admin->rs->is_cat && !empty($_POST['edit_link'])) {
            // Update a link

            dcCore::app()->admin->link_title = Html::escapeHTML($_POST['link_title']);
            dcCore::app()->admin->link_href  = Html::escapeHTML($_POST['link_href']);
            dcCore::app()->admin->link_desc  = Html::escapeHTML($_POST['link_desc']);
            dcCore::app()->admin->link_lang  = Html::escapeHTML($_POST['link_lang']);

            dcCore::app()->admin->link_xfn = '';

            if (!empty($_POST['identity'])) {
                dcCore::app()->admin->link_xfn .= $_POST['identity'];
            } else {
                if (!empty($_POST['friendship'])) {
                    dcCore::app()->admin->link_xfn .= ' ' . $_POST['friendship'];
                }
                if (!empty($_POST['physical'])) {
                    dcCore::app()->admin->link_xfn .= ' met';
                }
                if (!empty($_POST['professional'])) {
                    dcCore::app()->admin->link_xfn .= ' ' . implode(' ', $_POST['professional']);
                }
                if (!empty($_POST['geographical'])) {
                    dcCore::app()->admin->link_xfn .= ' ' . $_POST['geographical'];
                }
                if (!empty($_POST['family'])) {
                    dcCore::app()->admin->link_xfn .= ' ' . $_POST['family'];
                }
                if (!empty($_POST['romantic'])) {
                    dcCore::app()->admin->link_xfn .= ' ' . implode(' ', $_POST['romantic']);
                }
            }

            try {
                dcCore::app()->admin->blogroll->updateLink(dcCore::app()->admin->id, dcCore::app()->admin->link_title, dcCore::app()->admin->link_href, dcCore::app()->admin->link_desc, dcCore::app()->admin->link_lang, trim((string) dcCore::app()->admin->link_xfn));
                dcPage::addSuccessNotice(__('Link has been successfully updated'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [
                    'edit' => 1,
                    'id'   => dcCore::app()->admin->id,
                ]);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (isset(dcCore::app()->admin->rs) && dcCore::app()->admin->rs->is_cat && !empty($_POST['edit_cat'])) {
            // Update a category

            dcCore::app()->admin->link_desc = Html::escapeHTML($_POST['link_desc']);

            try {
                dcCore::app()->admin->blogroll->updateCategory(dcCore::app()->admin->id, dcCore::app()->admin->link_desc);
                dcPage::addSuccessNotice(__('Category has been successfully updated'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [
                    'edit' => 1,
                    'id'   => dcCore::app()->admin->id,
                ]);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        # Languages combo
        $links      = dcCore::app()->admin->blogroll->getLangs(['order' => 'asc']);
        $lang_combo = dcAdminCombos::getLangsCombo($links, true);

        dcPage::openModule(My::name());

        echo
        dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                My::name()                                  => dcCore::app()->admin->getPageURL(),
            ]
        ) .
        dcPage::notices() .
        '<p><a class="back" href="' . dcCore::app()->admin->getPageURL() . '">' . __('Return to blogroll') . '</a></p>';

        if (isset(dcCore::app()->admin->rs)) {
            if (dcCore::app()->admin->rs->is_cat) {
                echo
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .
                '<h3>' . __('Edit category') . '</h3>' .

                '<p><label for="link_desc" class="required classic"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
                form::field('link_desc', 30, 255, [
                    'default'    => Html::escapeHTML(dcCore::app()->admin->link_desc),
                    'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                ]) .

                form::hidden('edit', 1) .
                form::hidden('id', dcCore::app()->admin->id) .
                dcCore::app()->formNonce() .
                '<input type="submit" name="edit_cat" value="' . __('Save') . '"/></p>' .
                '</form>';
            } else {
                echo
                '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" class="two-cols fieldset">' .

                '<div class="col30 first-col">' .
                '<h3>' . __('Edit link') . '</h3>' .

                '<p><label for="link_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
                form::field('link_title', 30, 255, [
                    'default'    => Html::escapeHTML(dcCore::app()->admin->link_title),
                    'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                ]) .
                '</p>' .

                '<p><label for="link_href" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('URL:') . '</label> ' .
                form::url('link_href', [
                    'size'       => 30,
                    'default'    => Html::escapeHTML(dcCore::app()->admin->link_href),
                    'extra_html' => 'required placeholder="' . __('URL') . '"',
                ]) .
                '</p>' .

                '<p><label for="link_desc">' . __('Description:') . '</label> ' .
                form::field(
                    'link_desc',
                    30,
                    255,
                    [
                        'default'    => Html::escapeHTML(dcCore::app()->admin->link_desc),
                        'extra_html' => 'lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                    ]
                ) . '</p>' .

                '<p><label for="link_lang">' . __('Language:') . '</label> ' .
                form::combo('link_lang', $lang_combo, dcCore::app()->admin->link_lang) .
                '</p>' .

                '</div>' .

                // XFN nightmare
                '<div class="col70 last-col">' .
                '<h3>' . __('XFN information') . '</h3>' .
                '<p class="clear form-note">' . __('More information on <a href="https://en.wikipedia.org/wiki/XHTML_Friends_Network">Wikipedia</a> website') . '</p>' .

                '<div class="table-outer">' .
                '<table class="noborder">' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Me') . '</th>' .
                '<td><p>' . '<label class="classic">' .
                form::checkbox(['identity'], 'me', (dcCore::app()->admin->link_xfn == 'me')) . ' ' .
                __('_xfn_Another link for myself') . '</label></p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Friendship') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'contact',
                    strpos(dcCore::app()->admin->link_xfn, 'contact') !== false
                ) . __('_xfn_Contact') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'acquaintance',
                    strpos(dcCore::app()->admin->link_xfn, 'acquaintance') !== false
                ) . __('_xfn_Acquaintance') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'friend',
                    strpos(dcCore::app()->admin->link_xfn, 'friend') !== false
                ) . __('_xfn_Friend') . '</label> ' .
                '<label class="classic">' . form::radio(['friendship'], '') . __('None') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Physical') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::checkbox(
                    ['physical'],
                    'met',
                    strpos(dcCore::app()->admin->link_xfn, 'met') !== false
                ) . __('_xfn_Met') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Professional') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::checkbox(
                    ['professional[]'],
                    'co-worker',
                    strpos(dcCore::app()->admin->link_xfn, 'co-worker') !== false
                ) . __('_xfn_Co-worker') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['professional[]'],
                    'colleague',
                    strpos(dcCore::app()->admin->link_xfn, 'colleague') !== false
                ) . __('_xfn_Colleague') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Geographical') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::radio(
                    ['geographical'],
                    'co-resident',
                    strpos(dcCore::app()->admin->link_xfn, 'co-resident') !== false
                ) . __('_xfn_Co-resident') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['geographical'],
                    'neighbor',
                    strpos(dcCore::app()->admin->link_xfn, 'neighbor') !== false
                ) . __('_xfn_Neighbor') . '</label> ' .
                '<label class="classic">' . form::radio(['geographical'], '') . __('None') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Family') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'child',
                    strpos(dcCore::app()->admin->link_xfn, 'child') !== false
                ) . __('_xfn_Child') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'parent',
                    strpos(dcCore::app()->admin->link_xfn, 'parent') !== false
                ) . __('_xfn_Parent') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'sibling',
                    strpos(dcCore::app()->admin->link_xfn, 'sibling') !== false
                ) . __('_xfn_Sibling') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'spouse',
                    strpos(dcCore::app()->admin->link_xfn, 'spouse') !== false
                ) . __('_xfn_Spouse') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'kin',
                    strpos(dcCore::app()->admin->link_xfn, 'kin') !== false
                ) . __('_xfn_Kin') . '</label> ' .
                '<label class="classic">' . form::radio(['family'], '') . __('None') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Romantic') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'muse',
                    strpos(dcCore::app()->admin->link_xfn, 'muse') !== false
                ) . __('_xfn_Muse') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'crush',
                    strpos(dcCore::app()->admin->link_xfn, 'crush') !== false
                ) . __('_xfn_Crush') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'date',
                    strpos(dcCore::app()->admin->link_xfn, 'date') !== false
                ) . __('_xfn_Date') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'sweetheart',
                    strpos(dcCore::app()->admin->link_xfn, 'sweetheart') !== false
                ) . __('_xfn_Sweetheart') . '</label> ' .
                '</p></td>' .
                '</tr>' .
                '</table></div>' .

                '</div>' .
                '<p class="clear">' . form::hidden('p', My::id()) .
                form::hidden('edit', 1) .
                form::hidden('id', dcCore::app()->admin->id) .
                dcCore::app()->formNonce() .
                '<input type="submit" name="edit_link" value="' . __('Save') . '"/></p>' .
                '</form>';
            }
        }

        dcPage::closeModule();
    }
}
