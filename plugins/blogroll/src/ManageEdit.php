<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Exception;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use form;

/**
 * @brief   The module manage blogroll process.
 * @ingroup blogroll
 */
class ManageEdit extends Process
{
    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE) && !empty($_REQUEST['edit']) && !empty($_REQUEST['id']));

        if (self::status()) {
            App::backend()->id = Html::escapeHTML($_REQUEST['id']);

            App::backend()->rs = null;

            try {
                App::backend()->rs = App::backend()->blogroll->getLink(App::backend()->id);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            if (!App::error()->flag() && App::backend()->rs->isEmpty()) {
                App::backend()->link_title = '';
                App::backend()->link_href  = '';
                App::backend()->link_desc  = '';
                App::backend()->link_lang  = '';
                App::backend()->link_xfn   = '';
                App::error()->add(__('No such link or title'));
            } else {
                App::backend()->link_title = App::backend()->rs->link_title;
                App::backend()->link_href  = App::backend()->rs->link_href;
                App::backend()->link_desc  = App::backend()->rs->link_desc;
                App::backend()->link_lang  = App::backend()->rs->link_lang;
                App::backend()->link_xfn   = App::backend()->rs->link_xfn;
            }
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (isset(App::backend()->rs) && !App::backend()->rs->is_cat && !empty($_POST['edit_link'])) {
            // Update a link

            App::backend()->link_title = Html::escapeHTML($_POST['link_title']);
            App::backend()->link_href  = Html::escapeHTML($_POST['link_href']);
            App::backend()->link_desc  = Html::escapeHTML($_POST['link_desc']);
            App::backend()->link_lang  = Html::escapeHTML($_POST['link_lang']);

            App::backend()->link_xfn = '';

            if (!empty($_POST['identity'])) {
                App::backend()->link_xfn .= $_POST['identity'];
            } else {
                if (!empty($_POST['friendship'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['friendship'];
                }
                if (!empty($_POST['physical'])) {
                    App::backend()->link_xfn .= ' met';
                }
                if (!empty($_POST['professional'])) {
                    App::backend()->link_xfn .= ' ' . implode(' ', $_POST['professional']);
                }
                if (!empty($_POST['geographical'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['geographical'];
                }
                if (!empty($_POST['family'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['family'];
                }
                if (!empty($_POST['romantic'])) {
                    App::backend()->link_xfn .= ' ' . implode(' ', $_POST['romantic']);
                }
            }

            try {
                App::backend()->blogroll->updateLink(App::backend()->id, App::backend()->link_title, App::backend()->link_href, App::backend()->link_desc, App::backend()->link_lang, trim((string) App::backend()->link_xfn));
                Notices::addSuccessNotice(__('Link has been successfully updated'));
                My::redirect([
                    'edit' => 1,
                    'id'   => App::backend()->id,
                ]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (isset(App::backend()->rs) && App::backend()->rs->is_cat && !empty($_POST['edit_cat'])) {
            // Update a category

            App::backend()->link_desc = Html::escapeHTML($_POST['link_desc']);

            try {
                App::backend()->blogroll->updateCategory(App::backend()->id, App::backend()->link_desc);
                Notices::addSuccessNotice(__('Category has been successfully updated'));
                My::redirect([
                    'edit' => 1,
                    'id'   => App::backend()->id,
                ]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        # Languages combo
        $links      = App::backend()->blogroll->getLangs(['order' => 'asc']);
        $lang_combo = Combos::getLangsCombo($links, true);

        Page::openModule(My::name());

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => App::backend()->getPageURL(),
            ]
        ) .
        Notices::getNotices() .
        '<p><a class="back" href="' . App::backend()->getPageURL() . '">' . __('Return to blogroll') . '</a></p>';

        if (isset(App::backend()->rs)) {
            if (App::backend()->rs->is_cat) {
                echo
                '<form action="' . App::backend()->getPageURL() . '" method="post">' .
                '<h3>' . __('Edit category') . '</h3>' .

                '<p><label for="link_desc" class="required classic"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
                form::field('link_desc', 30, 255, [
                    'default'    => Html::escapeHTML(App::backend()->link_desc),
                    'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]) .

                form::hidden('edit', 1) .
                form::hidden('id', App::backend()->id) .
                App::nonce()->getFormNonce() .
                '<input type="submit" name="edit_cat" value="' . __('Save') . '"/></p>' .
                '</form>';
            } else {
                echo
                '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" class="two-cols fieldset">' .

                '<div class="col30 first-col">' .
                '<h3>' . __('Edit link') . '</h3>' .

                '<p><label for="link_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
                form::field('link_title', 30, 255, [
                    'default'    => Html::escapeHTML(App::backend()->link_title),
                    'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]) .
                '</p>' .

                '<p><label for="link_href" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('URL:') . '</label> ' .
                form::url('link_href', [
                    'size'       => 30,
                    'default'    => Html::escapeHTML(App::backend()->link_href),
                    'extra_html' => 'required placeholder="' . __('URL') . '"',
                ]) .
                '</p>' .

                '<p><label for="link_desc">' . __('Description:') . '</label> ' .
                form::field(
                    'link_desc',
                    30,
                    255,
                    [
                        'default'    => Html::escapeHTML(App::backend()->link_desc),
                        'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                    ]
                ) . '</p>' .

                '<p><label for="link_lang">' . __('Language:') . '</label> ' .
                form::combo('link_lang', $lang_combo, App::backend()->link_lang) .
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
                form::checkbox(['identity'], 'me', (App::backend()->link_xfn == 'me')) . ' ' .
                __('_xfn_Another link for myself') . '</label></p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Friendship') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'contact',
                    str_contains(App::backend()->link_xfn, 'contact')
                ) . __('_xfn_Contact') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'acquaintance',
                    str_contains(App::backend()->link_xfn, 'acquaintance')
                ) . __('_xfn_Acquaintance') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['friendship'],
                    'friend',
                    str_contains(App::backend()->link_xfn, 'friend')
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
                    str_contains(App::backend()->link_xfn, 'met')
                ) . __('_xfn_Met') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Professional') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::checkbox(
                    ['professional[]'],
                    'co-worker',
                    str_contains(App::backend()->link_xfn, 'co-worker')
                ) . __('_xfn_Co-worker') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['professional[]'],
                    'colleague',
                    str_contains(App::backend()->link_xfn, 'colleague')
                ) . __('_xfn_Colleague') . '</label>' .
                '</p></td>' .
                '</tr>' .

                '<tr class="line">' .
                '<th>' . __('_xfn_Geographical') . '</th>' .
                '<td><p>' .
                '<label class="classic">' . form::radio(
                    ['geographical'],
                    'co-resident',
                    str_contains(App::backend()->link_xfn, 'co-resident')
                ) . __('_xfn_Co-resident') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['geographical'],
                    'neighbor',
                    str_contains(App::backend()->link_xfn, 'neighbor')
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
                    str_contains(App::backend()->link_xfn, 'child')
                ) . __('_xfn_Child') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'parent',
                    str_contains(App::backend()->link_xfn, 'parent')
                ) . __('_xfn_Parent') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'sibling',
                    str_contains(App::backend()->link_xfn, 'sibling')
                ) . __('_xfn_Sibling') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'spouse',
                    str_contains(App::backend()->link_xfn, 'spouse')
                ) . __('_xfn_Spouse') . '</label> ' .
                '<label class="classic">' . form::radio(
                    ['family'],
                    'kin',
                    str_contains(App::backend()->link_xfn, 'kin')
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
                    str_contains(App::backend()->link_xfn, 'muse')
                ) . __('_xfn_Muse') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'crush',
                    str_contains(App::backend()->link_xfn, 'crush')
                ) . __('_xfn_Crush') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'date',
                    str_contains(App::backend()->link_xfn, 'date')
                ) . __('_xfn_Date') . '</label> ' .
                '<label class="classic">' . form::checkbox(
                    ['romantic[]'],
                    'sweetheart',
                    str_contains(App::backend()->link_xfn, 'sweetheart')
                ) . __('_xfn_Sweetheart') . '</label> ' .
                '</p></td>' .
                '</tr>' .
                '</table></div>' .

                '</div>' .
                '<p class="clear">' . form::hidden('p', My::id()) .
                form::hidden('edit', 1) .
                form::hidden('id', App::backend()->id) .
                App::nonce()->getFormNonce() .
                '<input type="submit" name="edit_link" value="' . __('Save') . '"/></p>' .
                '</form>';
            }
        }

        Page::closeModule();
    }
}
