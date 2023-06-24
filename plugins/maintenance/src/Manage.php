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

use dcCore;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

class Manage extends Process
{
    public static function init(): bool
    {
        if (!My::checkContext(My::MANAGE)) {
            return false;
        }

        static::$init = true;

        // Set env

        dcCore::app()->admin->maintenance = new Maintenance();
        dcCore::app()->admin->tasks       = dcCore::app()->admin->maintenance->getTasks();
        dcCore::app()->admin->code        = empty($_POST['code']) ? null : (int) $_POST['code'];
        dcCore::app()->admin->tab         = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        // Get task object

        dcCore::app()->admin->task = null;
        if (!empty($_REQUEST['task'])) {
            dcCore::app()->admin->task = dcCore::app()->admin->maintenance->getTask($_REQUEST['task']);

            if (dcCore::app()->admin->task === null) {
                dcCore::app()->error->add('Unknown task ID');
            }

            dcCore::app()->admin->task->code(dcCore::app()->admin->code);
        }

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // Execute task

        if (dcCore::app()->admin->task && !empty($_POST['task']) && dcCore::app()->admin->task->id() == $_POST['task']) {
            try {
                dcCore::app()->admin->code = dcCore::app()->admin->task->execute();
                if (false === dcCore::app()->admin->code) {
                    throw new Exception(dcCore::app()->admin->task->error());
                }
                if (true === dcCore::app()->admin->code) {
                    dcCore::app()->admin->maintenance->setLog(dcCore::app()->admin->task->id());

                    Page::addSuccessNotice(dcCore::app()->admin->task->success());
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), ['task' => dcCore::app()->admin->task->id(), 'tab' => dcCore::app()->admin->tab], '#' . dcCore::app()->admin->tab);
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Save settings

        if (!empty($_POST['save_settings'])) {
            try {
                dcCore::app()->blog->settings->maintenance->put(
                    'plugin_message',
                    !empty($_POST['settings_plugin_message']),
                    'boolean',
                    'Display alert message of late tasks on plugin page',
                    true,
                    true
                );

                foreach (dcCore::app()->admin->tasks as $t) {
                    if (!$t->id()) {
                        continue;
                    }

                    if (!empty($_POST['settings_recall_type']) && $_POST['settings_recall_type'] == 'all') {
                        $ts = $_POST['settings_recall_time'];
                    } else {
                        $ts = empty($_POST['settings_ts_' . $t->id()]) ? 0 : $_POST['settings_ts_' . $t->id()];
                    }
                    dcCore::app()->blog->settings->maintenance->put(
                        'ts_' . $t->id(),
                        abs((int) $ts),
                        'integer',
                        sprintf('Recall time for task %s', $t->id()),
                        true,
                        $t->blog()
                    );
                }

                Page::addSuccessNotice(__('Maintenance plugin has been successfully configured.'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), ['tab' => dcCore::app()->admin->tab], '#' . dcCore::app()->admin->tab);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Save system settings

        if (!empty($_POST['save_system'])) {
            try {
                // Default (global) settings
                dcCore::app()->blog->settings->system->put('csp_admin_on', !empty($_POST['system_csp_global']), null, null, true, true);
                dcCore::app()->blog->settings->system->put('csp_admin_report_only', !empty($_POST['system_csp_global_report_only']), null, null, true, true);
                // Current blog settings
                dcCore::app()->blog->settings->system->put('csp_admin_on', !empty($_POST['system_csp']));
                dcCore::app()->blog->settings->system->put('csp_admin_report_only', !empty($_POST['system_csp_report_only']));

                Page::addSuccessNotice(__('System settings have been saved.'));

                if (!empty($_POST['system_csp_reset'])) {
                    dcCore::app()->blog->settings->system->dropEvery('csp_admin_on');
                    dcCore::app()->blog->settings->system->dropEvery('csp_admin_report_only');
                    Page::addSuccessNotice(__('All blog\'s Content-Security-Policy settings have been reset to default.'));
                }

                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), ['tab' => dcCore::app()->admin->tab], '#' . dcCore::app()->admin->tab);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
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

        $head = Page::jsPageTabs(dcCore::app()->admin->tab) .
            My::jsLoad('settings.js');
        if (dcCore::app()->admin->task && dcCore::app()->admin->task->ajax()) {
            $head .= Page::jsJson('maintenance', ['wait' => __('Please wait...')]) .
                My::jsLoad('dc.maintenance.js');
        }
        $head .= dcCore::app()->admin->maintenance->getHeaders();

        Page::openModule(My::name(), $head);

        // Check if there is something to display according to user permissions
        if (empty(dcCore::app()->admin->tasks)) {
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

        if (dcCore::app()->admin->task && ($res = dcCore::app()->admin->task->step()) !== null) {
            // Page title

            echo
            Page::breadcrumb(
                [
                    __('Plugins')                                                                 => '',
                    '<a href="' . dcCore::app()->admin->getPageURL() . '">' . My::name() . '</a>' => '',
                    Html::escapeHTML(dcCore::app()->admin->task->name())                          => '',
                ]
            ) .
            Page::notices();

            // content
            if (substr($res, 0, 1) != '<') {
                $res = sprintf('<p class="step-msg">%s</p>', $res);
            }

            // Intermediate task (task required several steps)
            echo
            '<div class="step-box" id="' . dcCore::app()->admin->task->id() . '">' .
            '<p class="step-back">' .
            '<a class="back" href="' . dcCore::app()->admin->getPageURL() . '&amp;tab=' . dcCore::app()->admin->task->tab() . '#' . dcCore::app()->admin->task->tab() . '">' . __('Back') . '</a>' .
            '</p>' .
            '<h3>' . Html::escapeHTML(dcCore::app()->admin->task->name()) . '</h3>' .
            '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .
            $res .
            '<p class="step-submit">' .
            '<input type="submit" value="' . dcCore::app()->admin->task->task() . '" /> ' .
            form::hidden(['task'], dcCore::app()->admin->task->id()) .
            form::hidden(['code'], (int) dcCore::app()->admin->code) .
            dcCore::app()->formNonce() .
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
            Page::notices();

            // Simple task (with only a button to start it)

            foreach (dcCore::app()->admin->maintenance->getTabs() as $tab_obj) {
                $res_group = '';
                foreach (dcCore::app()->admin->maintenance->getGroups() as $group_obj) {
                    $res_task = '';
                    foreach (dcCore::app()->admin->tasks as $t) {
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
                        if (dcCore::app()->blog->settings->maintenance->plugin_message && $ts !== false) {
                            if ($ts === null) {
                                $res_task .= '<br /> <span class="warn">' .
                                    __('This task has never been executed.') . ' ' .
                                    __('You should execute it now.') . '</span>';
                            } else {
                                $res_task .= '<br /> <span class="warn">' .
                                    sprintf(
                                        __('Last execution of this task was on %s.'),
                                        Date::str(dcCore::app()->blog->settings->system->date_format, $ts) . ' ' .
                                        Date::str(dcCore::app()->blog->settings->system->time_format, $ts)
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
                    '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .
                    $res_group .
                    '<p><input type="submit" value="' . __('Execute task') . '" /> ' .
                    ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                    form::hidden(['tab'], $tab_obj->id()) .
                    dcCore::app()->formNonce() . '</p>' .
                    '<p class="form-note info">' . __('This may take a very long time.') . '</p>' .
                    '</form>' .
                    '</div>';
                }
            }

            // Advanced tasks (that required a tab)

            foreach (dcCore::app()->admin->tasks as $t) {
                if (!$t->id() || $t->group() !== null) {
                    continue;
                }

                echo
                '<div id="' . $t->id() . '" class="multi-part" title="' . $t->name() . '">' .
                '<h3>' . $t->name() . '</h3>' .
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .
                $t->content() .
                '<p><input type="submit" value="' . __('Execute task') . '" /> ' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                form::hidden(['task'], $t->id()) .
                form::hidden(['tab'], $t->id()) .
                dcCore::app()->formNonce() . '</p>' .
                '</form>' .
                '</div>';
            }

            // Settings

            echo
            '<div id="settings" class="multi-part" title="' . __('Alert settings') . '">' .
            '<h3>' . __('Alert settings') . '</h3>' .
            '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .

            '<div class="fieldset">' .
            '<h4>' . __('Activation') . '</h4>' .
            '<p><label for="settings_plugin_message" class="classic">' .
            form::checkbox('settings_plugin_message', 1, dcCore::app()->blog->settings->maintenance->plugin_message) .
            __('Display alert messages on late tasks') . '</label></p>' .

            '<p class="info">' . sprintf(
                __('You can place list of late tasks on your %s.'),
                '<a href="' . dcCore::app()->adminurl->get('admin.user.preferences') . '#user-favorites">' . __('Dashboard') . '</a>'
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
            foreach (dcCore::app()->admin->tasks as $t) {
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
            dcCore::app()->formNonce() . '</p>' .
            '</form>' .
            '</div>';

            // System tab
            if (dcCore::app()->auth->isSuperAdmin()) {
                echo
                '<div id="system" class="multi-part" title="' . __('System') . '">' .
                '<h3>' . __('System settings') . '</h3>' .
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">';

                echo
                '<div class="fieldset two-cols clearfix">' .
                '<h4 class="pretty-title">' . __('Content-Security-Policy') . '</h4>' .

                '<div class="col">' .
                '<p><label for="system_csp" class="classic">' .
                form::checkbox('system_csp', '1', dcCore::app()->blog->settings->system->csp_admin_on) .
                __('Enable Content-Security-Policy system') . '</label></p>' .
                '<p><label for="system_csp_report_only" class="classic">' .
                form::checkbox('system_csp_report_only', '1', dcCore::app()->blog->settings->system->csp_admin_report_only) .
                __('Enable Content-Security-Policy report only') . '</label></p>' .
                '</div>' .

                '<div class="col">' .
                '<p><label for="system_csp_global" class="classic">' .
                form::checkbox('system_csp_global', '1', dcCore::app()->blog->settings->system->getGlobal('csp_admin_on')) .
                __('Enable Content-Security-Policy system by default') . '</label></p>' .
                '<p><label for="system_csp_global_report_only" class="classic">' .
                form::checkbox('system_csp_global_report_only', '1', dcCore::app()->blog->settings->system->getGlobal('csp_admin_report_only')) .
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
                dcCore::app()->formNonce() . '</p>' .
                '</form>' .
                '</div>';
            }
        }

        Page::helpBlock('maintenance', 'maintenancetasks');

        Page::closeModule();
    }
}
