<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Antispam::initFilters();

        App::backend()->filters     = Antispam::$filters->getFilters();
        App::backend()->page_name   = My::name();
        App::backend()->filter_gui  = false;
        App::backend()->default_tab = null;
        App::backend()->filter      = null;

        try {
            // Show filter configuration GUI
            if (!empty($_GET['f'])) {
                if (!isset(App::backend()->filters[$_GET['f']])) {
                    throw new Exception(__('Filter does not exist.'));
                }

                if (!App::backend()->filters[$_GET['f']]->hasGUI()) {
                    throw new Exception(__('Filter has no user interface.'));
                }

                App::backend()->filter     = App::backend()->filters[$_GET['f']];
                App::backend()->filter_gui = App::backend()->filter->gui(App::backend()->filter->guiURL());
            }

            // Remove all spam
            if (!empty($_POST['delete_all'])) {
                $ts = isset($_POST['ts']) ? (int) $_POST['ts'] : null;
                $ts = Date::str('%Y-%m-%d %H:%M:%S', $ts, App::blog()->settings()->system->blog_timezone);

                Antispam::delAllSpam($ts);

                Notices::addSuccessNotice(__('Spam comments have been successfully deleted.'));
                My::redirect();
            }

            // Update filters
            if (isset($_POST['filters_upd'])) {
                $filters_opt = [];
                $i           = 0;
                foreach (App::backend()->filters as $fid => $f) {
                    $filters_opt[$fid] = [false, $i];
                    $i++;
                }

                // Enable active filters
                if (isset($_POST['filters_active']) && is_array($_POST['filters_active'])) {
                    foreach ($_POST['filters_active'] as $v) {
                        $filters_opt[$v][0] = true;
                    }
                }

                // Order filters
                if (!empty($_POST['f_order']) && empty($_POST['filters_order'])) {
                    $order = $_POST['f_order'];
                    asort($order);
                    $order = array_keys($order);
                } elseif (!empty($_POST['filters_order'])) {
                    $order = explode(',', trim((string) $_POST['filters_order'], ','));
                }

                if (isset($order)) {
                    foreach ($order as $i => $f) {
                        $filters_opt[$f][1] = $i;
                    }
                }

                // Set auto delete flag
                if (isset($_POST['filters_auto_del']) && is_array($_POST['filters_auto_del'])) {
                    foreach ($_POST['filters_auto_del'] as $v) {
                        $filters_opt[$v][2] = true;
                    }
                }

                Antispam::$filters->saveFilterOpts($filters_opt);

                Notices::addSuccessNotice(__('Filters configuration has been successfully saved.'));
                My::redirect();
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $title = (App::backend()->filter_gui !== false ?
            sprintf(__('%s configuration'), App::backend()->filter->name) . ' - ' :
            '' . App::backend()->page_name);

        $head = Page::jsPageTabs(App::backend()->default_tab);
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $head .= Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js');
        }
        $head .= Page::jsJson('antispam', ['confirm_spam_delete' => __('Are you sure you want to delete all spams?')]) .
            My::jsLoad('antispam') .
            My::cssLoad('style');

        Page::openModule($title, $head);

        if (App::backend()->filter_gui !== false) {
            echo
            Page::breadcrumb(
                [
                    __('Plugins')                                                        => '',
                    App::backend()->page_name                                            => App::backend()->getPageURL(),
                    sprintf(__('%s filter configuration'), App::backend()->filter->name) => '',
                ]
            ) .
            Notices::getNotices() .
            '<p><a href="' . App::backend()->getPageURL() . '" class="back">' . __('Back to filters list') . '</a></p>' .

            App::backend()->filter_gui;

            if (App::backend()->filter->help) {
                Page::helpBlock(App::backend()->filter->help);
            }
        } else {
            echo
            Page::breadcrumb(
                [
                    __('Plugins')             => '',
                    App::backend()->page_name => '',
                ]
            ) .
            Notices::getNotices();

            # Information
            $spam_count      = Antispam::countSpam();
            $published_count = Antispam::countPublishedComments();
            $moderationTTL   = My::settings()->antispam_moderation_ttl;

            echo
            '<form action="' . App::backend()->getPageURL() . '" method="post" class="fieldset">' .
            '<h3>' . __('Information') . '</h3>' .
            '<ul class="spaminfo">' .
            '<li class="spamcount"><a href="' . App::backend()->url->get('admin.comments', ['status' => '-2']) . '">' . __('Junk comments:') . '</a> ' .
            '<strong>' . $spam_count . '</strong></li>' .
            '<li class="hamcount"><a href="' . App::backend()->url->get('admin.comments', ['status' => '1']) . '">' . __('Published comments:') . '</a> ' .
                $published_count . '</li>' .
            '</ul>';

            if ($spam_count > 0) {
                echo
                '<p>' . App::nonce()->getFormNonce() .
                form::hidden('ts', time()) .
                '<input name="delete_all" class="delete" type="submit" value="' . __('Delete all spams') . '" /></p>';
            }
            if ($moderationTTL != null && $moderationTTL >= 0) {
                echo
                '<p>' . sprintf(__('All spam comments older than %s day(s) will be automatically deleted.'), $moderationTTL) . ' ' .
                sprintf(__('You can modify this duration in the %s'), '<a href="' . App::backend()->url->get('admin.blog.pref') .
                '#antispam_moderation_ttl"> ' . __('Blog settings') . '</a>') .
                '.</p>';
            }
            echo
            '</form>' .

            // Filters
            '<form action="' . App::backend()->getPageURL() . '" method="post" id="filters-list-form">';

            if (!empty($_GET['upd'])) {
                Notices::success(__('Filters configuration has been successfully saved.'));
            }

            echo
            '<div class="table-outer">' .
            '<table class="dragable">' .
            '<caption class="as_h3">' . __('Available spam filters') . '</caption>' .
            '<thead><tr>' .
            '<th>' . __('Order') . '</th>' .
            '<th>' . __('Active') . '</th>' .
            '<th>' . __('Auto Del.') . '</th>' .
            '<th class="nowrap">' . __('Filter name') . '</th>' .
            '<th colspan="2">' . __('Description') . '</th>' .
            '</tr></thead>' .
            '<tbody id="filters-list" >';

            $i = 1;
            foreach (App::backend()->filters as $fid => $f) {
                $gui_link = '&nbsp;';
                if ($f->hasGUI()) {
                    $gui_link = '<a href="' . Html::escapeHTML($f->guiURL()) . '">' .
                        '<img src="images/edit-mini.png" alt="' . __('Filter configuration') . '" ' .
                        'title="' . __('Filter configuration') . '" /></a>';
                }

                echo
                '<tr class="line' . ($f->active ? '' : ' offline') . '" id="f_' . $fid . '">' .
                '<td class="handle">' . form::number(['f_order[' . $fid . ']'], [
                    'min'        => 1,
                    'max'        => is_countable(App::backend()->filters) ? count(App::backend()->filters) : 0,
                    'default'    => $i,
                    'class'      => 'position',
                    'extra_html' => 'title="' . __('position') . '"',
                ]) .
                '</td>' .
                '<td class="nowrap">' . form::checkbox(
                    ['filters_active[]'],
                    $fid,
                    [
                        'checked'    => $f->active,
                        'extra_html' => 'title="' . __('Active') . '"',
                    ]
                ) . '</td>' .
                '<td class="nowrap">' . form::checkbox(
                    ['filters_auto_del[]'],
                    $fid,
                    [
                        'checked'    => $f->auto_delete,
                        'extra_html' => 'title="' . __('Auto Del.') . '"',
                    ]
                ) . '</td>' .
                '<td class="nowrap" scope="row">' . $f->name . '</td>' .
                '<td class="maximal">' . $f->description . '</td>' .
                    '<td class="status">' . $gui_link . '</td>' .
                '</tr>';
                $i++;
            }
            echo
            '</tbody></table></div>' .
            '<p>' . form::hidden('filters_order', '') .
            App::nonce()->getFormNonce() .
            '<input type="submit" name="filters_upd" value="' . __('Save') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '</form>';

            // Syndication
            if (DC_ADMIN_URL) {
                $ham_feed = App::blog()->url() . App::url()->getURLFor(
                    'hamfeed',
                    Antispam::getUserCode()
                );
                $spam_feed = App::blog()->url() . App::url()->getURLFor(
                    'spamfeed',
                    Antispam::getUserCode()
                );

                echo
                '<h3>' . __('Syndication') . '</h3>' .
                '<ul class="spaminfo">' .
                '<li class="feed"><a href="' . $spam_feed . '">' . __('Junk comments RSS feed') . '</a></li>' .
                '<li class="feed"><a href="' . $ham_feed . '">' . __('Published comments RSS feed') . '</a></li>' .
                '</ul>';
            }

            Page::helpBlock('antispam', 'antispam-filters');
        }

        Page::closeModule();
    }
}
