<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Module\ModuleDefine;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/plugins.php
 */
class Plugins
{
    use TraitProcess;

    public static function init(): bool
    {
        // backward compat
        App::backend()->list = App::backend()->modulesList();

        App::backend()->modulesList()::$allow_multi_install = App::config()->allowMultiModules();
        // deprecated since 2.26
        App::backend()->modulesList()::$distributed_modules = explode(',', App::config()->distributedPlugins());

        $disabled = App::plugins()->disableDepModules();
        if ($disabled !== []) {
            App::backend()->notices()->addWarningNotice(
                (new Div())
                    ->items([
                        (new Note())
                            ->text(__('The following plugins have been disabled :')),
                        (new Ul())
                            ->items(
                                array_map(fn (string $item) => (new Li())->text($item), $disabled)
                            ),
                    ])
                ->render(),
                ['divtag' => true, 'with_ts' => false]
            );

            App::backend()->url()->redirect('admin.plugins');
            dotclear_exit();
        }

        if (App::backend()->modulesList()->setConfiguration()) {
            // -- Display module configuration page --
            self::renderConfig();

            // Stop reading code here, rendering will be done before returning (see below)
            return self::status(false);
        }

        App::backend()->page()->checkSuper();

        # -- Execute actions --
        try {
            App::backend()->modulesList()->doActions();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        // -- Plugin install --
        App::backend()->plugins_install = null;
        if (!App::error()->flag()) {
            App::backend()->plugins_install = App::plugins()->installModules();
        }

        return true;
    }

    public static function render(): void
    {
        // -- Page header --
        App::backend()->page()->open(
            __('Plugins management'),
            (
                empty($_GET['nocache']) && empty($_GET['showupdate']) ?
                App::backend()->page()->jsJson('module_update_url', App::backend()->url()->get('admin.plugins', ['showupdate' => 1]) . '#update') : ''
            ) .
            App::backend()->page()->jsLoad('js/_plugins.js') .
            App::backend()->page()->jsPageTabs() .

            # --BEHAVIOR-- pluginsToolsHeaders -- bool
            App::behavior()->callBehavior('pluginsToolsHeadersV2', false),
            App::backend()->page()->breadcrumb(
                [
                    __('System')             => '',
                    __('Plugins management') => '',
                ]
            )
        );

        // -- Plugins install messages --
        if (!empty(App::backend()->plugins_install['success'])) {
            $success = [];
            foreach (App::backend()->plugins_install['success'] as $k => $v) {
                $info      = implode(' - ', App::backend()->modulesList()->getSettingsUrls($k, true));
                $success[] = $k . ($info !== '' ? ' → ' . $info : '');
            }
            App::backend()->notices()->success(
                (new Div())
                    ->items([
                        (new Note())
                            ->text(__('Following plugins have been installed:')),
                        (new Ul())
                            ->items(
                                array_map(fn (string $item) => (new Li())->text($item), $success)
                            ),
                    ])
                ->render(),
                false,
                true
            );
            unset($success);
        }
        if (!empty(App::backend()->plugins_install['failure'])) {
            $failure = [];
            foreach (App::backend()->plugins_install['failure'] as $k => $v) {
                $failure[] = $k . ' (' . $v . ')';
            }

            App::backend()->notices()->error(
                (new Div())
                    ->items([
                        (new Note())
                            ->text(__('Following plugins have not been installed:')),
                        (new Ul())
                            ->items(
                                array_map(fn (string $item) => (new Li())->text($item), $failure)
                            ),
                    ])
                ->render(),
                false,
                true
            );
            unset($failure);
        }

        // -- Display modules lists --

        $multi_parts = [];

        $parts = [];

        # Activated modules
        $defines = App::backend()->modulesList()->modules->getDefines(
            ['state' => App::backend()->modulesList()->modules->safeMode() ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED]
        );
        if ($defines !== []) {
            $parts[] = (new Set())
                ->items([
                    (new Text(
                        'h3',
                        (App::auth()->isSuperAdmin() ? __('Activated plugins') : __('Installed plugins')) .
                        (App::backend()->modulesList()->modules->safeMode() ? ' ' . __('(in normal mode)') : '')
                    )),
                    (new Note())
                        ->class('more-info')
                        ->text(__('You can configure and manage installed plugins from this list.')),
                    (new Capture(App::backend()->modulesList()
                        ->setList('plugin-activate')
                        ->setTab('plugins')
                        ->setDefines($defines)
                        ->displayModules(...), [
                            // cols
                            ['expander', 'icon', 'name', 'version', 'desc', 'distrib', 'deps'],
                            // actions
                            ['deactivate', 'delete', 'behavior'],
                        ])),
                ]);
        }

        # Deactivated modules
        if (App::auth()->isSuperAdmin()) {
            $defines = App::backend()->modulesList()->modules->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED]);
            if ($defines !== []) {
                $parts[] = (new Set())
                    ->items([
                        (new Text(
                            'h3',
                            __('Deactivated plugins')
                        )),
                        (new Note())
                            ->class('more-info')
                            ->text(__('Deactivated plugins are installed but not usable. You can activate them from here.')),
                        (new Capture(App::backend()->modulesList()
                            ->setList('plugin-deactivate')
                            ->setTab('plugins')
                            ->setDefines($defines)
                            ->displayModules(...), [
                                // cols
                                ['expander', 'icon', 'name', 'version', 'desc', 'distrib'],
                                // actions
                                ['activate', 'delete'],
                            ])),
                    ]);
            }
        }

        $multi_parts[] = (new Div('plugins'))
            ->title(__('Installed plugins'))
            ->class('multi-part')
            ->items($parts);

