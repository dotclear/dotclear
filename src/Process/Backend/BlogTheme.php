<?php
/**
 * @since 2.27 Before as admin/blog_theme.php
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
use dcThemes;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemesList;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

class BlogTheme extends Process
{
    public static function init(): bool
    {
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_ADMIN,
        ]));

        // Loading themes
        dcCore::app()->themes = new dcThemes();
        dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, 'admin', dcCore::app()->lang);

        // Page helper
        dcCore::app()->admin->list = new ThemesList(
            dcCore::app()->themes,
            dcCore::app()->blog->themes_path,
            dcCore::app()->blog->settings->system->store_theme_url,
            !empty($_GET['nocache']) ? true : null
        );
        // deprecated since 2.26
        ThemesList::$distributed_modules = explode(',', DC_DISTRIB_THEMES);

        if (dcCore::app()->themes->disableDepModules(dcCore::app()->adminurl->get('admin.blog.theme', []))) {
            // A redirection occured, so we should never go further here
            exit;
        }

        if (dcCore::app()->admin->list->setConfiguration(dcCore::app()->blog->settings->system->theme)) {
            // Display module configuration page

            // Get content before page headers
            $include = dcCore::app()->admin->list->includeConfiguration();
            if ($include) {
                include $include;
            }

            // Gather content
            dcCore::app()->admin->list->getConfiguration();

            // Display page
            Page::open(
                __('Blog appearance'),
                Page::jsPageTabs() .

                # --BEHAVIOR-- themesToolsHeaders -- bool
                dcCore::app()->callBehavior('themesToolsHeadersV2', true),
                Page::breadcrumb(
                    [
                        // Active links
                        Html::escapeHTML(dcCore::app()->blog->name) => '',
                        __('Blog appearance')                       => dcCore::app()->admin->list->getURL('', false),
                        // inactive link
                        '<span class="page-title">' . __('Theme configuration') . '</span>' => '',
                    ]
                )
            );

            // Display previously gathered content
            dcCore::app()->admin->list->displayConfiguration();

            Page::helpBlock('core_blog_theme_conf');
            Page::close();

            // Stop reading code here
            return self::status(false);
        }

        // Execute actions
        try {
            dcCore::app()->admin->list->doActions();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!empty($_GET['shot'])) {
            // Get a theme screenshot
            $filename = Path::real(
                empty($_GET['src']) ?
                dcCore::app()->blog->themes_path . '/' . $_GET['shot'] . '/screenshot.jpg' :
                dcCore::app()->blog->themes_path . '/' . $_GET['shot'] . '/' . Path::clean($_GET['src'])
            );

            if (!file_exists($filename)) {
                $filename = __DIR__ . '/images/noscreenshot.png';
            }

            Http::cache([...[$filename], ...get_included_files()]);

            header('Content-Type: ' . Files::getMimeType($filename));
            header('Content-Length: ' . filesize($filename));
            readfile($filename);

            // File sent, so bye bye
            exit;
        }

        return true;
    }

    public static function render(): void
    {
        // Page header
        Page::open(
            __('Themes management'),
            (
                empty($_GET['nocache']) && empty($_GET['showupdate']) ?
                Page::jsJson('module_update_url', dcCore::app()->adminurl->get('admin.blog.theme', ['showupdate' => 1]) . '#update') : ''
            ) .
            Page::jsModal() .
            Page::jsLoad('js/_blog_theme.js') .
            Page::jsPageTabs() .

            # --BEHAVIOR-- themesToolsHeaders -- bool
            dcCore::app()->callBehavior('themesToolsHeadersV2', false),
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name)                     => '',
                    '<span class="page-title">' . __('Blog appearance') . '</span>' => '',
                ]
            )
        );

        // Display themes lists --
        if (dcCore::app()->auth->isSuperAdmin()) {
            if (!dcCore::app()->error->flag() && !empty($_GET['nocache'])) {
                Page::success(__('Manual checking of themes update done successfully.'));
            }

            echo
            (new Form('force-checking'))
                ->action(dcCore::app()->admin->list->getURL('', false))
                ->method('get')
                ->fields([
                    (new Para())
                    ->items([
                        (new Hidden('nocache', '1')),
                        (new Hidden(['process'], 'BlogTheme')),
                        (new Submit('force-checking-update', __('Force checking update of themes'))),
                    ]),
                ])
                ->render();

            // Updated themes from repo
            $defines = dcCore::app()->admin->list->store->getDefines(true);
            if (!empty($defines)) {
                echo
                '<div class="multi-part" id="update" title="' . Html::escapeHTML(__('Update themes')) . '">' .
                '<h3>' . Html::escapeHTML(__('Update themes')) . '</h3>' .
                '<p>' . sprintf(
                    __('There is one theme to update available from repository.', 'There are %s themes to update available from repository.', count($defines)),
                    count($defines)
                ) . '</p>';

                dcCore::app()->admin->list
                    ->setList('theme-update')
                    ->setTab('themes')
                    ->setDefines($defines)
                    ->displayModules(
                        // cols
                        ['checkbox', 'name', 'sshot', 'desc', 'author', 'version', 'current_version', 'repository', 'parent'],
                        // actions
                        ['update', 'delete']
                    );

                echo
                '<p class="info vertical-separator">' . sprintf(
                    __('Visit %s repository, the resources center for Dotclear.'),
                    '<a href="https://themes.dotaddict.org/galerie-dc2/">Dotaddict</a>'
                ) .
                '</p>' .
                '</div>';
            }
        }

        // Activated themes
        $defines = dcCore::app()->admin->list->modules->getDefines(
            ['state' => dcCore::app()->admin->list->modules->safeMode() ? dcModuleDefine::STATE_SOFT_DISABLED : dcModuleDefine::STATE_ENABLED]
        );
        if (!empty($defines)) {
            echo
            '<div class="multi-part" id="themes" title="' . __('Installed themes') . '">' .
            '<h3>' .
            (dcCore::app()->auth->isSuperAdmin() ? __('Activated themes') : __('Installed themes')) .
            (dcCore::app()->admin->list->modules->safeMode() ? ' ' . __('(in normal mode)') : '') .
            '</h3>' .
            '<p class="more-info">' . __('You can configure and manage installed themes from this list.') . '</p>';

            dcCore::app()->admin->list
                ->setList('theme-activate')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModules(
                    // cols
                    ['sshot', 'distrib', 'name', 'config', 'desc', 'author', 'version', 'parent'],
                    // actions
                    ['select', 'behavior', 'deactivate', 'clone', 'delete', 'try']
                );

            echo
            '</div>';
        }

        // Deactivated modules
        $defines = dcCore::app()->admin->list->modules->getDefines(['state' => dcModuleDefine::STATE_HARD_DISABLED]);
        if (!empty($defines)) {
            echo
            '<div class="multi-part" id="deactivate" title="' . __('Deactivated themes') . '">' .
            '<h3>' . __('Deactivated themes') . '</h3>' .
            '<p class="more-info">' . __('Deactivated themes are installed but not usable. You can activate them from here.') . '</p>';

            dcCore::app()->admin->list
                ->setList('theme-deactivate')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModules(
                    // cols
                    ['sshot', 'name', 'distrib', 'desc', 'author', 'version'],
                    // actions
                    ['activate', 'delete', 'try']
                );

            echo
            '</div>';
        }

        if (dcCore::app()->auth->isSuperAdmin() && dcCore::app()->admin->list->isWritablePath()) {
            // New modules from repo
            $search  = dcCore::app()->admin->list->getSearch();
            $defines = $search ? dcCore::app()->admin->list->store->searchDefines($search) : dcCore::app()->admin->list->store->getDefines();

            if (!empty($search) || !empty($defines)) {
                echo
                '<div class="multi-part" id="new" title="' . __('Add themes') . '">' .
                '<h3>' . __('Add themes from repository') . '</h3>';

                dcCore::app()->admin->list
                    ->setList('theme-new')
                    ->setTab('new')
                    ->setDefines($defines)
                    ->displaySearch()
                    ->displayIndex()
                    ->displayModules(
                        // cols
                        ['expander', 'sshot', 'name', 'score', 'config', 'desc', 'author', 'version', 'parent', 'details', 'support'],
                        // actions
                        ['install'],
                        // nav limit
                        true
                    );

                echo
                '<p class="info vertical-separator">' . sprintf(
                    __('Visit %s repository, the resources center for Dotclear.'),
                    '<a href="https://themes.dotaddict.org/galerie-dc2/">Dotaddict</a>'
                ) .
                '</p>' .
                '</div>';
            }

            // Add a new theme
            echo
            '<div class="multi-part" id="addtheme" title="' . __('Install or upgrade manually') . '">' .
            '<h3>' . __('Add themes from a package') . '</h3>' .
            '<p class="more-info">' . __('You can install themes by uploading or downloading zip files.') . '</p>';

            dcCore::app()->admin->list->displayManualForm();

            echo
            '</div>';
        }

        # --BEHAVIOR-- themesToolsTabs --
        dcCore::app()->callBehavior('themesToolsTabsV2');

        // Notice for super admin
        if (dcCore::app()->auth->isSuperAdmin() && !dcCore::app()->admin->list->isWritablePath()) {
            echo
            '<p class="warning">' . __('Some functions are disabled, please give write access to your themes directory to enable them.') . '</p>';
        }

        Page::helpBlock('core_blog_theme');
        Page::close();
    }
}
