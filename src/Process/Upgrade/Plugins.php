<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\NextStore;
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Upgrade\PluginsList;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief   Upgarde process plugins management page.
 *
 * As we are in safe mode, plugins are soft disabled.
 *
 * @since   2.29
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

        // -- Page helper --
        self::$plugins_list = new PluginsList(
            App::plugins(),
            App::config()->pluginsRoot(),
            App::blog()->settings()->system->store_plugin_url,
            !empty($_GET['nocache']) ? true : null
        );

        PluginsList::$allow_multi_install = App::config()->allowMultiModules();

        # -- Execute actions --
        try {
            self::$plugins_list->doActions();
        } catch (Exception $e) {
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
            self::$plugins_install = App::plugins()->installModules();
        }

        // Messages
        if (!empty(self::$plugins_install['success'])) {
            $success = [];
            foreach (self::$plugins_install['success'] as $k => $v) {
                $info      = implode(' - ', self::$plugins_list->getSettingsUrls($k, true));
                $success[] = $k . ($info !== '' ? ' → ' . $info : '');
            }
            Notices::AddSuccessNotice(
                __('Following plugins have been installed:') .
                '<ul><li>' . implode("</li>\n<li>", $success) . '</li></ul>'
            );
            unset($success);
        }
        if (!empty(self::$plugins_install['failure'])) {
            $failure = [];
            foreach (self::$plugins_install['failure'] as $k => $v) {
                $failure[] = $k . ' (' . $v . ')';
            }

            Notices::AddErrorNotice(
                __('Following plugins have not been installed:') .
                '<ul><li>' . implode("</li>\n<li>", $failure) . '</li></ul>'
            );
            unset($failure);
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

        // Updated modules from repo
        $defines = self::$plugins_list->store->getDefines(true);
        echo
        '<div class="multi-part" id="update" title="' . Html::escapeHTML(__('Update plugins')) . '">' .
        '<h3>' . Html::escapeHTML(__('Update plugins')) . '</h3>' .
        (new Form('force-checking'))
            ->action(self::$plugins_list->getURL('', false))
            ->method('get')
            ->fields([
                (new Para())
                ->items([
                    (new Hidden('nocache', '1')),
                    (new Hidden('process', 'Plugins')),
                    (new Submit('force-checking-update', __('Force checking update of plugins'))),
                ]),
            ])
            ->render();

        if (empty($defines)) {
            echo
            '<p>' . sprintf('There are %s plugins to update available from repository.', 0) . '</p>';
        } else {
            echo
            '<p>' . sprintf(
                __('There is one plugin to update available from repository.', 'There are %s plugins to update available from repository.', count($defines)),
                count($defines)
            ) . '</p>';

            self::$plugins_list
                ->setList('plugin-update')
                ->setTab('update')
                ->setDefines($defines)
                ->displayModules(
                    /* cols */
                    ['checkbox', 'name', 'version', 'repository', 'current_version', 'desc'],
                    /* actions */
                    ['update']
                );

            echo
            '<p class="info vertical-separator">' . sprintf(
                __('Visit %s repository, the resources center for Dotclear.'),
                '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>'
            ) .
            '</p>';
        }

        echo
        '</div>';

        echo
        '<div class="multi-part" id="plugins" title="' . __('Installed plugins') . '">';

        # Activated modules
        $defines = self::$plugins_list->modules->getDefines(
            ['state' => self::$plugins_list->modules->safeMode() ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED]
        );
        if (!empty($defines)) {
            echo
            '<h3>' .
            __('Activated plugins') . ' ' . __('(in normal mode)') .
            '</h3>' .
            '<p class="more-info">' . __('You can configure and manage installed plugins from this list.') . '</p>';

            self::$plugins_list
                ->setList('plugin-activate')
                ->setTab('plugins')
                ->setDefines($defines)
                ->displayModules(
                    /* cols */
                    ['expander', 'name', 'version', 'desc', 'distrib', 'deps'],
                    /* actions */
                    ['deactivate', 'delete', 'behavior']
                );
        }

        # Deactivated modules
        if (App::auth()->isSuperAdmin()) {
            $defines = self::$plugins_list->modules->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED]);
            if (!empty($defines)) {
                echo
                '<h3>' . __('Deactivated plugins') . '</h3>' .
                '<p class="more-info">' . __('Deactivated plugins are installed but not usable. You can activate them from here.') . '</p>';

                self::$plugins_list
                    ->setList('plugin-deactivate')
                    ->setTab('plugins')
                    ->setDefines($defines)
                    ->displayModules(
                        /* cols */
                        ['expander', 'name', 'version', 'desc', 'distrib'],
                        /* actions */
                        ['activate', 'delete']
                    );
            }
        }

        echo
        '</div>';

        // Check all Modules except from ditrib
        self::nextStoreList(self::$plugins_list, explode(',', App::config()->distributedPlugins()), App::upgrade()->url()->get('upgrade.plugins'));

        if (self::$plugins_list->isWritablePath()) {
            # New modules from repo
            $search  = self::$plugins_list->getSearch();
            $defines = $search ? self::$plugins_list->store->searchDefines($search) : self::$plugins_list->store->getDefines();

            if (!empty($search) || !empty($defines)) {
                echo
                '<div class="multi-part" id="new" title="' . __('Add plugins') . '">' .
                '<h3>' . __('Add plugins from repository') . '</h3>';

                self::$plugins_list
                    ->setList('plugin-new')
                    ->setTab('new')
                    ->setDefines($defines)
                    ->displaySearch()
                    ->displayIndex()
                    ->displayModules(
                        /* cols */
                        ['expander', 'name', 'score', 'version', 'desc', 'deps'],
                        /* actions */
                        ['install'],
                        /* nav limit */
                        true
                    );

                echo
                '<p class="info vertical-separator">' . sprintf(
                    __('Visit %s repository, the resources center for Dotclear.'),
                    '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>'
                ) .
                '</p>' .

                '</div>';
            }

            # Add a new plugin
            echo
            '<div class="multi-part" id="addplugin" title="' . __('Install or upgrade manually') . '">' .
            '<h3>' . __('Add plugins from a package') . '</h3>' .
            '<p class="more-info">' . __('You can install plugins by uploading or downloading zip files.') . '</p>';

            self::$plugins_list->displayManualForm();

            echo
            '</div>';
        }

        # -- Notice for super admin --
        if (!self::$plugins_list->isWritablePath()) {
            echo
            '<p class="warning">' . __('Some functions are disabled, please give write access to your plugins directory to enable them.') . '</p>';
        }

        Page::helpBlock('core_plugins');
        Page::close();
    }

    /**
     * @param   array<int, string>  $excludes
     */
    protected static function nextStoreList(PluginsList $plugins, array $excludes, string $page_url): void
    {
        echo
        '<div class="multi-part" id="nextstore" title="' . __('Store version') . '">' .
        '<h3>' . __('Check stores versions') . '</h3>' .

        '<form method="post" action="' . $page_url . '#nextstore" id="nextstoreform">' .
        '<p><input type="submit" name="nextstorecheck" value="' . __('Check lastest stores versions') . '" />' .
        App::nonce()->getFormNonce() . '</p>' .
        '</form>' .

        '<p class="more-info">' . sprintf(__('You can check repositories for modules written explicitly for Dotclear release greater than %s.'), App::config()->dotclearVersion()) . '</p>';

        $list = [];
        // Check ALL modules
        foreach ($plugins->modules->getDefines() as $module) {
            if (is_a($module, ModuleDefine::class) && !in_array($module->getId(), $excludes)) {
                $list[$module->getId()] = $module;
            }
        }

        if (!count($list)) {
            echo
            '<div class="info">' . __('There is no module to check') . '</div>' .
            '</div>';

            return;
        }

        if (isset(self::$next_store)) {
            self::displayNextStoreList($list, self::$next_store);
        }

        echo
        '</div>';
    }

    /**
     * @param   array<string, ModuleDefine>     $modules
     * @param   array<int, ModuleDefine>        $repos
     */
    protected static function displayNextStoreList(array $modules, array $repos): void
    {
        echo
        '<div class="table-outer">' .
        '<table id="mvmodules" class="modules">' .
        '<caption class="hidden">' . Html::escapeHTML(__('Modules list')) . '</caption><tr>' .
        '<th class="first nowrap" colspan="2">' . __('Name') . '</th>' .
        '<th class="nowrap count" scope="col">' . __('Current version') . '</th>' .
        '<th class="nowrap count" scope="col">' . __('Latest version') . '</th>' .
        '<th class="nowrap count" scope="col">' . __('Written for Dotclear') . '</th>';

        if (App::config()->allowRepositories()) {
            echo
            '<th class="nowrap count" scope="col">' . __('Repository') . '</th>';
        }

        // regain module ID
        $store = [];
        foreach($repos as $module) {
            $store[$module->getId()] = $module;
        }

        foreach ($modules as $id => $module) {
            if (!isset($store[$id])) {
                $img = [__('No version available'), 'check-off.png'];
            } elseif (version_compare(App::config()->dotclearVersion(), $store[$id]->get('dc_min'), '>=')) {
                $img = [__('No update available'), 'check-wrn.png'];
            } else {
                $img = [__('Newer version available'), 'check-on.png'];
            }
            $img = sprintf('<img alt="%1$s" title="%1$s" src="images/%2$s" />', $img[0], $img[1]);

            $default_icon = false;

            echo
            '<tr class="line' . (!isset($store[$id]) || $module->get('version') == $store[$id]->get('version') ? ' offline' : '') . '" id="mvmodules_m_' . Html::escapeHTML((string) $id) . '">' .
            '<td class="module-icon nowrap">' .
            $img . '</td>' .
            '<th class="module-name nowrap" scope="row">' .
            Html::escapeHTML($module->get('name')) . ($id != $module->get('name') ? sprintf(__(' (%s)'), $id) : '') .
            '</td>';

            if (isset($store[$id])) {
                echo
                '<td class="module-version nowrap count">' .
                Html::escapeHTML($store[$id]->get('current_version')) . '</td>' .
                '<td class="module-version nowrap count maximal">' .
                Html::escapeHTML($store[$id]->get('version')) . '</td>' .
                '<td class="module-version nowrap count">' .
                Html::escapeHTML($store[$id]->get('dc_min')) . '</td>';

                if (App::config()->allowRepositories()) {
                    echo
                    '<td class="module-repository nowrap count">' .
                    (empty($module->get('repository')) ? __('Official repository') : __('Third-party repository')) . '</td>';
                }
            } else {
                echo
                '<td class="module-current-version nowrap count">' .
                Html::escapeHTML($module->get('version')) . '</td>' .
                '<td class="module-version nowrap count maximal" colspan="' . (App::config()->allowRepositories() ? '3' : '2') . '">' .
                Html::escapeHTML(__('No version available on stores')) . '</td>';
            }

            echo
            '</tr>';
        }

        echo
        '</table></div>';
    }
}
