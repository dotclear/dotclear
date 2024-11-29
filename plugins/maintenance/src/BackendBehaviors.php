<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Helper;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Dd;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Dl;
use Dotclear\Helper\Html\Form\Dt;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;

/**
 * @brief   The module backend behaviors.
 * @ingroup maintenance
 */
class BackendBehaviors
{
    /**
     * Register default tasks.
     *
     * @param   Maintenance     $maintenance    Maintenance instance
     */
    public static function dcMaintenanceInit(Maintenance $maintenance): void
    {
        $maintenance
            ->addTab('maintenance', __('Servicing'), ['summary' => __('Tools to maintain the performance of your blogs.')])
            ->addTab('backup', __('Backup'), ['summary' => __('Tools to back up your content.')])
            ->addTab('dev', __('Development'), ['summary' => __('Tools to assist in development of plugins, themes and core.')])

            ->addGroup('optimize', __('Optimize'))
            ->addGroup('index', __('Count and index'))
            ->addGroup('purge', __('Purge'))
            ->addGroup('other', __('Other'))
            ->addGroup('zipblog', __('Current blog'))
            ->addGroup('zipfull', __('All blogs'))

            ->addGroup('l10n', __('Translations'), ['summary' => __('Maintain translations')])

            ->addTask(Task\Cache::class)
            ->addTask(Task\CSP::class)
            ->addTask(Task\IndexPosts::class)
            ->addTask(Task\IndexComments::class)
            ->addTask(Task\CountComments::class)
            ->addTask(Task\SynchPostsMeta::class)
            ->addTask(Task\Logs::class)
            ->addTask(Task\Vacuum::class)
            ->addTask(Task\ZipMedia::class)
            ->addTask(Task\ZipTheme::class)
        ;
    }

