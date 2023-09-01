<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

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
        if (!self::status(My::checkContext(My::MANAGE))) {
            return false;
        }

        // Set env

        App::backend()->maintenance = new Maintenance();
        App::backend()->tasks       = App::backend()->maintenance->getTasks();
        App::backend()->code        = empty($_POST['code']) ? null : (int) $_POST['code'];
        App::backend()->tab         = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        // Get task object

        App::backend()->task = null;
        if (!empty($_REQUEST['task'])) {
            App::backend()->task = App::backend()->maintenance->getTask($_REQUEST['task']);

            if (App::backend()->task === null) {
                App::error()->add('Unknown task ID');
            }

            App::backend()->task->code(App::backend()->code);
        }

        return self::status(true);
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Execute task

        if (App::backend()->task && !empty($_POST['task']) && App::backend()->task->id() == $_POST['task']) {
            try {
                App::backend()->code = App::backend()->task->execute();
                if (false === App::backend()->code) {
                    throw new Exception(App::backend()->task->error());
                }
                if (true === App::backend()->code) {
                    App::backend()->maintenance->setLog(App::backend()->task->id());

                    Notices::addSuccessNotice(App::backend()->task->success());
                    My::redirect(['task' => App::backend()->task->id(), 'tab' => App::backend()->tab], '#' . App::backend()->tab);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Save settings

        if (!empty($_POST['save_settings'])) {
            try {
                My::settings()->put(
                    'plugin_message',
                    !empty($_POST['settings_plugin_message']),
                    'boolean',
                    'Display alert message of late tasks on plugin page',
                    true,
                    true
                );

                foreach (App::backend()->tasks as $t) {
                    if (!$t->id()) {
                        continue;
                    }

                    if (!empty($_POST['settings_recall_type']) && $_POST['settings_recall_type'] == 'all') {
                        $ts = $_POST['settings_recall_time'];
                    } else {
                        $ts = empty($_POST['settings_ts_' . $t->id()]) ? 0 : $_POST['settings_ts_' . $t->id()];
                    }
                    My::settings()->put(
                        'ts_' . $t->id(),
                        abs((int) $ts),
                        'integer',
                        sprintf('Recall time for task %s', $t->id()),
                        true,
                        $t->blog()
                    );
                }

                Notices::addSuccessNotice(__('Maintenance plugin has been successfully configured.'));
                My::redirect(['tab' => App::backend()->tab], '#' . App::backend()->tab);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Save system settings

        if (!empty($_POST['save_system'])) {
            try {
                // Default (global) settings
                App::blog()->settings->system->put('csp_admin_on', !empty($_POST['system_csp_global']), null, null, true, true);
                App::blog()->settings->system->put('csp_admin_report_only', !empty($_POST['system_csp_global_report_only']), null, null, true, true);
                // Current blog settings
                App::blog()->settings->system->put('csp_admin_on', !empty($_POST['system_csp']));
                App::blog()->settings->system->put('csp_admin_report_only', !empty($_POST['system_csp_report_only']));

                Notices::addSuccessNotice(__('System settings have been saved.'));

                if (!empty($_POST['system_csp_reset'])) {
                    App::blog()->settings->system->dropEvery('csp_admin_on');
                    App::blog()->settings->system->dropEvery('csp_admin_report_only');
                    Notices::addSuccessNotice(__('All blog\'s Content-Security-Policy settings have been reset to default.'));
                }

                My::redirect(['tab' => App::backend()->tab], '#' . App::backend()->tab);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // Combos

        $combo_ts = [
            __('Never')            => 0,
            __('Every week')       => 604800,
            __('Every two weeks')  => 1_209_600,
            __('Every month')      => 2_592_000,
            __('Every two months') => 5_184_000,
        ];

        // Display page

        $head = Page::jsPageTabs(App::backend()->tab) .
            My::jsLoad('settings');
        if (App::backend()->task && App::backend()->task->ajax()) {
            $head .= Page::jsJson('maintenance', ['wait' => __('Please wait...')]) .
                My::jsLoad('dc.maintenance');
        }
        $head .= App::backend()->maintenance->getHeaders();

        Page::openModule(My::name(), $head);

        // Check if there is something to display according to user permissions
        if (empty(App::backend()->tasks)) {
            echo
            Page::breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            ) .
            '<p class="warn">' . __('You have not sufficient permissions to view this page.') . '</p>';

            Page::closeModule();

            return;
        }

        if (App::backend()->task && ($res = App::backend()->task->step()) !== null) {
            // Page title

            echo
            Page::breadcrumb(
                [
                    __('Plugins')                                                           => '',
                    '<a href="' . App::backend()->getPageURL() . '">' . My::name() . '</a>' => '',
                    Html::escapeHTML(App::backend()->task->name())                          => '',
                ]
            ) .
            Notices::getNotices();

            // content
            if (substr($res, 0, 1) != '<') {
                $res = sprintf('<p class="step-msg">%s</p>', $res);
            }

            // Intermediate task (task required several steps)
            echo
            '<div class="step-box" id="' . App::backend()->task->id() . '">' .
            '<p class="step-back">' .
            '<a class="back" href="' . App::backend()->getPageURL() . '&amp;tab=' . App::backend()->task->tab() . '#' . App::backend()->task->tab() . '">' . __('Back') . '</a>' .
            '</p>' .
            '<h3>' . Html::escapeHTML(App::backend()->task->name()) . '</h3>' .
            '<form action="' . App::backend()->getPageURL() . '" method="post">' .
            $res .
            '<p class="step-submit">' .
            '<input type="submit" value="' . App::backend()->task->task() . '" /> ' .
            form::hidden(['task'], App::backend()->task->id()) .
            form::hidden(['code'], (int) App::backend()->code) .
            App::nonce()->getFormNonce() .
            '</p>' .
            '</form>' .
            '</div>';
        } else {
            // Page title

            echo
            Page::breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            ) .
            Notices::getNotices();

            // Simple task (with only a button to start it)

            foreach (App::backend()->maintenance->getTabs() as $tab_obj) {
                $res_group = '';
                foreach (App::backend()->maintenance->getGroups() as $group_obj) {
                    $res_task = '';
                    foreach (App::backend()->tasks as $t) {
                        if (!$t->id()
                        || $t->group() != $group_obj->id()
                        || $t->tab()   != $tab_obj->id()) {
                            continue;
                        }

                        $res_task .= '<p>' . form::radio(['task', $t->id()], $t->id()) . ' ' .
                            '<label class="classic" for="' . $t->id() . '">' .
                            Html::escapeHTML($t->task()) . '</label>';

                        // Expired task alert message
                        $ts = $t->expired();
                        if (My::settings()->plugin_message && $ts !== false) {
                            if ($ts === null) {
                                $res_task .= '<br /> <span class="warn">' .
                                    __('This task has never been executed.') . ' ' .
                                    __('You should execute it now.') . '</span>';
                            } else {
                                $res_task .= '<br /> <span class="warn">' .
                                    sprintf(
                                        __('Last execution of this task was on %s.'),
                                        Date::str(App::blog()->settings->system->date_format, $ts) . ' ' .
                                        Date::str(App::blog()->settings->system->time_format, $ts)
                                    ) . ' ' .
                                    __('You should execute it now.') . '</span>';
                            }
                        }

                        $res_task .= '</p>';
                    }

                    if (!empty($res_task)) {
                        $res_group .= '<div class="fieldset">' .
                            '<h4 id="' . $group_obj->id() . '">' . $group_obj->name() . '</h4>' .
                            $res_task .
                            '</div>';
                    }
                }

                if (!empty($res_group)) {
                    echo
                    '<div id="' . $tab_obj->id() . '" class="multi-part" title="' . $tab_obj->name() . '">' .
                    '<h3>' . $tab_obj->name() . '</h3>' .
                    // ($tab_obj->option('summary') ? '<p>'.$tab_obj->option('summary').'</p>' : '').
                    '<form action="' . App::backend()->getPageURL() . '" method="post">' .
                    $res_group .
                    '<p><input type="submit" value="' . __('Execute task') . '" /> ' .
                    ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                    form::hidden(['tab'], $tab_obj->id()) .
                    App::nonce()->getFormNonce() . '</p>' .
                    '<p class="form-note info">' . __('This may take a very long time.') . '</p>' .
                    '</form>' .
                    '</div>';
                }
            }

            // Advanced tasks (that required a tab)

            foreach (App::backend()->tasks as $t) {
                if (!$t->id() || $t->group() !== null) {
                    continue;
                }

                echo
                '<div id="' . $t->id() . '" class="multi-part" title="' . $t->name() . '">' .
                '<h3>' . $t->name() . '</h3>' .
                '<form action="' . App::backend()->getPageURL() . '" method="post">' .
                $t->content() .
                '<p><input type="submit" value="' . __('Execute task') . '" /> ' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                form::hidden(['task'], $t->id()) .
                form::hidden(['tab'], $t->id()) .
                App::nonce()->getFormNonce() . '</p>' .
                '</form>' .
                '</div>';
            }

            // Settings

            echo
            '<div id="settings" class="multi-part" title="' . __('Alert settings') . '">' .
            '<h3>' . __('Alert settings') . '</h3>' .
            '<form action="' . App::backend()->getPageURL() . '" method="post">' .

            '<div class="fieldset">' .
            '<h4>' . __('Activation') . '</h4>' .
            '<p><label for="settings_plugin_message" class="classic">' .
            form::checkbox('settings_plugin_message', 1, My::settings()->plugin_message) .
            __('Display alert messages on late tasks') . '</label></p>' .

            '<p class="info">' . sprintf(
                __('You can place list of late tasks on your %s.'),
                '<a href="' . App::backend()->url->get('admin.user.preferences') . '#user-favorites">' . __('Dashboard') . '</a>'
            ) . '</p>' .
            '</div>' .

            '<div class="fieldset">' .
            '<h4>' . __('Frequency') . '</h4>' .
            '<p>' . form::radio(['settings_recall_type', 'settings_recall_all'], 'all') . ' ' .
            '<label class="classic" for="settings_recall_all">' .
            '<strong>' . __('Use one recall time for all tasks') . '</strong></label></p>' .

            '<p class="field wide"><label for="settings_recall_time">' . __('Recall time for all tasks:') . '</label>' .
            form::combo('settings_recall_time', $combo_ts, 'seperate', 'recall-for-all') .
            '</p>' .

            '<p class="vertical-separator">' . form::radio(['settings_recall_type', 'settings_recall_separate'], 'separate', 1) . ' ' .
            '<label class="classic" for="settings_recall_separate">' .
            '<strong>' . __('Use one recall time per task') . '</strong></label></p>';
            foreach (App::backend()->tasks as $t) {
                if (!$t->id()) {
                    continue;
                }
                echo
                '<div class="two-boxes">' .
                '<p class="field wide"><label for="settings_ts_' . $t->id() . '">' . $t->task() . '</label>' .
                form::combo('settings_ts_' . $t->id(), $combo_ts, $t->ts(), 'recall-per-task') .
                '</p>' .
                '</div>';
            }
            echo
            '</div>' .
            '<p class="field wide"><input type="submit" value="' . __('Save') . '" /> ' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            form::hidden(['tab'], 'settings') .
            form::hidden(['save_settings'], 1) .
            App::nonce()->getFormNonce() . '</p>' .
            '</form>' .
            '</div>';

            // System tab
            if (App::auth()->isSuperAdmin()) {
                echo
                '<div id="system" class="multi-part" title="' . __('System') . '">' .
                '<h3>' . __('System settings') . '</h3>' .
                '<form action="' . App::backend()->getPageURL() . '" method="post">';

                echo
                '<div class="fieldset two-cols clearfix">' .
                '<h4 class="pretty-title">' . __('Content-Security-Policy') . '</h4>' .

                '<div class="col">' .
                '<p><label for="system_csp" class="classic">' .
                form::checkbox('system_csp', '1', App::blog()->settings->system->csp_admin_on) .
                __('Enable Content-Security-Policy system') . '</label></p>' .
                '<p><label for="system_csp_report_only" class="classic">' .
                form::checkbox('system_csp_report_only', '1', App::blog()->settings->system->csp_admin_report_only) .
                __('Enable Content-Security-Policy report only') . '</label></p>' .
                '</div>' .

                '<div class="col">' .
                '<p><label for="system_csp_global" class="classic">' .
                form::checkbox('system_csp_global', '1', App::blog()->settings->system->getGlobal('csp_admin_on')) .
                __('Enable Content-Security-Policy system by default') . '</label></p>' .
                '<p><label for="system_csp_global_report_only" class="classic">' .
                form::checkbox('system_csp_global_report_only', '1', App::blog()->settings->system->getGlobal('csp_admin_report_only')) .
                __('Enable Content-Security-Policy report only by default') . '</label></p>' .
                '<p><label for="system_csp_reset" class="classic">' .
                form::checkbox('system_csp_reset', '1', 0) .
                __('Also apply these settings to all blogs') . '</label></p>' .
                '</div>' .
                '</div>';

                echo
                '<p class="field wide"><input type="submit" value="' . __('Save') . '" /> ' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                form::hidden(['tab'], 'system') .
                form::hidden(['save_system'], 1) .
                App::nonce()->getFormNonce() . '</p>' .
                '</form>' .
                '</div>';
            }
        }

        Page::helpBlock('maintenance', 'maintenancetasks');

        Page::closeModule();
    }
}
