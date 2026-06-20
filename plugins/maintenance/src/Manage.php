<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup maintenance
 */
class Manage
{
    use TraitProcess;

    // Local properties

    /**
     * Maintenance instance
     */
    private static Maintenance $maintenance;

    /**
     * List of available maintenance tasks
     *
     * @var array<string, MaintenanceTask> $tasks;
     */
    private static array $tasks;

    /**
     * Current task
     */
    private static MaintenanceTask $task;

    /**
     * True if a task is in progress, otherwise false
     */
    private static bool $task_in_progress;

    /**
     * Current task code
     */
    private static null|int|bool $code;

    /**
     * Current task count
     */
    private static int $count;

    /**
     * Current tab
     */
    private static string $tab;

    /**
     *
     */
    public static function init(): bool
    {
        if (!self::status(My::checkContext(My::MANAGE))) {
            return false;
        }

        // Set env

        if (!isset(self::$maintenance)) {
            self::$maintenance = new Maintenance();
            self::$tasks       = self::$maintenance->getTasks();
        }

        self::$code  = isset($_POST['code'])  && is_numeric($code = $_POST['code']) ? (int) $code : null;
        self::$count = isset($_POST['count']) && is_numeric($count = $_POST['count']) ? (int) $count : 0;

        self::$tab = isset($_REQUEST['tab']) && is_string($tab = $_REQUEST['tab']) ? $tab : '';

        // Get task object

        self::$task_in_progress = false;

        if (!empty($_REQUEST['task'])) {
            $task_id = is_string($task_id = $_REQUEST['task']) ? $task_id : '';
            if ($task_id !== '') {
                $task = self::$maintenance->getTask($task_id);
                if ($task instanceof MaintenanceTask) {
                    self::$task = $task;
                    self::$task->code(self::$code);
                    if (self::$count > 0) {
                        self::$task->count(self::$count);
                    }
                    self::$task_in_progress = true;
                } else {
                    App::error()->add('Unknown task ID');
                }
            }
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Post data helpers
        $_Bool = fn (string $name): bool => !empty($_POST[$name]);
        $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
        $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        // Execute task

        if (self::$task_in_progress && $_Bool('task')) {
            $task_id = $_Str('task');
            if ($task_id === self::$task->id()) {
                try {
                    self::$code = self::$task->execute();
                    if (self::$code === false) {
                        throw new Exception(self::$task->error());
                    }

                    if (self::$code === true) {
                        self::$maintenance->setLog(self::$task->id());

                        App::backend()->notices()->addSuccessNotice(self::$task->success());
                        My::redirect(['task' => self::$task->id(), 'tab' => self::$tab], '#' . self::$tab);
                    }
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        // Save settings

        if ($_Bool('save_settings')) {
            try {
                My::settings()->put(
                    'plugin_message',
                    $_Bool('settings_plugin_message'),
                    App::blogWorkspace()::NS_BOOL,
                    'Display alert message of late tasks on plugin page',
                    true,
                    true
                );

                foreach (self::$tasks as $task) {
                    if (!$task->id()) {
                        continue;
                    }

                    $recall_type = $_Str('settings_recall_type');
                    $delay       = $recall_type === 'all' ? $_Int('settings_recall_time') : $_Int('settings_ts_' . $task->id());

                    My::settings()->put(
                        'ts_' . $task->id(),
                        abs($delay),
                        App::blogWorkspace()::NS_INT,
                        sprintf('Recall time for task %s', $task->id()),
                        true,
                        $task->blog()
                    );
                }

                App::backend()->notices()->addSuccessNotice(__('Maintenance plugin has been successfully configured.'));
                My::redirect(['tab' => self::$tab], '#' . self::$tab);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Save system settings

        if ($_Bool('save_system')) {
            try {
                // Default (global) settings
                App::blog()->settings()->get('system')->put('csp_admin_on', $_Bool('system_csp_global'), null, null, true, true);
                App::blog()->settings()->get('system')->put('csp_admin_report_only', $_Bool('system_csp_global_report_only'), null, null, true, true);

                // Current blog settings
                App::blog()->settings()->get('system')->put('csp_admin_on', $_Bool('system_csp'));
                App::blog()->settings()->get('system')->put('csp_admin_report_only', $_Bool('system_csp_report_only'));

                App::backend()->notices()->addSuccessNotice(__('System settings have been saved.'));

                if ($_Bool('system_csp_reset')) {
                    App::blog()->settings()->get('system')->dropEvery('csp_admin_on');
                    App::blog()->settings()->get('system')->dropEvery('csp_admin_report_only');
                    App::backend()->notices()->addSuccessNotice(__('All blog\'s Content-Security-Policy settings have been reset to default.'));
                }

                My::redirect(['tab' => self::$tab], '#' . self::$tab);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // Combos

        $combo_ts = [
            new Option(__('Never'), (string) 0),
            new Option(__('Every week'), (string) 604_800),
            new Option(__('Every two weeks'), (string) 1_209_600),
            new Option(__('Every month'), (string) 2_592_000),
            new Option(__('Every two months'), (string) 5_184_000),
        ];

        if (App::config()->debugMode() && App::config()->devMode()) {
            $combo_ts = array_merge($combo_ts, [
                new Option('5 minutes', (string) 300),
                new Option('1 minute', (string) 60),
            ]);
        }

        // Display page

        $head = App::backend()->page()->jsPageTabs(self::$tab) .
            My::jsLoad('settings') .
            My::cssLoad('style');
        if (self::$task_in_progress && self::$task->ajax()) {
            $head .= App::backend()->page()->jsJson('maintenance', ['wait' => __('Please wait...')]) .
                My::jsLoad('dc.maintenance');
        } else {
            $head .= App::backend()->page()->jsConfirmClose('settings-form');
        }
        $head .= self::$maintenance->getHeaders();

        App::backend()->page()->openModule(My::name(), $head);

        // Check if there is something to display according to user permissions
        if (self::$tasks === []) {
            echo
            App::backend()->page()->breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            );

            App::backend()->notices()->warning(__('You have not sufficient permissions to view this page.'), false);

            App::backend()->page()->closeModule();

            return;
        }

        if (self::$task_in_progress && ($res = self::$task->step()) !== null) {
            // Page title
            echo
            App::backend()->page()->breadcrumb(
                [
                    __('Plugins')                                                           => '',
                    '<a href="' . App::backend()->getPageURL() . '">' . My::name() . '</a>' => '',
                    Html::escapeHTML(self::$task->name())                                   => '',
                ]
            ) .
            App::backend()->notices()->getNotices();

            // Content
            if (str_starts_with($res, '<')) {
                $content = new Text(null, $res);
            } else {
                // Encapsulate content in paragraph
                $content = (new para())
                    ->class('step-msg')
                    ->items([
                        (new Text(null, $res)),
                    ]);
            }

            // Intermediate task (task required several steps)
            echo (new Para())
                ->class('step-back')
                ->items([
                    (new Link('back'))
                        ->href(App::backend()->getPageURL() . '&amp;tab=' . self::$task->tab() . '#' . self::$task->tab())
                        ->class('back')
                        ->text(__('Back')),
                ])
            ->render();

            echo (new Form('step-form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields([
                    (new Fieldset(self::$task->id()))
                        ->class('step-box')
                        ->fields([
                            $content,
                            (new Para())->class(['step-submit', 'form-buttons'])->items([
                                ...My::hiddenFields(),
                                (new Hidden(['task'], self::$task->id())),
                                (new Hidden(['code'], (string) self::$code)),
                                (new Hidden(['count'], (string) self::$task->getCount())),
                                (new Submit(['step-submit-button'], self::$task->task())),
                            ]),
                        ]),
                ])
            ->render();
        } else {
            // Page title

            echo
            App::backend()->page()->breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            ) .
            App::backend()->notices()->getNotices();

            $date_format = is_string($date_format = App::blog()->settings()->system->date_format) ? $date_format : '%F';
            $time_format = is_string($time_format = App::blog()->settings()->system->time_format) ? $time_format : '%T';

            // Simple task (with only a button to start it)
            foreach (self::$maintenance->getTabs() as $tab_obj) {
                $groups = [];
                foreach (self::$maintenance->getGroups() as $group_obj) {
                    $tasks = [];
                    foreach (self::$tasks as $t) {
                        if (!$t->id()) {
                            continue;
                        }
                        if ($t->group() != $group_obj->id()) {
                            continue;
                        }
                        if ($t->tab() != $tab_obj->id()) {
                            continue;
                        }

                        // Expired task alert message
                        $ts   = $t->expired();
                        $note = new None();
                        if (My::settings()->plugin_message && $ts !== false) {
                            if ($ts === null) {
                                $note = (new Span(__('This task has never been executed.') . ' ' . __('You should execute it now.')))
                                    ->class('warn');
                            } else {
                                $note = (new Span(sprintf(
                                    __('Last execution of this task was on %s.'),
                                    Date::str($date_format, $ts) . ' ' .
                                    Date::str($time_format, $ts)
                                ) . ' ' . __('You should execute it now.')))
                                    ->class('warn');
                            }
                        }

                        $tasks[] = (new Para())
                            ->items([
                                (new Radio(['task', $t->id()]))
                                    ->value($t->id())
                                    ->label((new Label(Html::escapeHTML($t->task()), Label::INSIDE_TEXT_AFTER))),
                                $note,
                            ]);
                    }

                    if ($tasks !== []) {
                        $groups[] = (new Fieldset())
                            ->legend(new Legend($group_obj->name(), $group_obj->id()))
                            ->fields($tasks);
                    }
                }

                if ($groups !== []) {
                    echo (new Div($tab_obj->id()))
                        ->class('multi-part')
                        ->title($tab_obj->name())
                        ->items([
                            (new Text('h3', $tab_obj->name())),
                            (new Form($tab_obj->id() . '-form'))
                                ->method('post')
                                ->action(App::backend()->getPageURL())
                                ->fields([
                                    ...$groups,
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ...My::hiddenFields(),
                                            (new Hidden(['tab'], $tab_obj->id())),
                                            (new Submit([$tab_obj->id() . '-submit'], __('Execute task'))),
                                            (new Button([$tab_obj->id() . '-back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                                        ]),
                                    (new Note())
                                        ->class(['form-note', 'info'])
                                        ->text(__('This may take a very long time.')),
                                ]),
                        ])
                    ->render();
                }
            }

            // Advanced tasks (that required a tab)
            foreach (self::$tasks as $t) {
                if (!$t->id()) {
                    continue;
                }
                if ($t->group() !== null) {
                    continue;
                }

                echo (new Div($t->id()))
                    ->class('multi-part')
                    ->title($t->name())
                    ->items([
                        (new Text('h3', $t->name())),
                        (new Form($t->id() . '-form'))
                            ->method('post')
                            ->action(App::backend()->getPageURL())
                            ->fields([
                                (new Text(null, $t->content())),
                                (new Para())
                                    ->class('form-buttons')
                                    ->items([
                                        ...My::hiddenFields(),
                                        (new Hidden(['task'], $t->id())),
                                        (new Hidden(['tab'], $t->id())),
                                        (new Submit([$t->id() . '-submit'], __('Execute task'))),
                                        (new Button([$t->id() . '-back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                                    ]),
                            ]),
                    ])
                ->render();
            }

            // Settings
            $tasks     = [];
            $ts_global = true;
            $ts_list   = [];
            foreach (self::$tasks as $t) {
                if (!$t->id()) {
                    continue;
                }
                if (!in_array($t->ts(), $ts_list)) {
                    $ts_list[] = $t->ts();
                }
                $label   = $t->ts() ? (new Strong($t->task()))->render() : $t->task();
                $tasks[] = (new Div())
                    ->class('two-boxes')
                    ->items([
                        (new Para())
                            ->class(['field', 'wide'])
                            ->items([
                                (new Select('settings_ts_' . $t->id()))
                                    ->class('recall-per-task')
                                    ->items($combo_ts)
                                    ->default((string) $t->ts())
                                    ->label((new Label($label, Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                    ]);
            }
            $ts_global = count($ts_list) <= 1;

            echo (new Div('settings'))
                ->class('multi-part')
                ->title(__('Alert settings'))
                ->items([
                    (new Text('h3', __('Alert settings'))),
                    (new Form('settings-form'))
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('Activation')))
                                ->fields([
                                    (new Para())
                                        ->items([
                                            (new Checkbox('settings_plugin_message', (bool) My::settings()->plugin_message))
                                                ->value(1)
                                                ->label((new Label(__('Display alert messages on late tasks'), Label::INSIDE_LABEL_AFTER))),
                                        ]),
                                    (new Note())
                                        ->class('info')
                                        ->text(sprintf(
                                            __('You can place list of late tasks on your %s.'),
                                            '<a href="' . App::backend()->url()->get('admin.user.preferences') . '#user-favorites">' . __('Dashboard') . '</a>'
                                        )),
                                ]),
                            (new Fieldset())
                                ->legend(new Legend(__('Frequency')))
                                ->fields([
                                    // All
                                    (new Para())
                                        ->items([
                                            (new Radio(['settings_recall_type', 'settings_recall_all'], $ts_global))
                                                ->value('all')
                                                ->label((new Label(
                                                    (new Strong(__('Use one recall time for all tasks')))->render(),
                                                    Label::INSIDE_TEXT_AFTER
                                                ))),
                                        ]),
                                    (new Para())
                                        ->class(['field', 'wide'])
                                        ->items([
                                            (new Select('settings_recall_time'))
                                                ->class('recall-for-all')
                                                ->items($combo_ts)
                                                ->label((new Label(__('Recall time for all tasks:'), Label::OUTSIDE_TEXT_BEFORE))),
                                        ]),
                                    // Separate
                                    (new Para())
                                        ->class('vertical-separator')
                                        ->items([
                                            (new Radio(['settings_recall_type', 'settings_recall_separate'], !$ts_global))
                                                ->value('separate')
                                                ->label((new Label(
                                                    (new Strong(__('Use one recall time per task')))->render(),
                                                    Label::INSIDE_TEXT_AFTER
                                                ))),
                                        ]),
                                    ...$tasks,
                                ]),
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    ...My::hiddenFields(),
                                    (new Hidden(['tab'], 'settings')),
                                    (new Hidden(['save_settings'], '1')),
                                    (new Submit(['settings-submit'], __('Save'))),
                                    (new Button(['settings-back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                                ]),
                        ]),
                ])
            ->render();

            // System tab
            if (App::auth()->isSuperAdmin()) {
                echo (new Div('system'))
                    ->class('multi-part')
                    ->title(__('System'))
                    ->items([
                        (new Text('h3', __('System settings'))),
                        (new Form('system-form'))
                            ->method('post')
                            ->action(App::backend()->getPageURL())
                            ->fields([
                                (new Fieldset())
                                    ->legend(new Legend(__('Content-Security-Policy')))
                                    ->fields([
                                        (new Div())
                                            ->class('two-cols')
                                            ->items([
                                                (new Div())
                                                    ->class('col')
                                                    ->items([
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp', (bool) App::blog()->settings()->get('system')->csp_admin_on))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Enable Content-Security-Policy system'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp_report_only', (bool) App::blog()->settings()->get('system')->csp_admin_report_only))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Enable Content-Security-Policy report only'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                    ]),
                                                (new Div())
                                                    ->class('col')
                                                    ->items([
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp_global', (bool) App::blog()->settings()->get('system')->getGlobal('csp_admin_on')))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Enable Content-Security-Policy system by default'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp_global_report_only', (bool) App::blog()->settings()->get('system')->getGlobal('csp_admin_report_only')))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Enable Content-Security-Policy report only by default'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp_reset', false))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Also apply these settings to all blogs'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                    ]),
                                            ]),
                                    ]),
                                (new Para())
                                     ->class('form-buttons')
                                     ->items([
                                         ...My::hiddenFields(),
                                         (new Hidden(['tab'], 'system')),
                                         (new Hidden(['save_system'], '1')),
                                         (new Submit(['system-submit'], __('Save'))),
                                         (new Button(['system-back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                                     ]),
                            ]),
                    ])
                ->render();
            }
        }

        App::backend()->page()->helpBlock('maintenance', 'maintenancetasks');

        App::backend()->page()->closeModule();
    }
}
