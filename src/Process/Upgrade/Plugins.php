<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\NextStore;
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Upgrade\PluginsList;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Caption,
    Capture,
    Div,
    Form,
    Hidden,
    Img,
    Li,
    Note,
    Para,
    Set,
    Submit,
    Table,
    Td,
    Text,
    Th,
    Tr,
    Ul
};
use Dotclear\Helper\Html\Html;
use Dotclear\Module\ModuleDefine;
use Throwable;

/**
 * @brief   Upgarde process plugins management page.
 *
 * As we are in safe mode, plugins are soft disabled.
 *
 * @since   2.29
 *
 * @todo switch Helper/Html/Form/...
 */
class Plugins extends Process
{
    private static PluginsList $plugins_list;

    /**
     * @var     array<int, ModuleDefine>    $next_store
     */
    private static array $next_store;

    /**
     * @var     null|array<string, array<string, bool|string>> $plugins_install
     */
    private static ?array $plugins_install = null;

    public static function init(): bool
    {
        Page::checkSuper();

        // Load modules in safe mode
        App::plugins()->safeMode(true);

        try {
            App::plugins()->loadModules(App::config()->pluginsRoot(), 'upgrade', App::lang()->getLang());
        } catch (Throwable) {
            App::error()->add(__('Some plugins could not be loaded.'));
        }

        // -- Page helper --
        self::$plugins_list = new PluginsList(
            App::plugins(),
            App::config()->pluginsRoot(),
            App::blog()->settings()->system->store_plugin_url,
            empty($_GET['nocache']) ? null : true
        );

        PluginsList::$allow_multi_install = App::config()->allowMultiModules();

        # -- Execute actions --
        try {
            self::$plugins_list->doActions();
        } catch (Throwable $e) {
            App::error()->add($e->getMessage());
        }

        if (!empty($_POST['nextstorecheck'])) {
            self::$next_store = (new NextStore(App::plugins(), (string) App::blog()->settings()->get('system')->get('store_plugin_url'), true))->getDefines(true);
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        // Plugins install
        self::$plugins_install = null;
        if (!App::error()->flag()) {
            $do_install = isset($_REQUEST['do_install']);
            if ($do_install) {
                // Add BACKEND context to run plugins installation if necessary
                App::task()->addContext('BACKEND');
            }
            self::$plugins_install = App::plugins()->installModules();
            if (!$do_install) {
                // Messages, only if not already done by installModules() above
                if (!empty(self::$plugins_install['success'])) {
                    $success = [];
                    foreach (array_keys(self::$plugins_install['success']) as $k) {
                        $info      = implode(' - ', self::$plugins_list->getSettingsUrls($k, true));
                        $success[] = $k . ($info !== '' ? ' → ' . $info : '');
                    }
                    Notices::success(
                        __('Following plugins have been installed:') .
                        (new Ul())
                          ->items(array_map(fn ($elt) => ((new Li())->text($elt)), $success))
                        ->render(),
                        false,
                        true
                    );
                    unset($success);
                }
                if (!empty(self::$plugins_install['failure'])) {
                    $failure = [];
                    foreach (self::$plugins_install['failure'] as $k => $v) {
                        $failure[] = $k . ' (' . $v . ')';
                    }

                    Notices::error(
                        __('Following plugins have not been installed:') .
                        (new Ul())
                          ->items(array_map(fn ($elt) => ((new Li())->text($elt)), $failure))
                        ->render(),
                        false,
                        true
                    );
                    unset($failure);
                }
            }
        }

        if (null == App::blog()->settings()->system->store_plugin_url) {
            Notices::AddMessageNotice(__('Official plugins repository could not be updated as there is no URL set in configuration.'));
        }

        if (!App::error()->flag() && !empty($_GET['nocache'])) {
            Notices::AddSuccessNotice(__('Manual checking of plugins update done successfully.'));
        }

        return true;
    }

    public static function render(): void
    {
        // -- Page header --
        Page::open(
            __('Plugins management'),
            (
                empty($_GET['nocache']) && empty($_GET['showupdate']) ?
                Page::jsJson('module_update_url', App::upgrade()->url()->get('upgrade.plugins', ['showupdate' => 1]) . '#update') : ''
            ) .
            Page::jsLoad('js/_plugins.js') .
            Page::jsPageTabs(),
            Page::breadcrumb(
                [
                    __('Dotclear update')    => '',
                    __('Plugins management') => '',
                ]
            )
        );

        // -- Display modules lists --

        $multi_parts = [];

        $parts = [];

        # Activated modules
        $defines = self::$plugins_list->modules->getDefines(
            ['state' => self::$plugins_list->modules->safeMode() ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED]
        );
        if ($defines !== []) {
            $parts[] = (new Set())
                ->items([
                    (new Text(
                        'h3',
                        __('Activated plugins') . ' ' . __('(in normal mode)')
                    )),
                    (new Note())
                        ->class('more-info')
                        ->text(__('You can configure and manage installed plugins from this list.')),
                    (new Capture(self::$plugins_list
                        ->setList('plugin-activate')
                        ->setTab('plugins')
                        ->setDefines($defines)
                        ->displayModules(...), [
                            // cols
                            ['expander', 'name', 'version', 'desc', 'distrib', 'deps'],
                            // actions
                            ['deactivate', 'delete', 'behavior'],
                        ])),
                ]);
        }

        # Deactivated modules
        $defines = self::$plugins_list->modules->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED]);
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
                    (new Capture(self::$plugins_list
                        ->setList('plugin-deactivate')
                        ->setTab('plugins')
                        ->setDefines($defines)
                        ->displayModules(...), [
                            // cols
                            ['expander', 'name', 'version', 'desc', 'distrib'],
                            // actions
                            ['activate', 'delete'],
                        ])),
                ]);
        }

        $multi_parts[] = (new Div('plugins'))
            ->title(__('Installed plugins'))
            ->class('multi-part')
            ->items($parts);

        // Updated modules from repo
        $defines = self::$plugins_list->store->getDefines(true);
        $updates = $defines === [] ? '' : sprintf(' (%s)', count($defines));

        $parts = [];
        if ($defines === []) {
            $parts[] = (new Note())
                ->text(__('No updates available for plugins.'));
        } else {
            $parts[] = (new Note())
                ->text(sprintf(__('There is one plugin update available:', 'There are %s plugin updates available:', count($defines)), count($defines)));

            $parts[] = (new Capture(self::$plugins_list
                ->setList('plugin-update')
                ->setTab('update')
                ->setDefines($defines)
                ->displayModules(...), [
                    // cols
                    ['checkbox', 'name', 'version', 'repository', 'current_version', 'desc'],
                    // actions
                    ['update'],
                ]));

            $parts[] = (new Note())
                ->class(['info', 'vertical-separator'])
                ->text(sprintf(__('Visit %s repository, the resources center for Dotclear.'), '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>'));
        }

        $multi_parts[] = (new Div('update'))
            ->title(Html::escapeHTML(__('Update plugins')) . $updates)
            ->class('multi-part')
            ->items([
                (new Text('h3', __('Update plugins'))),
                (new Form('force-checking'))
                    ->action(self::$plugins_list->getURL('', true, 'update'))
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

        // Check all Modules except from ditrib
        $multi_parts[] = self::nextStoreList(self::$plugins_list, explode(',', App::config()->distributedPlugins()), App::upgrade()->url()->get('upgrade.plugins'));

        if (self::$plugins_list->isWritablePath()) {
            # New modules from repo
            $search  = self::$plugins_list->getSearch();
            $defines = $search ? self::$plugins_list->store->searchDefines($search) : self::$plugins_list->store->getDefines();

            if ($search !== null && $search !== '' || $defines !== []) {
                self::$plugins_list
                    ->setList('plugin-new')
                    ->setTab('new')
                    ->setDefines($defines);

                $multi_parts[] = (new Div('new'))
                    ->title(__('Add plugins'))
                    ->class('multi-part')
                    ->items([
                        (new Text('h3', __('Add plugins from repository'))),
                        (new Capture(self::$plugins_list
                            ->displaySearch(...))),
                        (new Capture(self::$plugins_list
                            ->displayIndex(...))),
                        (new Capture(self::$plugins_list
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
                            ->text(sprintf(__('Visit %s repository, the resources center for Dotclear.'), '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>')),
                    ]);
            }

            # Add a new plugin
            $multi_parts[] = (new Div('addplugin'))
                ->title(__('Install or upgrade manually'))
                ->class('multi-part')
                ->items([
                    (new Text('h3', __('Add plugins from a package'))),
                    (new Note())
                        ->class('more-info')
                        ->text(__('You can install plugins by uploading or downloading zip files.')),
                    (new Capture(self::$plugins_list->displayManualForm(...))),
                ]);
        }

        echo (new Set())
            ->items($multi_parts)
        ->render();

        # -- Notice for super admin --
        if (!self::$plugins_list->isWritablePath()) {
            echo (new Note())
                ->class('warning')
                ->text(__('Some functions are disabled, please give write access to your plugins directory to enable them.'))
            ->render();
        }

        Page::helpBlock('core_plugins');
        Page::close();
    }

    /**
     * @param   array<int, string>  $excludes
     */
    protected static function nextStoreList(PluginsList $plugins, array $excludes, string $page_url): Div
    {
        $list = [];
        // Check ALL modules
        foreach ($plugins->modules->getDefines() as $module) {
            if (is_a($module, ModuleDefine::class) && !in_array($module->getId(), $excludes)) {
                $list[$module->getId()] = $module;
            }
        }

        $items = [];
        if ($list === []) {
            $items[] = (new Note())
                ->text(__('There is no module to check'))
                ->class('info');
        } elseif (isset(self::$next_store)) {
            $items[] = self::displayNextStoreList($list, self::$next_store);
        }

        return (new Div('nextstore'))
            ->class('multi-part')
            ->title(__('Store version'))
            ->items([
                (new Text('h3', __('Check stores versions'))),
                (new Form('nextstoreform'))
                    ->method('post')
                    ->action($page_url . '#nextstore')
                    ->fields([
                        (new Para())
                            ->items([
                                App::nonce()->formNonce(),
                                (new Submit(['nextstorecheck'], __('Check lastest stores versions'))),
                            ]),
                    ]),
                (new Note())
                    ->text(sprintf(__('You can check repositories for modules written explicitly for Dotclear release greater than %s.'), App::config()->dotclearVersion()))
                    ->class('more-info'),
                ...$items,
            ]);
    }

    /**
     * @param   array<string, ModuleDefine>     $modules
     * @param   array<int, ModuleDefine>        $repos
     */
    protected static function displayNextStoreList(array $modules, array $repos): Div
    {
        // regain module ID
        $store = [];
        foreach ($repos as $module) {
            $store[$module->getId()] = $module;
        }

        $trs = [];
        foreach ($modules as $id => $module) {
            if (!isset($store[$id])) {
                $img = [__('No version available'), 'check-off.svg', 'check-off'];
            } elseif (version_compare(App::config()->dotclearVersion(), $store[$id]->get('dc_min'), '>=')) {
                $img = [__('No update available'), 'check-wrn.svg', 'check-wrn'];
            } else {
                $img = [__('Newer version available'), 'check-on.svg', 'check-on'];
            }

            $tds   = [];
            $tds[] = (new Td())
                ->class('module-icon nowrap')
                ->items([
                    (new Img('images/' . $img[1]))
                        ->alt($img[0])
                        ->title($img[0])
                        ->class('mark mark-' . $img[2]),
                ]);
            $tds[] = (new Td())
                ->class('module-name nowrap')
                ->text(Html::escapeHTML($module->get('name')) . ($id != $module->get('name') ? sprintf(__(' (%s)'), $id) : ''));

            if (isset($store[$id])) {
                $tds[] = (new Td())
                    ->class('module-version nowrap count')
                    ->text(Html::escapeHTML($store[$id]->get('current_version')));
                $tds[] = (new Td())
                    ->class('module-version nowrap count maximal')
                    ->text(Html::escapeHTML($store[$id]->get('version')));
                $tds[] = (new Td())
                    ->class('module-version nowrap count')
                    ->text(Html::escapeHTML($store[$id]->get('dc_min')));

                if (App::config()->allowRepositories()) {
                    $tds[] = (new Td())
                        ->class('module-repository nowrap count')
                        ->text((empty($module->get('repository')) ? __('Official repository') : __('Third-party repository')));
                }
            } else {
                $tds[] = (new Td())
                    ->class('module-current-version nowrap count')
                    ->text(Html::escapeHTML($module->get('version')));
                $tds[] = (new Td())
                    ->class('module-version nowrap count maximal')
                    ->colspan(3)
                    ->text(Html::escapeHTML(__('No version available')));
            }

            $trs[] = (new Tr('mvmodules_m_' . Html::escapeHTML((string) $id)))
                ->class('line' . (!isset($store[$id]) || $module->get('version') == $store[$id]->get('version') ? ' offline' : ''))
                ->items($tds);
        }

        return (new Div())
            ->class('table-outer')
            ->items([
                (new Table('mvmodules'))
                    ->class('modules')
                    ->items([
                        (new Caption(Html::escapeHTML(__('Modules list'))))
                            ->class('hidden'),
                        (new Tr())
                            ->items([
                                (new Th())
                                    ->class('first nowrap')
                                    ->colspan(2)
                                    ->text(__('Name')),
                                (new Th())
                                    ->class('nowrap count')
                                    ->scope('col')
                                    ->text(__('Current version')),
                                (new Th())
                                    ->class('nowrap count')
                                    ->scope('col')
                                    ->text(__('Latest version')),
                                (new Th())
                                    ->class('nowrap count')
                                    ->scope('col')
                                    ->text(__('Written for Dotclear')),
                                (new Th())
                                    ->class('nowrap')
                                    ->scope('col')
                                    ->text(__('Repository')),
                            ]),
                        ...$trs,
                    ]),
            ]);
    }
}
