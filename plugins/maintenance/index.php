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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

// Set env

$core->blog->settings->addNamespace('maintenance');

$maintenance = new dcMaintenance($core);
$tasks       = $maintenance->getTasks();

$headers = '';
$p_url   = $core->adminurl->get('admin.plugin.maintenance');
$task    = null;
$expired = array();

$code = empty($_POST['code']) ? null : (integer) $_POST['code'];
$tab  = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

// Get task object

if (!empty($_REQUEST['task'])) {
    $task = $maintenance->getTask($_REQUEST['task']);

    if ($task === null) {
        $core->error->add('Unknow task ID');
    }

    $task->code($code);
}

// Execute task

if ($task && !empty($_POST['task']) && $task->id() == $_POST['task']) {
    try {
        $code = $task->execute();
        if (false === $code) {
            throw new Exception($task->error());
        }
        if (true === $code) {
            $maintenance->setLog($task->id());

            dcPage::addSuccessNotice($task->success());
            http::redirect($p_url . '&task=' . $task->id() . '&tab=' . $tab . '#' . $tab);
        }
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

// Save settings

if (!empty($_POST['save_settings'])) {

    try {
        $core->blog->settings->maintenance->put(
            'plugin_message',
            !empty($_POST['settings_plugin_message']),
            'boolean',
            'Display alert message of late tasks on plugin page',
            true,
            true
        );

        foreach ($tasks as $t) {
            if (!$t->id()) {
                continue;
            }

            if (!empty($_POST['settings_recall_type']) && $_POST['settings_recall_type'] == 'all') {
                $ts = $_POST['settings_recall_time'];
            } else {
                $ts = empty($_POST['settings_ts_' . $t->id()]) ? 0 : $_POST['settings_ts_' . $t->id()];
            }
            $core->blog->settings->maintenance->put(
                'ts_' . $t->id(),
                abs((integer) $ts),
                'integer',
                sprintf('Recall time for task %s', $t->id()),
                true,
                $t->blog()
            );
        }

        dcPage::addSuccessNotice(__('Maintenance plugin has been successfully configured.'));
        http::redirect($p_url . '&tab=' . $tab . '#' . $tab);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

// Combos

$combo_ts = array(
    __('Never')            => 0,
    __('Every week')       => 604800,
    __('Every two weeks')  => 1209600,
    __('Every month')      => 2592000,
    __('Every two months') => 5184000
);

// Display page

echo '<html><head>
<title>' . __('Maintenance') . '</title>' .
dcPage::jsPageTabs($tab) .
dcPage::jsLoad(dcPage::getPF('maintenance/js/settings.js'));

if ($task && $task->ajax()) {
    echo
    '<script type="text/javascript">' . "\n" .
    dcPage::jsVar('dotclear.msg.wait', __('Please wait...')) .
    '</script>' .
    dcPage::jsLoad(dcPage::getPF('maintenance/js/dc.maintenance.js'));
}

echo
$maintenance->getHeaders() . '
</head>
<body>';

// Check if there is somthing to display according to user permissions
if (empty($tasks)) {
    echo dcPage::breadcrumb(
        array(
            __('Plugins')     => '',
            __('Maintenance') => ''
        )
    ) .
    '<p class="warn">' . __('You have not sufficient permissions to view this page.') . '</p>' .
        '</body></html>';

    return;
}

if ($task && ($res = $task->step()) !== null) {

    // Page title

    echo dcPage::breadcrumb(
        array(
            __('Plugins')                                            => '',
            '<a href="' . $p_url . '">' . __('Maintenance') . '</a>' => '',
            html::escapeHTML($task->name())                          => ''
        )
    ) . dcPage::notices();

    // content
    if (substr($res, 0, 1) != '<') {
        $res = sprintf('<p class="step-msg">%s</p>', $res);
    }

    // Intermediate task (task required several steps)

    echo
    '<div class="step-box" id="' . $task->id() . '">' .
    '<p class="step-back">' .
    '<a class="back" href="' . $p_url . '&amp;tab=' . $task->tab() . '#' . $task->tab() . '">' . __('Back') . '</a>' .
    '</p>' .
    '<h3>' . html::escapeHTML($task->name()) . '</h3>' .
    '<form action="' . $p_url . '" method="post">' .
    $res .
    '<p class="step-submit">' .
    '<input type="submit" value="' . $task->task() . '" /> ' .
    form::hidden(array('task'), $task->id()) .
    form::hidden(array('code'), (integer) $code) .
    $core->formNonce() .
        '</p>' .
        '</form>' .
        '</div>';
} else {

    // Page title

    echo dcPage::breadcrumb(
        array(
            __('Plugins')     => '',
            __('Maintenance') => ''
        )
    ) . dcPage::notices();

    // Simple task (with only a button to start it)

    foreach ($maintenance->getTabs() as $tab_obj) {
        $res_group = '';
        foreach ($maintenance->getGroups() as $group_obj) {
            $res_task = '';
            foreach ($tasks as $t) {
                if (!$t->id()
                    || $t->group() != $group_obj->id()
                    || $t->tab() != $tab_obj->id()) {
                    continue;
                }

                $res_task .=
                '<p>' . form::radio(array('task', $t->id()), $t->id()) . ' ' .
                '<label class="classic" for="' . $t->id() . '">' .
                html::escapeHTML($t->task()) . '</label>';

                // Expired task alert message
                $ts = $t->expired();
                if ($core->blog->settings->maintenance->plugin_message && $ts !== false) {
                    if ($ts === null) {
                        $res_task .=
                        '<br /> <span class="warn">' .
                        __('This task has never been executed.') . ' ' .
                        __('You should execute it now.') . '</span>';
                    } else {
                        $res_task .=
                        '<br /> <span class="warn">' . sprintf(
                            __('Last execution of this task was on %s.'),
                            dt::str($core->blog->settings->system->date_format, $ts) . ' ' .
                            dt::str($core->blog->settings->system->time_format, $ts)
                        ) . ' ' .
                        __('You should execute it now.') . '</span>';
                    }
                }

                $res_task .= '</p>';
            }

            if (!empty($res_task)) {
                $res_group .=
                '<div class="fieldset">' .
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
            '<form action="' . $p_url . '" method="post">' .
            $res_group .
            '<p><input type="submit" value="' . __('Execute task') . '" /> ' .
            form::hidden(array('tab'), $tab_obj->id()) .
            $core->formNonce() . '</p>' .
            '<p class="form-note info">' . __('This may take a very long time.') . '</p>' .
                '</form>' .
                '</div>';
        }
    }

    // Advanced tasks (that required a tab)

    foreach ($tasks as $t) {
        if (!$t->id() || $t->group() !== null) {
            continue;
        }

        echo
        '<div id="' . $t->id() . '" class="multi-part" title="' . $t->name() . '">' .
        '<h3>' . $t->name() . '</h3>' .
        '<form action="' . $p_url . '" method="post">' .
        $t->content() .
        '<p><input type="submit" value="' . __('Execute task') . '" /> ' .
        form::hidden(array('task'), $t->id()) .
        form::hidden(array('tab'), $t->id()) .
        $core->formNonce() . '</p>' .
            '</form>' .
            '</div>';
    }

    // Settings

    echo
    '<div id="settings" class="multi-part" title="' . __('Alert settings') . '">' .
    '<h3>' . __('Alert settings') . '</h3>' .
    '<form action="' . $p_url . '" method="post">' .

    '<h4 class="pretty-title">' . __('Activation') . '</h4>' .
    '<p><label for="settings_plugin_message" class="classic">' .
    form::checkbox('settings_plugin_message', 1, $core->blog->settings->maintenance->plugin_message) .
    __('Display alert messages on late tasks') . '</label></p>' .

    '<p class="info">' . sprintf(
        __('You can place list of late tasks on your %s.'),
        '<a href="' . $core->adminurl->get('admin.user.preferences') . '#user-favorites">' . __('Dashboard') . '</a>'
    ) . '</p>' .

    '<h4 class="pretty-title vertical-separator">' . __('Frequency') . '</h4>' .

    '<p class="vertical-separator">' . form::radio(array('settings_recall_type', 'settings_recall_all'), 'all') . ' ' .
    '<label class="classic" for="settings_recall_all">' .
    '<strong>' . __('Use one recall time for all tasks') . '</strong></label></p>' .

    '<p class="field wide vertical-separator"><label for="settings_recall_time">' . __('Recall time for all tasks:') . '</label>' .
    form::combo('settings_recall_time', $combo_ts, 'seperate', 'recall-for-all') .
    '</p>' .

    '<p class="vertical-separator">' . form::radio(array('settings_recall_type', 'settings_recall_separate'), 'separate', 1) . ' ' .
    '<label class="classic" for="settings_recall_separate">' .
    '<strong>' . __('Use one recall time per task') . '</strong></label></p>';

    foreach ($tasks as $t) {
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
    '<p class="field wide"><input type="submit" value="' . __('Save') . '" /> ' .
    form::hidden(array('tab'), 'settings') .
    form::hidden(array('save_settings'), 1) .
    $core->formNonce() . '</p>' .
        '</form>' .
        '</div>';
}

dcPage::helpBlock('maintenance', 'maintenancetasks');

echo
    '</body></html>';
