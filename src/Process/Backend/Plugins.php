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
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @since 2.27 Before as admin/plugins.php
 */
class Plugins extends Process
{
    public static function init(): bool
    {
        // -- Page helper --
        App::backend()->list = new ModulesList(
            App::plugins(),
            App::config()->pluginsRoot(),
            App::blog()->settings()->system->store_plugin_url,
            !empty($_GET['nocache']) ? true : null
        );

        ModulesList::$allow_multi_install = App::config()->allowMultiModules();
        // deprecated since 2.26
        ModulesList::$distributed_modules = explode(',', App::config()->distributedPlugins());

        $disabled = App::plugins()->disableDepModules();
        if (count($disabled)) {
            Notices::addWarningNotice(
                __('The following plugins have been disabled :') .
                '<ul><li>' . implode("</li>\n<li>", $disabled) . '</li></ul>',
                ['divtag' => true, 'with_ts' => false]
            );

            App::backend()->url()->redirect('admin.plugins');
            exit;
        }

        if (App::backend()->list->setConfiguration()) {
            // -- Display module configuration page --
            self::renderConfig();

            // Stop reading code here, rendering will be done before returning (see below)
            return self::status(false);
        }

        Page::checkSuper();

        # -- Execute actions --
        try {
            App::backend()->list->doActions();
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
        Page::open(
            __('Plugins management'),
            (
                empty($_GET['nocache']) && empty($_GET['showupdate']) ?
                Page::jsJson('module_update_url', App::backend()->url()->get('admin.plugins', ['showupdate' => 1]) . '#update') : ''
            ) .
            Page::jsLoad('js/_plugins.js') .
            Page::jsPageTabs() .

            # --BEHAVIOR-- pluginsToolsHeaders -- bool
            App::behavior()->callBehavior('pluginsToolsHeadersV2', false),
            Page::breadcrumb(
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
                $info      = implode(' - ', App::backend()->list->getSettingsUrls($k, true));
                $success[] = $k . ($info !== '' ? ' → ' . $info : '');
            }
            Notices::success(
                __('Following plugins have been installed:') .
                '<ul><li>' . implode("</li>\n<li>", $success) . '</li></ul>',
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

            Notices::error(
                __('Following plugins have not been installed:') .
                '<ul><li>' . implode("</li>\n<li>", $failure) . '</li></ul>',
                false,
                true
            );
            unset($failure);
        }

        // -- Display modules lists --
        echo
        '<div class="multi-part" id="plugins" title="' . __('Installed plugins') . '">';

        # Activated modules
        $defines = App::backend()->list->modules->getDefines(
            ['state' => App::backend()->list->modules->safeMode() ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED]
        );
        if (!empty($defines)) {
            echo
            '<h3>' .
            (App::auth()->isSuperAdmin() ? __('Activated plugins') : __('Installed plugins')) .
            (App::backend()->list->modules->safeMode() ? ' ' . __('(in normal mode)') : '') .
            '</h3>' .
            '<p class="more-info">' . __('You can configure and manage installed plugins from this list.') . '</p>';

            App::backend()->list
                ->setList('plugin-activate')
                ->setTab('plugins')
                ->setDefines($defines)
                ->displayModules(
                    /* cols */
                    ['expander', 'icon', 'name', 'version', 'desc', 'distrib', 'deps'],
                    /* actions */
                    ['deactivate', 'delete', 'behavior']
                );
        }

        # Deactivated modules
        if (App::auth()->isSuperAdmin()) {
            $defines = App::backend()->list->modules->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED]);
            if (!empty($defines)) {
                echo
                '<h3>' . __('Deactivated plugins') . '</h3>' .
                '<p class="more-info">' . __('Deactivated plugins are installed but not usable. You can activate them from here.') . '</p>';

                App::backend()->list
                    ->setList('plugin-deactivate')
                    ->setTab('plugins')
                    ->setDefines($defines)
                    ->displayModules(
                        /* cols */
                        ['expander', 'icon', 'name', 'version', 'desc', 'distrib'],
                        /* actions */
                        ['activate', 'delete']
                    );
            }
        }

        echo
        '</div>';

        // Updatable modules
        if (App::auth()->isSuperAdmin()) {
            if (null == App::blog()->settings()->system->store_plugin_url) {
                Notices::message(__('Official repository could not be updated as there is no URL set in configuration.'));
            }

            if (!App::error()->flag() && !empty($_GET['nocache'])) {
                Notices::success(__('Manual checking of plugins update done successfully.'));
            }

            // Updated modules from repo
            $defines = App::backend()->list->store->getDefines(true);

            $tmp = new ArrayObject($defines);

            # --BEHAVIOR-- afterCheckStoreUpdate -- string, ArrayObject<int, ModuleDefine>
            App::behavior()->callBehavior('afterCheckStoreUpdate', 'plugins', $tmp);

            $defines = $tmp->getArrayCopy();
            $updates = !empty($defines) && count($defines) > 0 ? sprintf(' (%s)', count($defines)) : '';

            echo
            '<div class="multi-part" id="update" title="' . Html::escapeHTML(__('Update plugins')) . $updates . '">' .
            '<h3>' . Html::escapeHTML(__('Update plugins')) . '</h3>';

            echo
            (new Form('force-checking'))
                ->action(App::backend()->list->getURL('', false) . '#update') // Switch to update tab if possible
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
                '<p>' . __('No updates available for plugins.') . '</p>';
            } else {
                echo
                '<p>' . sprintf(
                    __(
                        'There is one plugin update available:',
                        'There are %s plugin updates available:',
                        count($defines)
                    ),
                    count($defines)
                ) . '</p>';

                App::backend()->list
                    ->setList('plugin-update')
                    ->setTab('update')
                    ->setDefines($defines)
                    ->displayModules(
                        /* cols */
                        ['checkbox', 'icon', 'name', 'version', 'repository', 'current_version', 'desc'],
                        /* actions */
                        ['update', 'behavior']
                    );

                echo
                '<p class="info vertical-separator">' . sprintf(
                    __('Visit %s repository, the resources center for Dotclear.'),
                    '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>'
                ) .
                '</p>';
            }
            echo '</div>';
        }