    /**
     * Favorites.
     *
     * @param   Favorites   $favs   favs
     */
    public static function adminDashboardFavorites(Favorites $favs): void
    {
        $favs->register(My::id(), [
            'title'       => My::name(),
            'url'         => My::manageUrl(),
            'small-icon'  => My::icons(),
            'large-icon'  => My::icons(),
            'permissions' => App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]),
            'active_cb'    => self::adminDashboardFavoritesActive(...),
            'dashboard_cb' => self::adminDashboardFavoritesCallback(...),
        ]);
    }

    /**
     * Is maintenance plugin active.
     *
     * @param   string                  $request    The request
     * @param   array<string, mixed>    $params     The parameters
     *
     * @return  bool    True if maintenance plugin is active else false
     */
    public static function adminDashboardFavoritesActive(string $request, array $params): bool
    {
        return isset($params['p']) && $params['p'] == My::id();
    }

    /**
     * Favorites hack.
     *
     * This updates maintenance fav icon text
     * if there are tasks required maintenance.
     *
     * @param   arrayObject<string, mixed>     $icon   The icon
     */
    public static function adminDashboardFavoritesCallback(ArrayObject $icon): void
    {
        // Check user option
        if (!My::prefs()->dashboard_icon) {
            return;
        }

        // Check expired tasks
        $maintenance = new Maintenance();
        $count       = 0;
        foreach ($maintenance->getTasks() as $t) {
            if ($t->expired() !== false) {
                $count++;
            }
        }

        if (!$count) {
            return;
        }

        $icon['title'] .= '<br>' . sprintf(__('One task to execute', '%s tasks to execute', $count), $count);
        $icon['large-icon'] = My::icons('update');
    }

    /**
     * Dashboard header behavior
     *
     * @return     string
     */
    public static function adminDashboardHeaders(): string
    {
        return My::jsLoad('dashboard');
    }

    /**
     * Dashboard items stack.
     *
     * @param   ArrayObject<int, mixed>     $items  items
     */
    public static function adminDashboardItems(ArrayObject $items): void
    {
        if (!My::prefs()->dashboard_item) {
            return;
        }

        $maintenance = new Maintenance();

        $lines = [];
        foreach ($maintenance->getTasks() as $t) {
            $ts = $t->expired();
            if ($ts === false) {
                continue;
            }

            $lines[] = (new Li())
                ->title($ts === null ?
                __('This task has never been executed.')
                :
                sprintf(
                    __('Last execution of this task was on %s.'),
                    Date::dt2str(App::blog()->settings()->system->date_format, (string) $ts) . ' ' .
                    Date::dt2str(App::blog()->settings()->system->time_format, (string) $ts)
                ))
                ->text($t->task());
        }

        if (empty($lines)) {
            return;
        }

        $items->append(new ArrayObject([
            (new Div('maintenance-expired'))
                ->class(['box', 'small'])
                ->items([
                    (new Text('h3', Helper::adminIcon(My::icons(), true, '', '', 'icon-small') . ' ' . __('Maintenance'))),
                    (new Note())
                        ->class(['warning', 'no-margin'])
                        ->text(sprintf(__('There is a task to execute.', 'There are %s tasks to execute.', count($lines)), count($lines))),
                    (new Ul())
                        ->items($lines),
                    (new Para())
                        ->items([
                            (new Link())
                                ->href(My::manageUrl())
                                ->text(__('Manage tasks')),
                        ]),
                ])
            ->render(),
        ]));
    }

    /**
     * User preferences form.
     *
     * This add options for superadmin user
     * to show or not expired taks.
     */
    public static function adminDashboardOptionsForm(): void
    {
        echo (new Fieldset())
            ->legend(new Legend(My::name()))
            ->fields([
                (new Para())
                    ->items([
                        (new Checkbox('maintenance_dashboard_icon', (bool) My::prefs()->dashboard_icon))
                            ->value(1)
                            ->label((new Label(__('Display overdue tasks counter on maintenance dashboard icon'), Label::INSIDE_TEXT_AFTER))),
                    ]),
                (new Para())
                    ->items([
                        (new Checkbox('maintenance_dashboard_item', (bool) My::prefs()->dashboard_item))
                            ->value(1)
                            ->label((new Label(__('Display overdue tasks list on dashboard items'), Label::INSIDE_TEXT_AFTER))),
                    ]),
            ])
        ->render();
    }

    /**
     * User preferences update.
     *
     * @param   string  $user_id    The user identifier
     */
    public static function adminAfterDashboardOptionsUpdate(?string $user_id = null): void
    {
        if (is_null($user_id)) {
            return;
        }

        My::prefs()->put('dashboard_icon', !empty($_POST['maintenance_dashboard_icon']), 'boolean');
        My::prefs()->put('dashboard_item', !empty($_POST['maintenance_dashboard_item']), 'boolean');
    }

    /**
     * Build a well sorted help for tasks.
     *
     * This method is not so good if used with lot of tranlsations
     * as it grows memory usage and translations files size,
     * it is better to use help ressource files
     * but keep it for exemple of how to use behavior adminPageHelpBlock.
     * Cheers, JC
     *
     * @param   ArrayObject<int, mixed>     $blocks     The blocks
     */
    public static function adminPageHelpBlock(ArrayObject $blocks): void
    {
        if (in_array('maintenancetasks', $blocks->getArrayCopy(), true)) {
            $maintenance = new Maintenance();

            $contents = [];
            foreach ($maintenance->getTabs() as $tab_obj) {
                $groups = [];
                foreach ($maintenance->getGroups() as $group_obj) {
                    $tasks = [];
                    foreach ($maintenance->getTasks() as $t) {
                        if ($t->group()  != $group_obj->id()
                            || $t->tab() != $tab_obj->id()) {
                            continue;
                        }
                        $desc = $t->description() ?: '';
                        if ($desc !== '') {
                            $tasks[] = (new Set())
                                ->items([
                                    (new Dt())->text($t->task()),
                                    (new Dd())->text($desc),
                                ]);
                        }
                    }
                    if (!empty($tasks)) {
                        $desc = $group_obj->description ?: $group_obj->summary; // @phpstan-ignore-line

                        $groups[] = (new Set())
                            ->items([
                                (new Text('h5', $group_obj->name())),
                                ($desc ? (new Note())->text($desc) : (new None())),
                                (new Dl())->items($tasks),
                            ]);
                    }
                }
                if (!empty($groups)) {
                    $desc = $tab_obj->description ?: $tab_obj->summary; // @phpstan-ignore-line

                    $contents[] = (new Set())
                        ->items([
                            (new Text('h4', $tab_obj->name())),
                            ($desc ? (new Note())->text($desc) : (new None())),
                            ...$groups,
                        ]);
                }
            }
            if (!empty($contents)) {
                $res          = new AdminPageHelpBlockContent();
                $res->content = (new Set())->items($contents)->render();
                $blocks->append($res);
            }
        }
    }
}
