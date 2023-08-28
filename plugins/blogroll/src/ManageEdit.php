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
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use form;

class ManageEdit extends Process
{
    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE) && !empty($_REQUEST['edit']) && !empty($_REQUEST['id']));

        if (self::status()) {
            Core::backend()->id = Html::escapeHTML($_REQUEST['id']);

            Core::backend()->rs = null;

            try {
                Core::backend()->rs = Core::backend()->blogroll->getLink(Core::backend()->id);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if (!dcCore::app()->error->flag() && Core::backend()->rs->isEmpty()) {
                Core::backend()->link_title = '';
                Core::backend()->link_href  = '';
                Core::backend()->link_desc  = '';
                Core::backend()->link_lang  = '';
                Core::backend()->link_xfn   = '';
                dcCore::app()->error->add(__('No such link or title'));
            } else {
                Core::backend()->link_title = Core::backend()->rs->link_title;
                Core::backend()->link_href  = Core::backend()->rs->link_href;
                Core::backend()->link_desc  = Core::backend()->rs->link_desc;
                Core::backend()->link_lang  = Core::backend()->rs->link_lang;
                Core::backend()->link_xfn   = Core::backend()->rs->link_xfn;
            }
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (isset(Core::backend()->rs) && !Core::backend()->rs->is_cat && !empty($_POST['edit_link'])) {
            // Update a link

            Core::backend()->link_title = Html::escapeHTML($_POST['link_title']);
            Core::backend()->link_href  = Html::escapeHTML($_POST['link_href']);
            Core::backend()->link_desc  = Html::escapeHTML($_POST['link_desc']);
            Core::backend()->link_lang  = Html::escapeHTML($_POST['link_lang']);

            Core::backend()->link_xfn = '';

            if (!empty($_POST['identity'])) {
                Core::backend()->link_xfn .= $_POST['identity'];
            } else {
                if (!empty($_POST['friendship'])) {
                    Core::backend()->link_xfn .= ' ' . $_POST['friendship'];
                }
                if (!empty($_POST['physical'])) {
                    Core::backend()->link_xfn .= ' met';
                }
                if (!empty($_POST['professional'])) {
                    Core::backend()->link_xfn .= ' ' . implode(' ', $_POST['professional']);
                }
                if (!empty($_POST['geographical'])) {
                    Core::backend()->link_xfn .= ' ' . $_POST['geographical'];
                }
                if (!empty($_POST['family'])) {
                    Core::backend()->link_xfn .= ' ' . $_POST['family'];
                }
                if (!empty($_POST['romantic'])) {
                    Core::backend()->link_xfn .= ' ' . implode(' ', $_POST['romantic']);
                }
            }

            try {
                Core::backend()->blogroll->updateLink(Core::backend()->id, Core::backend()->link_title, Core::backend()->link_href, Core::backend()->link_desc, Core::backend()->link_lang, trim((string) Core::backend()->link_xfn));
                Notices::addSuccessNotice(__('Link has been successfully updated'));
                My::redirect([
                    'edit' => 1,
                    'id'   => Core::backend()->id,
                ]);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (isset(Core::backend()->rs) && Core::backend()->rs->is_cat && !empty($_POST['edit_cat'])) {
            // Update a category

            Core::backend()->link_desc = Html::escapeHTML($_POST['link_desc']);

            try {
                Core::backend()->blogroll->updateCategory(Core::backend()->id, Core::backend()->link_desc);
                Notices::addSuccessNotice(__('Category has been successfully updated'));
                My::redirect([
                    'edit' => 1,
                    'id'   => Core::backend()->id,
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
        $links      = Core::backend()->blogroll->getLangs(['order' => 'asc']);
        $lang_combo = Combos::getLangsCombo($links, true);

        Page::openModule(My::name());

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(Core::blog()->name) => '',
                My::name()                                  => Core::backend()->getPageURL(),
            ]
        ) .
        Notices::getNotices() .
        '<p><a class="back" href="' . Core::backend()->getPageURL() . '">' . __('Return to blogroll') . '</a></p>';

        if (isset(Core::backend()->rs)) {
            if (Core::backend()->rs->is_cat) {
                echo
                '<form action="' . Core::backend()->getPageURL() . '" method="post">' .
                '<h3>' . __('Edit category') . '</h3>' .

                '<p><label for="link_desc" class="required classic"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
                form::field('link_desc', 30, 255, [
                    'default'    => Html::escapeHTML(Core::backend()->link_desc),
                    'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                ]) .

                form::hidden('edit', 1) .
                form::hidden('id', Core::backend()->id) .
                Core::nonce()->getFormNonce() .
                '<input type="submit" name="edit_cat" value="' . __('Save') . '"/></p>' .
                '</form>';
            } else {
                echo
                '<form action="' . Core::backend()->url->get('admin.plugin') . '" method="post" class="two-cols fieldset">' .

                '<div class="col30 first-col">' .
                '<h3>' . __('Edit link') . '</h3>' .

                '<p><label for="link_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
                form::field('link_title', 30, 255, [
                    'default'    => Html::escapeHTML(Core::backend()->link_title),
                    'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                ]) .
                '</p>' .

                '<p><label for="link_href" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('URL:') . '</label> ' .
                form::url('link_href', [
                    'size'       => 30,
                    'default'    => Html::escapeHTML(Core::backend()->link_href),
                    'extra_html' => 'required placeholder="' . __('URL') . '"',
                ]) .
                '</p>' .

                '<p><label for="link_desc">' . __('Description:') . '</label> ' .
                form::field(
                    'link_desc',
                    30,
                    255,
                    [
                        'default'    => Html::escapeHTML(Core::backend()->link_desc),
                        'extra_html' => 'lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                    ]
                ) . '</p>' .

                '<p><label for="link_lang">' . __('Language:') . '</label> ' .
                form::combo('link_lang', $lang_combo, Core::backend()->link_lang) .
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
                form::checkbox(['identity'], 'me', (Core::backend()->link_xfn == 'me')) . ' ' .
                __('_xfn_Another link for myself') . '</label></p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Friendship') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'contact',
                    strpos(Core::backend()->link_xfn, 'contact') !== false
                ) . __('_xfn_Contact') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'acquaintance',
                    strpos(Core::backend()->link_xfn, 'acquaintance') !== false
                ) . __('_xfn_Acquaintance') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'friend',
                    strpos(Core::backend()->link_xfn, 'friend') !== false
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
                    strpos(Core::backend()->link_xfn, 'met') !== false
                ) . __('_xfn_Met') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Professional') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::checkbox(
                    ['professional[]'],
                    'co-worker',
                    strpos(Core::backend()->link_xfn, 'co-worker') !== false
                ) . __('_xfn_Co-worker') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['professional[]'],
                    'colleague',
                    strpos(Core::backend()->link_xfn, 'colleague') !== false
                ) . __('_xfn_Colleague') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Geographical') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::radio(
                    ['geographical'],
                    'co-resident',
                    strpos(Core::backend()->link_xfn, 'co-resident') !== false
                ) . __('_xfn_Co-resident') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['geographical'],
                    'neighbor',
                    strpos(Core::backend()->link_xfn, 'neighbor') !== false
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
                    strpos(Core::backend()->link_xfn, 'child') !== false
                ) . __('_xfn_Child') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'parent',
                    strpos(Core::backend()->link_xfn, 'parent') !== false
                ) . __('_xfn_Parent') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'sibling',
                    strpos(Core::backend()->link_xfn, 'sibling') !== false
                ) . __('_xfn_Sibling') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'spouse',
                    strpos(Core::backend()->link_xfn, 'spouse') !== false
                ) . __('_xfn_Spouse') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'kin',
                    strpos(Core::backend()->link_xfn, 'kin') !== false
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
                    strpos(Core::backend()->link_xfn, 'muse') !== false
                ) . __('_xfn_Muse') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'crush',
                    strpos(Core::backend()->link_xfn, 'crush') !== false
                ) . __('_xfn_Crush') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'date',
                    strpos(Core::backend()->link_xfn, 'date') !== false
                ) . __('_xfn_Date') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'sweetheart',
                    strpos(Core::backend()->link_xfn, 'sweetheart') !== false
                ) . __('_xfn_Sweetheart') . '</label> ' .
                '</p></td>' .
                '</tr>' .
                '</table></div>' .

                '</div>' .
                '<p class="clear">' . form::hidden('p', My::id()) .
                form::hidden('edit', 1) .
                form::hidden('id', Core::backend()->id) .
                Core::nonce()->getFormNonce() .
                '<input type="submit" name="edit_link" value="' . __('Save') . '"/></p>' .
                '</form>';
            }
        }

        Page::closeModule();
    }
}