        if (App::auth()->isSuperAdmin() && App::backend()->list->isWritablePath()) {
            # New modules from repo
            $search  = App::backend()->list->getSearch();
            $defines = $search ? App::backend()->list->store->searchDefines($search) : App::backend()->list->store->getDefines();

            if (!empty($search) || !empty($defines)) {
                echo
                '<div class="multi-part" id="new" title="' . __('Add plugins') . '">' .
                '<h3>' . __('Add plugins from repository') . '</h3>';

                App::backend()->list
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

            App::backend()->list->displayManualForm();

            echo
            '</div>';
        }

        # --BEHAVIOR-- pluginsToolsTabs --
        App::behavior()->callBehavior('pluginsToolsTabsV2');

        # -- Notice for super admin --
        if (App::auth()->isSuperAdmin() && !App::backend()->list->isWritablePath()) {
            echo
            '<p class="warning">' . __('Some functions are disabled, please give write access to your plugins directory to enable them.') . '</p>';
        }

        Page::helpBlock('core_plugins');
        Page::close();
    }

    /**
     * Renders plugin configuration page.
     */
    public static function renderConfig(): void
    {
        // Get content before page headers
        $include = App::backend()->list->includeConfiguration();
        if ($include) {
            include $include;
        }

        // Gather content
        App::backend()->list->getConfiguration();

        // Display page
        Page::open(
            __('Plugins management'),

            # --BEHAVIOR-- pluginsToolsHeaders -- bool
            App::behavior()->callBehavior('pluginsToolsHeadersV2', true),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name())                                => '',
                    __('Plugins management')                                             => App::backend()->list->getURL('', false),
                    '<span class="page-title">' . __('Plugin configuration') . '</span>' => '',
                ]
            )
        );

        // Display previously gathered content
        App::backend()->list->displayConfiguration();

        Page::helpBlock('core_plugins_conf');
        Page::close();
    }
}
