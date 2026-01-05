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
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @since 2.27 Before as admin/blog_theme.php
 */
class BlogTheme
{
    use TraitProcess;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]));

        // Loading themes
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        // Backward compatibility
        App::backend()->list = App::backend()->themesList();
        // deprecated since 2.26
        App::backend()->themesList()::$distributed_modules = explode(',', App::config()->distributedThemes());

        $disabled = App::themes()->disableDepModules();

        if ($disabled !== []) {
            $list = (new Ul())
                ->items(array_map(fn (string $item) => (new Li())->text($item), $disabled))
            ->render();

            App::backend()->notices()->addWarningNotice(
                __('The following themes have been disabled :') . $list,
                ['divtag' => true, 'with_ts' => false]
            );

            App::backend()->url()->redirect('admin.blog.theme');
            dotclear_exit();
        }

        if (App::backend()->themesList()->setConfiguration(App::blog()->settings()->system->theme)) {
            // Display module configuration page

            // Get content before page headers
            $include = App::backend()->themesList()->includeConfiguration();
            if ($include) {
                include $include;
            }

            // Gather content
            App::backend()->themesList()->getConfiguration();

            // Display page
            App::backend()->page()->open(
                __('Blog appearance'),
                App::backend()->page()->jsPageTabs() .

                # --BEHAVIOR-- themesToolsHeaders -- bool
                App::behavior()->callBehavior('themesToolsHeadersV2', true),
                App::backend()->page()->breadcrumb(
                    [
                        // Active links
                        Html::escapeHTML(App::blog()->name()) => '',
                        __('Blog appearance')                 => App::backend()->themesList()->getURL('', false),
                        // inactive link
                        (new Span(__('Theme configuration')))->class('page-title')->render() => '',
                    ]
                )
            );

            // Display previously gathered content
            App::backend()->themesList()->displayConfiguration();

            if (!App::backend()->resources()->context()) {
                // Help sidebar has not been loaded by theme configuration
                App::backend()->page()->helpBlock('core_blog_theme_conf');
            }
            App::backend()->page()->close();

            // Stop reading code here
            return self::status(false);
        }

        // Execute actions
        try {
            App::backend()->themesList()->doActions();
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
                App::blog()->themesPath() . '/' . $_GET['shot'] . '/' . App::themes()::MODULE_FILE_SCREENSHOT :
                App::blog()->themesPath() . '/' . $_GET['shot'] . '/' . Path::clean($_GET['src'])
            );

            if (!file_exists($filename)) {
                $filename = __DIR__ . '/images/noscreenshot.svg';
            }

            Http::cache([$filename, ...get_included_files()]);

            header('Content-Type: ' . Files::getMimeType($filename));
            $size = filesize($filename);
            if ($size !== false) {
                header('Content-Length: ' . $size);
            }
            readfile($filename);

            // File sent, so bye bye
            dotclear_exit();
        }

        return true;
    }

    public static function render(): void
    {
        // Page header
        App::backend()->page()->open(
            __('Themes management'),
            (
                empty($_GET['nocache']) && empty($_GET['showupdate']) ?
                App::backend()->page()->jsJson('module_update_url', App::backend()->url()->get('admin.blog.theme', ['showupdate' => 1]) . '#update') : ''
            ) .
            App::backend()->page()->jsModal() .
            App::backend()->page()->jsLoad('js/_blog_theme.js') .
            App::backend()->page()->jsPageTabs() .

            # --BEHAVIOR-- themesToolsHeaders -- bool
            App::behavior()->callBehavior('themesToolsHeadersV2', false),
            App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name())                            => '',
                    (new Span(__('Blog appearance')))->class('page-title')->render() => '',
                ]
            )
        );

        // Display themes lists --
        $parts = [];

        // Activated themes
        $defines = App::backend()->themesList()->modules->getDefines(
            ['state' => App::backend()->themesList()->modules->safeMode() ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED]
        );
        if ($defines !== []) {
            $list = fn () => App::backend()->themesList()
                ->setList('theme-activate')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModulesFinal(
                    // cols
                    ['sshot', 'distrib', 'name', 'config', 'desc', 'tplset', 'author', 'version', 'date', 'parent'],
                    // actions
                    ['select', 'behavior', 'deactivate', 'clone', 'delete'],
                );

            $parts[] = (new Div('themes'))
                ->class('multi-part')
                ->title(__('Installed themes'))
                ->items([
                    (new Text('h3', (App::auth()->isSuperAdmin() ? __('Activated themes') : __('Installed themes')) . (App::backend()->themesList()->modules->safeMode() ? ' ' . __('(in normal mode)') : ''))),
                    (new Note())
                        ->class('more-info')
                        ->text(__('You can configure and manage installed themes from this list.')),
                    (new Capture($list)),
                ]);
        }

        // Deactivated modules
        $defines = App::backend()->themesList()->modules->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED]);
        if ($defines !== []) {
            $list = fn () => App::backend()->themesList()
                ->setList('theme-deactivate')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModulesFinal(
                    // cols
                    ['sshot', 'name', 'distrib', 'desc', 'tplset', 'author', 'version'],
                    // actions
                    ['activate', 'delete'],
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
                $messages .= App::backend()->notices()->message(
                    __('Official repository could not be updated as there is no URL set in configuration.'),
                    echo: false
                );
            }

            if (!App::error()->flag() && !empty($_GET['nocache'])) {
                $messages .= App::backend()->notices()->success(
                    __('Manual checking of themes update done successfully.'),
                    echo: false
                );
            }

            // Updated themes from repo
            $defines = App::backend()->themesList()->store->getDefines(true);
            $tmp     = new ArrayObject($defines);

            # --BEHAVIOR-- afterCheckStoreUpdate -- string, ArrayObject<int, ModuleDefine>
            App::behavior()->callBehavior('afterCheckStoreUpdate', 'themes', $tmp);

            $defines = $tmp->getArrayCopy();
            $updates = empty($defines) ? '' : sprintf(' (%s)', count($defines));

            $list = fn () => App::backend()->themesList()
                ->setList('theme-update')
                ->setTab('themes')
                ->setDefines($defines)
                ->displayModulesFinal(
                    // cols
                    ['checkbox', 'name', 'sshot', 'desc', 'tplset', 'author', 'version', 'current_version', 'repository', 'parent'],
                    // actions
                    ['update', 'delete']
                );

            $parts[] = (new Div('update'))
                ->title(Html::escapeHTML(__('Update themes') . $updates))
                ->class('multi-part')
                ->items([
                    $messages !== '' ? (new Text(null, $messages)) : (new None()),
                    (new Form('force-checking'))
                        ->action(App::backend()->themesList()->getURL('', true, 'update'))
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
                                        __('Visit %s themes repository.'),
                                        '<a href="https://dotclear.org/theme/list">Dotclear</a>'
                                    )),
                            ]),
                ]);
        }

        if (App::auth()->isSuperAdmin() && App::backend()->themesList()->isWritablePath()) {
            // New modules from repo
            $search  = App::backend()->themesList()->getSearch();
            $defines = $search ? App::backend()->themesList()->store->searchDefines($search) : App::backend()->themesList()->store->getDefines();

            $list = fn () => App::backend()->themesList()
                ->setList('theme-new')
                ->setTab('new')
                ->setDefines($defines)
                ->displaySearch()
                ->displayIndex()
                ->displayModulesFinal(
                    // cols
                    ['expander', 'sshot', 'name', 'score', 'config', 'desc', 'tplset', 'author', 'version', 'parent', 'details', 'support'],
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
                            __('Visit %s themes repository.'),
                            '<a href="https://dotclear.org/theme/list">Dotclear</a>'
                        )),
                ]);

            // Add a new theme
            $list = fn () => App::backend()->themesList()->displayManualFormFinal();

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
        if (App::auth()->isSuperAdmin() && !App::backend()->themesList()->isWritablePath()) {
            echo (new Note())
                ->class('warning')
                ->text(__('Some functions are disabled, please give write access to your themes directory to enable them.'))
            ->render();
        }

        App::backend()->page()->helpBlock('core_blog_theme');
        App::backend()->page()->close();
    }
}
