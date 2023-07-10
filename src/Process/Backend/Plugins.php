<?php
/**
 * @since 2.27 Before as admin/plugins.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use dcModuleDefine;
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Exception;

class Plugins extends Process
{
    public static function init(): bool
    {
        // -- Page helper --
        dcCore::app()->admin->list = new ModulesList(
            dcCore::app()->plugins,
            DC_PLUGINS_ROOT,
            dcCore::app()->blog->settings->system->store_plugin_url,
            !empty($_GET['nocache']) ? true : null
        );

        ModulesList::$allow_multi_install = (bool) DC_ALLOW_MULTI_MODULES;
        // deprecated since 2.26
        ModulesList::$distributed_modules = explode(',', DC_DISTRIB_PLUGINS);

        $disabled = dcCore::app()->plugins->disableDepModules();
        if (count($disabled)) {
            Notices::addWarningNotice(
                __('The following plugins have been disabled :') .
                '<ul><li>' . implode("</li>\n<li>", $disabled) . '</li></ul>',
                ['divtag' => true, 'with_ts' => false]
            );

            dcCore::app()->admin->url->redirect('admin.plugins');
            exit;
        }

        if (dcCore::app()->admin->list->setConfiguration()) {
            // -- Display module configuration page --
            self::renderConfig();
            // Stop reading code here, rendering will be done before returning (see below)
            return self::status(false);
        }

        Page::checkSuper();

        # -- Execute actions --
        try {
            dcCore::app()->admin->list->doActions();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        // -- Plugin install --
        dcCore::app()->admin->plugins_install = null;
        if (!dcCore::app()->error->flag()) {
            dcCore::app()->admin->plugins_install = dcCore::app()->plugins->installModules();
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
                Page::jsJson('module_update_url', dcCore::app()->admin->url->get('admin.plugins', ['showupdate' => 1]) . '#update') : ''
            ) .
            Page::jsLoad('js/_plugins.js') .
            Page::jsPageTabs() .

            # --BEHAVIOR-- pluginsToolsHeaders -- bool
            dcCore::app()->callBehavior('pluginsToolsHeadersV2', false),
            Page::breadcrumb(
                [
                    __('System')             => '',
                    __('Plugins management') => '',
                ]
            )
        );

        // -- Plugins install messages --
        if (!empty(dcCore::app()->admin->plugins_install['success'])) {
            $success = [];
            foreach (dcCore::app()->admin->plugins_install['success'] as $k => $v) {
                $info      = implode(' - ', dcCore::app()->admin->list->getSettingsUrls($k, true));
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
        if (!empty(dcCore::app()->admin->plugins_install['failure'])) {
            $failure = [];
            foreach (dcCore::app()->admin->plugins_install['failure'] as $k => $v) {
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
        if (dcCore::app()->auth->isSuperAdmin()) {
            if (null == dcCore::app()->blog->settings->system->store_plugin_url) {
                Notices::message(__('Official repository could not be updated as there is no URL set in configuration.'));
            }

            if (!dcCore::app()->error->flag() && !empty($_GET['nocache'])) {
                Notices::success(__('Manual checking of plugins update done successfully.'));
            }

            echo
            (new Form('force-checking'))
                ->action(dcCore::app()->admin->list->getURL('', false))
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

            // Updated modules from repo
            $defines = dcCore::app()->admin->list->store->getDefines(true);
            if (!empty($defines)) {
                echo
                '<div class="multi-part" id="update" title="' . Html::escapeHTML(__('Update plugins')) . '">' .
                '<h3>' . Html::escapeHTML(__('Update plugins')) . '</h3>' .
                '<p>' . sprintf(
                    __('There is one plugin to update available from repository.', 'There are %s plugins to update available from repository.', count($defines)),
                    count($defines)
                ) . '</p>';

                dcCore::app()->admin->list
                    ->setList('plugin-update')
                    ->setTab('update')
                    ->setDefines($defines)
                    ->displayModules(
                        /* cols */
                        ['checkbox', 'icon', 'name', 'version', 'repository', 'current_version', 'desc'],
                        /* actions */
                        ['update']
                    );

                echo
                '<p class="info vertical-separator">' . sprintf(
                    __('Visit %s repository, the resources center for Dotclear.'),
                    '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>'
                ) .
                '</p>' .

                '</div>';
            }
        }

        echo
        '<div class="multi-part" id="plugins" title="' . __('Installed plugins') . '">';

        # Activated modules
        $defines = dcCore::app()->admin->list->modules->getDefines(
            ['state' => dcCore::app()->admin->list->modules->safeMode() ? dcModuleDefine::STATE_SOFT_DISABLED : dcModuleDefine::STATE_ENABLED]
        );
        if (!empty($defines)) {
            echo
            '<h3>' .
            (dcCore::app()->auth->isSuperAdmin() ? __('Activated plugins') : __('Installed plugins')) .
            (dcCore::app()->admin->list->modules->safeMode() ? ' ' . __('(in normal mode)') : '') .
            '</h3>' .
            '<p class="more-info">' . __('You can configure and manage installed plugins from this list.') . '</p>';

            dcCore::app()->admin->list
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
        if (dcCore::app()->auth->isSuperAdmin()) {
            $defines = dcCore::app()->admin->list->modules->getDefines(['state' => dcModuleDefine::STATE_HARD_DISABLED]);
            if (!empty($defines)) {
                echo
                '<h3>' . __('Deactivated plugins') . '</h3>' .
                '<p class="more-info">' . __('Deactivated plugins are installed but not usable. You can activate them from here.') . '</p>';

                dcCore::app()->admin->list
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

        if (dcCore::app()->auth->isSuperAdmin() && dcCore::app()->admin->list->isWritablePath()) {
            # New modules from repo
            $search  = dcCore::app()->admin->list->getSearch();
            $defines = $search ? dcCore::app()->admin->list->store->searchDefines($search) : dcCore::app()->admin->list->store->getDefines();

            if (!empty($search) || !empty($defines)) {
                echo
                '<div class="multi-part" id="new" title="' . __('Add plugins') . '">' .
                '<h3>' . __('Add plugins from repository') . '</h3>';

                dcCore::app()->admin->list
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

            dcCore::app()->admin->list->displayManualForm();

            echo
            '</div>';
        }

        # --BEHAVIOR-- pluginsToolsTabs --
        dcCore::app()->callBehavior('pluginsToolsTabsV2');

        # -- Notice for super admin --
        if (dcCore::app()->auth->isSuperAdmin() && !dcCore::app()->admin->list->isWritablePath()) {
            echo
            '<p class="warning">' . __('Some functions are disabled, please give write access to your plugins directory to enable them.') . '</p>';
        }

        Page::helpBlock('core_plugins');
        Page::close();
    }

    /**
     * Renders plugin configuration page.
     */
    public static function renderConfig()
    {
        // Get content before page headers
        $include = dcCore::app()->admin->list->includeConfiguration();
        if ($include) {
            include $include;
        }

        // Gather content
        dcCore::app()->admin->list->getConfiguration();

        // Display page
        Page::open(
            __('Plugins management'),

            # --BEHAVIOR-- pluginsToolsHeaders -- bool
            dcCore::app()->callBehavior('pluginsToolsHeadersV2', true),
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name)                          => '',
                    __('Plugins management')                                             => dcCore::app()->admin->list->getURL('', false),
                    '<span class="page-title">' . __('Plugin configuration') . '</span>' => '',
                ]
            )
        );

        // Display previously gathered content
        dcCore::app()->admin->list->displayConfiguration();

        Page::helpBlock('core_plugins_conf');
        Page::close();
    }
}
