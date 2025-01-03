<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
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
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup maintenance
 */
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

            if (!App::backend()->task instanceof MaintenanceTask) {
                App::error()->add('Unknown task ID');
            }

            App::backend()->task?->code(App::backend()->code);
        }

        return self::status(true);
    }

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
                App::blog()->settings()->system->put('csp_admin_on', !empty($_POST['system_csp_global']), null, null, true, true);
                App::blog()->settings()->system->put('csp_admin_report_only', !empty($_POST['system_csp_global_report_only']), null, null, true, true);
                // Current blog settings
                App::blog()->settings()->system->put('csp_admin_on', !empty($_POST['system_csp']));
                App::blog()->settings()->system->put('csp_admin_report_only', !empty($_POST['system_csp_report_only']));

                Notices::addSuccessNotice(__('System settings have been saved.'));

                if (!empty($_POST['system_csp_reset'])) {
                    App::blog()->settings()->system->dropEvery('csp_admin_on');
                    App::blog()->settings()->system->dropEvery('csp_admin_report_only');
                    Notices::addSuccessNotice(__('All blog\'s Content-Security-Policy settings have been reset to default.'));
                }

                My::redirect(['tab' => App::backend()->tab], '#' . App::backend()->tab);
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
            __('Never')            => (string) 0,
            __('Every week')       => (string) 604800,
            __('Every two weeks')  => (string) 1_209_600,
            __('Every month')      => (string) 2_592_000,
            __('Every two months') => (string) 5_184_000,
        ];

        // Display page

        $head = Page::jsPageTabs(App::backend()->tab) .
            My::jsLoad('settings') .
            My::cssLoad('style');
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
            );

            Notices::warning(__('You have not sufficient permissions to view this page.'), false);

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

            // Content
            if (str_starts_with((string) $res, '<')) {
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
                        ->href(App::backend()->getPageURL() . '&amp;tab=' . App::backend()->task->tab() . '#' . App::backend()->task->tab())
                        ->class('back')
                        ->text(__('Back')),
                ])
            ->render();

            echo (new Form('step-form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields([
                    (new Fieldset(App::backend()->task->id()))
                        ->class('step-box')
                        ->fields([
                            $content,
                            (new Para())->class(['step-submit', 'form-buttons'])->items([
                                ...My::hiddenFields(),
                                (new Hidden(['task'], App::backend()->task->id())),
                                (new Hidden(['code'], (string) App::backend()->code)),
                                (new Submit(['step-submit-button'], App::backend()->task->task())),
                            ]),
                        ]),
                ])
            ->render();
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
                $groups = [];
                foreach (App::backend()->maintenance->getGroups() as $group_obj) {
                    $tasks = [];
                    foreach (App::backend()->tasks as $t) {
                        if (!$t->id()
                        || $t->group() != $group_obj->id()
                        || $t->tab()   != $tab_obj->id()) {
                            continue;
                        }

                        // Expired task alert message
                        $ts   = $t->expired();
                        $note = '';
                        if (My::settings()->plugin_message && $ts !== false) {
                            if ($ts === null) {
                                $note = '<span class="warn">' . __('This task has never been executed.') . ' ' . __('You should execute it now.') . '</span>';
                            } else {
                                $note = '<span class="warn">' . sprintf(
                                    __('Last execution of this task was on %s.'),
                                    Date::str(App::blog()->settings()->system->date_format, $ts) . ' ' .
                                    Date::str(App::blog()->settings()->system->time_format, $ts)
                                ) . ' ' . __('You should execute it now.') . '</span>';
                            }
                        }

                        $tasks[] = (new Para())
                            ->items([
                                (new Radio(['task', $t->id()]))
                                    ->value($t->id())
                                    ->label((new Label(Html::escapeHTML($t->task()), Label::INSIDE_TEXT_AFTER))),
                                ($note !== '' ? (new Text(null, $note)) : (new None())),
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
            foreach (App::backend()->tasks as $t) {
                if (!$t->id() || $t->group() !== null) {
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
            $tasks = [];
            foreach (App::backend()->tasks as $t) {
                if (!$t->id()) {
                    continue;
                }
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
                                    ->label((new Label($t->task(), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                    ]);
            }

            echo (new Div('settings'))
                ->class('multi-part')
                ->title(__('Alert settings'))
                ->items([
                    (new Text('h3', __('Alert settings'))),
                    (new Fieldset())
                        ->legend(new Legend(__('Activation')))
                        ->fields([
                            (new Para())
                                ->items([
                                    (new Checkbox('settings_plugin_message', My::settings()->plugin_message))
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
                                    (new Radio(['settings_recall_type', 'settings_recall_all']))
                                        ->value('all')
                                        ->label((new Label(
                                            (new Text('strong', __('Use one recall time for all tasks')))->render(),
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
                                    (new Radio(['settings_recall_type', 'settings_recall_separate']))
                                        ->value('separate')
                                        ->label((new Label(
                                            (new Text('strong', __('Use one recall time per task')))->render(),
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
                ])
            ->render();

            // System tab
            if (App::auth()->isSuperAdmin()) {
                echo (new Div())
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
                                                                (new Checkbox('system_csp', (bool) App::blog()->settings()->system->csp_admin_on))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Enable Content-Security-Policy system'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp_report_only', (bool) App::blog()->settings()->system->csp_admin_report_only))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Enable Content-Security-Policy report only'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                    ]),
                                                (new Div())
                                                    ->class('col')
                                                    ->items([
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp_global', (bool) App::blog()->settings()->system->getGlobal('csp_admin_on')))
                                                                    ->value(1)
                                                                    ->label((new Label(__('Enable Content-Security-Policy system by default'), Label::INSIDE_LABEL_AFTER))),
                                                            ]),
                                                        (new Para())
                                                            ->items([
                                                                (new Checkbox('system_csp_global_report_only', (bool) App::blog()->settings()->system->getGlobal('csp_admin_report_only')))
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

        Page::helpBlock('maintenance', 'maintenancetasks');

        Page::closeModule();
    }
}
