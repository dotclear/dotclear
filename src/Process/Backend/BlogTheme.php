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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemesList;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @since 2.27 Before as admin/blog_theme.php
 */
class BlogTheme extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]));

        // Loading themes
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        // Page helper
        App::backend()->list = new ThemesList(
            App::themes(),
            App::blog()->themesPath(),
            App::blog()->settings()->system->store_theme_url,
            !empty($_GET['nocache']) ? true : null
        );
        // deprecated since 2.26
        ThemesList::$distributed_modules = explode(',', App::config()->distributedThemes());

        $disabled = App::themes()->disableDepModules();

        if (count($disabled)) {
            $list = (new Ul())
                ->items(array_map(fn ($item) => (new Li())->text($item), $disabled))
            ->render();

            Notices::addWarningNotice(
                __('The following themes have been disabled :') . $list,
                ['divtag' => true, 'with_ts' => false]
            );

            App::backend()->url()->redirect('admin.blog.theme');
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
                        __('Blog appearance')                 => App::backend()->list->getURL('', false),
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
            $filename = (string) Path::real(
                empty($_GET['src']) ?
                App::blog()->themesPath() . '/' . $_GET['shot'] . '/screenshot.jpg' :
                App::blog()->themesPath() . '/' . $_GET['shot'] . '/' . Path::clean($_GET['src'])
            );

            if (!file_exists((string) $filename)) {
                $filename = __DIR__ . '/images/noscreenshot.svg';
            }

            Http::cache([$filename, ...get_included_files()]);

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
                Page::jsJson('module_update_url', App::backend()->url()->get('admin.blog.theme', ['showupdate' => 1]) . '#update') : ''
            ) .
            Page::jsModal() .
            Page::jsLoad('js/_blog_theme.js') .
            Page::jsPageTabs() .

            # --BEHAVIOR-- themesToolsHeaders -- bool
            App::behavior()->callBehavior('themesToolsHeadersV2', false),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name())                           => '',
                    '<span class="page-title">' . __('Blog appearance') . '</span>' => '',
                ]
            )
        );

        // Display themes lists --
        $parts = [];

        // Activated themes
        $defines = App::backend()->list->modules->getDefines(
            ['state' => App::backend()->list->modules->safeMode() ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED]
        );
        if (!empty($defines)) {
            $list = fn () => App::backend()->list
                ->setList('theme-activate')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModules(
                    // cols
                    ['sshot', 'distrib', 'name', 'config', 'desc', 'author', 'version', 'parent'],
                    // actions
                    ['select', 'behavior', 'deactivate', 'clone', 'delete', 'try']
                );

            $parts[] = (new Div('themes'))
                ->class('multi-part')
                ->title(__('Installed themes'))
                ->items([
                    (new Text('h3', (App::auth()->isSuperAdmin() ? __('Activated themes') : __('Installed themes')) . (App::backend()->list->modules->safeMode() ? ' ' . __('(in normal mode)') : ''))),
                    (new Note())
                        ->class('more-info')
                        ->text(__('You can configure and manage installed themes from this list.')),
                    (new Capture($list)),
                ]);
        }

        // Deactivated modules
        $defines = App::backend()->list->modules->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED]);
        if (!empty($defines)) {
            $list = fn () => App::backend()->list
                ->setList('theme-deactivate')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModules(
                    // cols
                    ['sshot', 'name', 'distrib', 'desc', 'author', 'version'],
                    // actions
                    ['activate', 'delete', 'try']
                );

            $parts[] = (new Div('deactivate'))
                ->class('multi-part')
                ->title(__('Deactivated themes'))
                ->items([
                    (new Text('h3', __('Deactivated themes'))),
                    (new Note())
                        ->class('more-info')
                        ->text(__('Deactivated themes are installed but not usable. You can activate them from here.')),
                    (new Capture($list)),
                ]);
        }

        // Updatable modules
        if (App::auth()->isSuperAdmin()) {
            $messages = '';
            if (!App::blog()->settings()->system->store_theme_url) {
                $messages .= Notices::message(
                    __('Official repository could not be updated as there is no URL set in configuration.'),
                    echo: false
                );
            }

            if (!App::error()->flag() && !empty($_GET['nocache'])) {
                $messages .= Notices::success(
                    __('Manual checking of themes update done successfully.'),
                    echo: false
                );
            }

            // Updated themes from repo
            $defines = App::backend()->list->store->getDefines(true);
            $tmp     = new ArrayObject($defines);

            # --BEHAVIOR-- afterCheckStoreUpdate -- string, ArrayObject<int, ModuleDefine>
            App::behavior()->callBehavior('afterCheckStoreUpdate', 'themes', $tmp);

            $defines = $tmp->getArrayCopy();
            $updates = !empty($defines) ? sprintf(' (%s)', count($defines)) : '';

            $list = fn () => App::backend()->list
                ->setList('theme-update')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModules(
                    // cols
                    ['checkbox', 'name', 'sshot', 'desc', 'author', 'version', 'current_version', 'repository', 'parent'],
                    // actions
                    ['update', 'delete']
                );

            $parts[] = (new Div('update'))
                ->title(Html::escapeHTML(__('Update themes') . $updates))
                ->class('multi-part')
                ->items([
                    $messages !== '' ? (new Text(null, $messages)) : (new None()),
                    (new Form('force-checking'))
                        ->action(App::backend()->list->getURL('', false))
                        ->method('get')
                        ->fields([
                            (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Hidden('nocache', '1')),
                                (new Hidden(['process'], 'BlogTheme')),
                                (new Submit('force-checking-update', __('Force checking update of themes'))),
                            ]),
                        ]),
                    empty($defines) ?
                        (new Note())
                            ->text(__('No updates available for themes.')) :
                        (new Set())
                            ->items([
                                (new Note())
                                    ->text(sprintf(
                                        __(
                                            'There is one theme update available:',
                                            'There are %s theme updates available:',
                                            count($defines)
                                        ),
                                        count($defines)
                                    )),
                                (new Capture($list)),
                                (new Note())
                                    ->class(['info', 'vertical-separator'])
                                    ->text(sprintf(
                                        __('Visit %s repository, the resources center for Dotclear.'),
                                        '<a href="https://themes.dotaddict.org/galerie-dc2/">Dotaddict</a>'
                                    )),
                            ]),
                ]);
        }

        if (App::auth()->isSuperAdmin() && App::backend()->list->isWritablePath()) {
            // New modules from repo
            $search  = App::backend()->list->getSearch();
            $defines = $search ? App::backend()->list->store->searchDefines($search) : App::backend()->list->store->getDefines();

            if (!empty($search) || !empty($defines)) {
                $list = fn () => App::backend()->list
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

                $parts[] = (new Div('new'))
                    ->title(__('Add themes'))
                    ->class('multi-part')
                    ->items([
                        (new Text('h3', __('Add themes from repository'))),
                        (new Capture($list)),
                        (new Note())
                            ->class(['info', 'vertical-separator'])
                            ->text(sprintf(
                                __('Visit %s repository, the resources center for Dotclear.'),
                                '<a href="https://themes.dotaddict.org/galerie-dc2/">Dotaddict</a>'
                            )),
                    ]);
            }

            // Add a new theme
            $list = fn () => App::backend()->list->displayManualForm();

            $parts[] = (new Div('addtheme'))
                ->title(__('Install or upgrade manually'))
                ->class('multi-part')
                ->items([
                    (new Text('h3', __('Add themes from a package'))),
                    (new Note())
                        ->class('more-info')
                        ->text(__('You can install themes by uploading or downloading zip files.')),
                    (new Capture($list)),
                ]);
        }

        echo (new Set())
            ->items($parts)
        ->render();

        # --BEHAVIOR-- themesToolsTabs --
        App::behavior()->callBehavior('themesToolsTabsV2');

        // Notice for super admin
        if (App::auth()->isSuperAdmin() && !App::backend()->list->isWritablePath()) {
            echo (new Note())
                ->class('warning')
                ->text(__('Some functions are disabled, please give write access to your themes directory to enable them.'))
            ->render();
        }

        Page::helpBlock('core_blog_theme');
        Page::close();
    }
}