        // Updatable modules
        if (App::auth()->isSuperAdmin()) {
            if (null == App::blog()->settings()->system->store_plugin_url) {
                App::backend()->notices()->message(__('Official repository could not be updated as there is no URL set in configuration.'));
            }

            if (!App::error()->flag() && !empty($_GET['nocache'])) {
                App::backend()->notices()->success(__('Manual checking of plugins update done successfully.'));
            }

            // Updated modules from repo
            $defines = App::backend()->modulesList()->store->getDefines(true);

            $tmp = new ArrayObject($defines);

            # --BEHAVIOR-- afterCheckStoreUpdate -- string, ArrayObject<int, ModuleDefine>
            App::behavior()->callBehavior('afterCheckStoreUpdate', 'plugins', $tmp);

            $defines = $tmp->getArrayCopy();
            $updates = empty($defines) ? '' : sprintf(' (%s)', count($defines));

            $parts = [];
            if (empty($defines)) {
                $parts[] = (new Note())
                    ->text(__('No updates available for plugins.'));
            } else {
                $parts[] = (new Note())
                    ->text(sprintf(__('There is one plugin update available:', 'There are %s plugin updates available:', count($defines)), count($defines)));

                $parts[] = (new Capture(App::backend()->modulesList()
                    ->setList('plugin-update')
                    ->setTab('update')
                    ->setDefines($defines)
                    ->displayModules(...), [
                        // cols
                        ['checkbox', 'icon', 'name', 'version', 'repository', 'current_version', 'desc'],
                        // actions
                        ['update', 'behavior'],
                    ]));

                $parts[] = (new Note())
                    ->class(['info', 'vertical-separator'])
                    ->text(sprintf(
                        __('Visit %s plugins repository.'),
                        '<a href="https://dotclear.org/plugin/list">Dotclear</a>'
                    ));
            }

            $multi_parts[] = (new Div('update'))
                ->title(Html::escapeHTML(__('Update plugins')) . $updates)
                ->class('multi-part')
                ->items([
                    (new Text('h3', __('Update plugins'))),
                    (new Form('force-checking'))
                        ->action(App::backend()->modulesList()->getURL('', true, 'update'))
                        ->method('get')
                        ->fields([
                            (new Para())
                            ->items([
                                (new Hidden('nocache', '1')),
                                (new Hidden(['process'], 'Plugins')),
                                (new Submit('force-checking-update', __('Force checking update of plugins'))),
                            ]),
                        ]),
                    ... $parts,
                ]);
        }

        if (App::auth()->isSuperAdmin() && App::backend()->modulesList()->isWritablePath()) {
            # New modules from repo
            $search  = App::backend()->modulesList()->getSearch();
            $defines = $search ? App::backend()->modulesList()->store->searchDefines($search) : App::backend()->modulesList()->store->getDefines();

            App::backend()->modulesList()
                ->setList('plugin-new')
                ->setTab('new')
                ->setDefines($defines);

            $multi_parts[] = (new Div('new'))
                ->title(__('Add plugins'))
                ->class('multi-part')
                ->items([
                    (new Text('h3', __('Add plugins from repository'))),
                    (new Capture(App::backend()->modulesList()
                        ->displaySearch(...))),
                    (new Capture(App::backend()->modulesList()
                        ->displayIndex(...))),
                    (new Capture(App::backend()->modulesList()
                        ->displayModules(...), [
                            // cols
                            ['expander', 'name', 'score', 'version', 'desc', 'deps'],
                            // actions
                            ['install'],
                            // nav limit
                            true,
                        ])),
                    (new Note())
                        ->class(['info', 'vertical-separator'])
                        ->text(sprintf(
                            __('Visit %s plugins repository.'),
                            '<a href="https://dotclear.org/plugin/list">Dotclear</a>'
                        )),
                ]);

            # Add a new plugin
            $multi_parts[] = (new Div('addplugin'))
                ->title(__('Install or upgrade manually'))
                ->class('multi-part')
                ->items([
                    (new Text('h3', __('Add plugins from a package'))),
                    (new Note())
                        ->class('more-info')
                        ->text(__('You can install plugins by uploading or downloading zip files.')),
                    (new Capture(App::backend()->modulesList()->displayManualForm(...))),
                ]);
        }

        # --BEHAVIOR-- pluginsToolsTabs --
        $multi_parts[] = (new Capture(App::behavior()->callBehavior(...), ['pluginsToolsTabsV2']));

        echo (new Set())
            ->items($multi_parts)
        ->render();

        # -- Notice for super admin --
        if (App::auth()->isSuperAdmin() && !App::backend()->modulesList()->isWritablePath()) {
            echo (new Note())
                ->class('warning')
                ->text(__('Some functions are disabled, please give write access to your plugins directory to enable them.'))
            ->render();
        }

        App::backend()->page()->helpBlock('core_plugins');
        App::backend()->page()->close();
    }

    /**
     * Renders plugin configuration page.
     */
    public static function renderConfig(): void
    {
        // Get content before page headers
        $include = App::backend()->modulesList()->includeConfiguration();
        if ($include) {
            include $include;
        }

        // Gather content
        App::backend()->modulesList()->getConfiguration();

        // Display page
        App::backend()->page()->open(
            __('Plugins management'),

            # --BEHAVIOR-- pluginsToolsHeaders -- bool
            App::behavior()->callBehavior('pluginsToolsHeadersV2', true),
            App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name())                                 => '',
                    __('Plugins management')                                              => App::backend()->modulesList()->getURL('', false),
                    (new Span(__('Plugin configuration')))->class('page-title')->render() => '',
                ]
            )
        );

        // Display previously gathered content
        App::backend()->modulesList()->displayConfiguration();

        if (!App::backend()->resources()->context()) {
            App::backend()->page()->helpBlock('core_plugins_conf');
        }
        App::backend()->page()->close();
    }
}
