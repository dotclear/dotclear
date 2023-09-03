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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemesList;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Module\ModuleDefine;
use Exception;

class BlogTheme extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]));

        // Loading themes
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang());
        }

        // Page helper
        App::backend()->list = new ThemesList(
            App::themes(),
            App::blog()->themesPath(),
            App::blog()->settings()->system->store_theme_url,
            !empty($_GET['nocache']) ? true : null
        );
        // deprecated since 2.26
        ThemesList::$distributed_modules = explode(',', DC_DISTRIB_THEMES);

        $disabled = App::themes()->disableDepModules();
        if (count($disabled)) {
            Notices::addWarningNotice(
                __('The following themes have been disabled :') .
                '<ul><li>' . implode("</li>\n<li>", $disabled) . '</li></ul>',
                ['divtag' => true, 'with_ts' => false]
            );

            App::backend()->url->redirect('admin.blog.theme');
            exit;
        }

        if (App::backend()->list->setConfiguration(App::blog()->settings()->system->theme)) {
            // Display module configuration page

            // Get content before page headers
            $include = App::backend()->list->includeConfiguration();
            if ($include) {
                include $include;
            }

            // Gather content
            App::backend()->list->getConfiguration();

            // Display page
            Page::open(
                __('Blog appearance'),
                Page::jsPageTabs() .

                # --BEHAVIOR-- themesToolsHeaders -- bool
                App::behavior()->callBehavior('themesToolsHeadersV2', true),
                Page::breadcrumb(
                    [
                        // Active links
                        Html::escapeHTML(App::blog()->name()) => '',
                        __('Blog appearance')               => App::backend()->list->getURL('', false),
                        // inactive link
                        '<span class="page-title">' . __('Theme configuration') . '</span>' => '',
                    ]
                )
            );

            // Display previously gathered content
            App::backend()->list->displayConfiguration();

            Page::helpBlock('core_blog_theme_conf');
            Page::close();

            // Stop reading code here
            return self::status(false);
        }

        // Execute actions
        try {
            App::backend()->list->doActions();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!empty($_GET['shot'])) {
            // Get a theme screenshot
            $filename = Path::real(
                empty($_GET['src']) ?
                App::blog()->themesPath() . '/' . $_GET['shot'] . '/screenshot.jpg' :
                App::blog()->themesPath() . '/' . $_GET['shot'] . '/' . Path::clean($_GET['src'])
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
                Page::jsJson('module_update_url', App::backend()->url->get('admin.blog.theme', ['showupdate' => 1]) . '#update') : ''
            ) .
            Page::jsModal() .
            Page::jsLoad('js/_blog_theme.js') .
            Page::jsPageTabs() .

            # --BEHAVIOR-- themesToolsHeaders -- bool
            App::behavior()->callBehavior('themesToolsHeadersV2', false),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name())                             => '',
                    '<span class="page-title">' . __('Blog appearance') . '</span>' => '',
                ]
            )
        );

        // Display themes lists --
        if (App::auth()->isSuperAdmin()) {
            if (null == App::blog()->settings()->system->store_theme_url) {
                Notices::message(__('Official repository could not be updated as there is no URL set in configuration.'));
            }

            if (!App::error()->flag() && !empty($_GET['nocache'])) {
                Notices::success(__('Manual checking of themes update done successfully.'));
            }

            echo
            (new Form('force-checking'))
                ->action(App::backend()->list->getURL('', false))
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
            $defines = App::backend()->list->store->getDefines(true);
            if (!empty($defines)) {
                echo
                '<div class="multi-part" id="update" title="' . Html::escapeHTML(__('Update themes')) . '">' .
                '<h3>' . Html::escapeHTML(__('Update themes')) . '</h3>' .
                '<p>' . sprintf(
                    __('There is one theme to update available from repository.', 'There are %s themes to update available from repository.', count($defines)),
                    count($defines)
                ) . '</p>';

                App::backend()->list
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
        $defines = App::backend()->list->modules->getDefines(
            ['state' => App::backend()->list->modules->safeMode() ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED]
        );
        if (!empty($defines)) {
            echo
            '<div class="multi-part" id="themes" title="' . __('Installed themes') . '">' .
            '<h3>' .
            (App::auth()->isSuperAdmin() ? __('Activated themes') : __('Installed themes')) .
            (App::backend()->list->modules->safeMode() ? ' ' . __('(in normal mode)') : '') .
            '</h3>' .
            '<p class="more-info">' . __('You can configure and manage installed themes from this list.') . '</p>';

            App::backend()->list
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
        $defines = App::backend()->list->modules->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED]);
        if (!empty($defines)) {
            echo
            '<div class="multi-part" id="deactivate" title="' . __('Deactivated themes') . '">' .
            '<h3>' . __('Deactivated themes') . '</h3>' .
            '<p class="more-info">' . __('Deactivated themes are installed but not usable. You can activate them from here.') . '</p>';

            App::backend()->list
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

        if (App::auth()->isSuperAdmin() && App::backend()->list->isWritablePath()) {
            // New modules from repo
            $search  = App::backend()->list->getSearch();
            $defines = $search ? App::backend()->list->store->searchDefines($search) : App::backend()->list->store->getDefines();

            if (!empty($search) || !empty($defines)) {
                echo
                '<div class="multi-part" id="new" title="' . __('Add themes') . '">' .
                '<h3>' . __('Add themes from repository') . '</h3>';

                App::backend()->list
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

            App::backend()->list->displayManualForm();

            echo
            '</div>';
        }

        # --BEHAVIOR-- themesToolsTabs --
        App::behavior()->callBehavior('themesToolsTabsV2');

        // Notice for super admin
        if (App::auth()->isSuperAdmin() && !App::backend()->list->isWritablePath()) {
            echo
            '<p class="warning">' . __('Some functions are disabled, please give write access to your themes directory to enable them.') . '</p>';
        }

        Page::helpBlock('core_blog_theme');
        Page::close();
    }
}
