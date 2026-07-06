<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Autoloader;
use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Helper\Network\Http;
use Dotclear\Interface\Module\ModulesInterface;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * Helper for admin list of themes.
 *
 * Provides an object to parse XML feed of modules from a repository.
 *
 * @since 2.6
 */
class ThemesList extends ModulesList
{
    /**
     * Constructor.
     *
     * Note that this creates Store instance.
     *
     * @param    ModulesInterface   $modules            ModulesInterface instance
     * @param    string             $modules_root       Modules root directories
     * @param    string             $xml_url            URL of modules feed from repository
     * @param    null|bool          $force              Force query repository
     * @param    bool               $use_cache_only     Check for updates using cache only (no HTTP requests)
     */
    public function __construct(
        ModulesInterface $modules,
        string $modules_root,
        string $xml_url,
        ?bool $force = false,
        bool $use_cache_only = true,
    ) {
        if ($force === true) {
            $use_cache_only = false;
        }

        parent::__construct($modules, $modules_root, $xml_url, $force, $use_cache_only);
        $this->page_url = App::backend()->url()->get('admin.blog.theme');
    }

    /**
     * Display themes list
     *
     * @param      array<string>    $cols       The cols
     * @param      array<string>    $actions    The actions
     * @param      bool             $nav_limit  The navigation limit
     */
    public function displayModules(array $cols = ['name', 'config', 'version', 'desc'], array $actions = [], bool $nav_limit = false): ThemesList
    {
        // Sort modules by id
        $sort_field = $this->getSort();
        if ($this->getSearch() === null) {
            uasort($this->defines, fn ($a, $b): int => $a->get($sort_field) <=> $b->get($sort_field));
        }

        $themes    = [];
        $first     = (new None());
        $officials = explode(',', App::config()->distributedThemes());

        $count = 0;
        foreach ($this->defines as $define) {
            $id = $define->getId();

            # Show only requested modules
            if ($nav_limit && $this->getSearch() === null) {
                $check = is_string($check = $define->get($sort_field)) ? $check : '';
                if ($check === '') {
                    continue;
                }

                $char = substr($check, 0, 1);
                if (!in_array($char, $this->nav_list)) {
                    $char = $this->nav_special;
                }

                if ($this->getIndex() !== $char) {
                    continue;
                }
            }

            $current = App::blog()->settings()->get('system')->getStr('theme', false) === $id && $this->modules->moduleExists($id);

            $distributed = $define->get('distributed') ? 'dc-box' : '';
            $root        = is_string($root = $define->get('root')) ? $root : '';
            $name        = is_string($name = $define->get('name')) ? $name : '';

            // Check if it is a symlink to a distributed one
            if (!$define->get('distributed') && $root !== '' && in_array($id, $officials, true)) {
                $root = Path::real($root);
                if ($root !== false && is_link($root)) {
                    $target = readlink($root);
                    if ($target === Path::real(App::config()->dotclearRoot()) . '/themes/' . $id) {
                        $distributed = 'dc-box';
                    }
                }
            }

            $git = (App::config()->devMode() || App::config()->debugMode()) && file_exists($root . DIRECTORY_SEPARATOR . '.git');

            $parts = [];

            if (in_array('name', $cols) && !$current) {
                $parts[] = (new Div(null, 'h4'))
                    ->class('module-name')
                    ->items([
                        in_array('checkbox', $cols) ?
                        (new Checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)]))
                            ->value(Html::escapeHTML($id))
                            ->label(new Label(Html::escapeHTML($name), Label::IL_FT)) :
                            (new Set())
                                ->items([
                                    (new Hidden(['modules[' . $count . ']'], Html::escapeHTML($id))),
                                    (new Text(null, Html::escapeHTML($name))),
                                ]),
                        App::nonce()->formNonce(),
                    ]);
            }

            // Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && App::config()->debugMode()) {
                $score   = is_numeric($score = $define->get('score')) ? (int) $score : 0;
                $parts[] = (new Note())
                    ->class(['module-score', 'debug'])
                    ->text(sprintf(__('Score: %s'), (string) $score));
            }

            if (in_array('sshot', $cols)) {
                // Screenshot from url
                $sshot = is_string($sshot = $define->get('sshot')) ? $sshot : '';
                if (preg_match('#^http(s)?://#', $sshot)) {
                    ;
                }
                // Screenshot from installed module
                elseif (file_exists(App::blog()->themesPath() . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_SCREENSHOT)) {
                    $sshot = $this->getURL('shot=' . rawurlencode($id));
                } elseif (file_exists(App::blog()->themesPath() . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_SCREENSHOT_ALT)) {
                    $sshot = $this->getURL('shot=' . rawurlencode($id));
                }
                // Default screenshot
                else {
                    $sshot = 'images/noscreenshot.svg';
                }

                $parts[] = (new Div())
                    ->class('module-sshot')
                    ->items([
                        (new Img($sshot))
                            ->loading('lazy')
                            ->alt(sprintf(__('%s screenshot.'), Html::escapeHTML($name))),
                    ]);
            }

            // Themes infos
            $module_infos = [];

            if (in_array('name', $cols) && $current) {
                $name = is_string($name = $define->get('name')) ? $name : '';

                $module_infos[] = (new Div(null, 'h4'))
                    ->class('module-name')
                    ->items([
                        in_array('checkbox', $cols) ?
                        (new Checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)]))
                            ->value(Html::escapeHTML($id))
                            ->label(new Label(Html::escapeHTML($name), Label::IL_FT)) :
                            (new Set())
                                ->items([
                                    (new Hidden(['modules[' . $count . ']'], Html::escapeHTML($id))),
                                    (new Text(null, Html::escapeHTML($name))),
                                ]),
                    ]);
            }

            $infos = [];
            if (in_array('desc', $cols)) {
                $desc = is_string($desc = $define->get('desc')) ? $desc : '';

                $infos[] = (new Span(Html::escapeHTML(__($desc))))
                    ->class('module-desc');
            }

            if (in_array('tplset', $cols)) {
                $tplset = is_string($tplset = $define->get('tplset')) ? Html::escapeHTML($tplset) : '';
                if ($tplset !== '') {
                    $infos[] = (new Span(sprintf(__('(%s template set)'), $tplset)))
                        ->class(['module-tplset', 'tplset-' . $tplset]);
                }
            }

            if (in_array('author', $cols)) {
                $author = is_string($author = $define->get('author')) ? $author : '';

                $infos[] = (new Span(sprintf(__('by %s'), Html::escapeHTML($author))))
                    ->class('module-author');
            }

            if (in_array('version', $cols)) {
                $version = is_string($version = $define->get('version')) ? $version : '';

                $infos[] = (new Span(sprintf(__('version %s'), Html::escapeHTML($version))))
                    ->class('module-version');
            }

            if (in_array('date', $cols) && !empty($define->get('date'))) {
                $date_format = App::blog()->settings()->get('system')->getStr('date_format', false) ?: __('%Y-%m-%d %H:%M');
                $user_tz     = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : null;
                $date        = is_string($date = $define->get('date')) ? $date : '';

                $infos[] = (new Span(sprintf(__('released on %s'), Html::escapeHTML(Date::dt2str($date_format, $date, $user_tz)))))
                    ->class('module-date');
            }

            if (in_array('current_version', $cols)) {
                $current_version = is_string($current_version = $define->get('current_version')) ? $current_version : '';

                $infos[] = (new Span(sprintf(__('(current version %s)'), Html::escapeHTML($current_version))))
                    ->class('module-current-version');
            }

            $parent = is_string($parent = $define->get('parent')) ? $parent : '';
            if (in_array('parent', $cols) && $parent !== '') {
                if ($this->modules->moduleExists($parent)) {
                    $infos[] = (new Span(sprintf(__('(built on "%s")'), Html::escapeHTML($parent))))
                        ->class('module-parent-ok');
                } else {
                    $infos[] = (new Span(sprintf(__('(requires "%s")'), Html::escapeHTML($parent))))
                        ->class('module-parent-missing');
                }
            }

            if (in_array('repository', $cols) && App::config()->allowRepositories()) {
                $infos[] = (new Span(empty($define->get('repository')) ? __('Official repository') : __('Third-party repository')))
                    ->class('module-repository');
            }

            if ($define->updLocked()) {
                $infos[] = (new Span(__('update locked')))
                    ->class('module-locked');
            }

            if (in_array('details', $cols) || in_array('support', $cols)) {
                $links = [];

                if (in_array('details', $cols)) {
                    $details = is_string($details = $define->get('details')) ? $details : '';
                    if ($details !== '') {
                        $links[] = (new Link())
                            ->class('module-details')
                            ->href($details)
                            ->text(__('Details'));
                    }
                }

                if (in_array('support', $cols)) {
                    $support = is_string($support = $define->get('support')) ? $support : '';
                    if ($support !== '') {
                        $links[] = (new Link())
                            ->class('module-support')
                            ->href($support)
                            ->text(__('Support'));
                    }
                }

                if ($links !== []) {
                    $infos[] = (new Span())
                        ->class('mod-more')
                        ->separator(' - ')
                        ->items($links);
                }
            }

            $module_infos[] = (new Para())
                ->separator(' ')
                ->items($infos);

            // Themes actions
            $module_actions = [];
            if ($current) {
                // _GET actions

                // by class name
                $namespace = is_string($namespace = $define->get('namespace')) ? $namespace : '';
                $class     = $namespace . Autoloader::NS_SEP . $this->modules::MODULE_CLASS_CONFIG;
                if ($namespace !== '' && class_exists($class)) {
                    $config = $class::init();
                    // by file name
                } else {
                    $config = file_exists(Path::real(App::blog()->themesPath() . DIRECTORY_SEPARATOR . $id) . DIRECTORY_SEPARATOR . $this->modules::MODULE_FILE_CONFIG);
                }

                # --BEHAVIOR-- adminCurrentThemeDetails -- string, ModuleDefine
                $behavior = App::behavior()->callBehavior('adminCurrentThemeDetailsV2', $define->getId(), $define);

                $module_actions[] = (new Div())
                    ->class('current-actions')
                    ->items([
                        $config ?
                            (new Para())
                                ->items([
                                    (new Link())
                                        ->href($this->getURL('module=' . $id . '&conf=1', false))
                                        ->class(['button', 'submit'])
                                        ->text(__('Configure theme')),
                                ]) :
                            (new None()),
                        (new Text(null, $behavior)),
                    ]);
            }

            // _POST actions
            if ($actions !== []) {
                $module_actions[] = (new Para())
                    ->class('module-post-actions')
                    ->items([
                        (new Text(null, implode(' ', $this->getActions($define, $actions)))),
                    ]);
            }

            $parts[] = $current ?
                (new Set())
                    ->items([
                        (new Div())
                            ->class('module-infos')
                            ->items($module_infos),
                        (new Div())
                            ->class('module-actions')
                            ->items($module_actions),
                    ]) :
                (new Details())
                    ->summary(new Summary(__('Details')))
                    ->items([
                        (new Div())
                            ->class('module-infos')
                            ->items($module_infos),
                        (new Div())
                            ->class('module-actions')
                            ->items($module_actions),
                    ]);

            $theme = (new Div())
                ->class(array_filter(['box', $distributed, $current ? 'medium' : '', $current ? 'current-theme' : 'theme', $git ? 'module-git' : '']))
                ->items($parts);

            // Put currently selected theme in first position
            if ($current) {
                $first = $theme;
            } else {
                $themes[] = $theme;
            }

            $count++;
        }

        $bottom = (new None());
        if (!$count && $this->getSearch() === null) {
            $bottom = (new Note())
                ->class('message')
                ->text(__('No themes matched your search.'));
        } elseif ((in_array('checkbox', $cols) || $count > 1) && $actions !== [] && App::auth()->isSuperAdmin()) {
            $buttons = $this->getGlobalActions($actions, in_array('checkbox', $cols));
            if ($buttons !== []) {
                $bottom = (new Set())
                    ->items([
                        in_array('checkbox', $cols) ? ((new Para())->class('checkboxes-helpers')) : (new None()),
                        (new Div())
                            ->items([
                                (new Text(null, implode(' ', $buttons))),
                            ]),
                    ]);
            }
        }

        echo (new Form(['themes-list-form']))
            ->method('post')
            ->action($this->getURL())
            ->class('modules-form-actions')
            ->fields([
                (new Div())
                    ->id(Html::escapeHTML($this->list_id))
                    ->class(array_filter(['modules', 'one-box', in_array('expander', $cols) ? ' expandable' : '']))
                    ->items([
                        $first,
                        ...$themes,
                    ]),
                $bottom,
            ])
        ->render();

        return $this;
    }

    /**
     * Gets the actions.
     *
     * @param      ModuleDefine     $define   The module define
     * @param      array<string>    $actions  The actions
     *
     * @return     array<string>  The actions.
     */
    protected function getActions(ModuleDefine $define, array $actions): array
    {
        $submits = [];
        $id      = $define->getId();

        if ($id !== App::blog()->settings()->get('system')->getStr('theme', false)) {
            // Select theme to use on curent blog
            if (in_array('select', $actions)) {
                $submits[] = (new Submit(['select[' . Html::escapeHTML($id) . ']'], __('Use this one')))->render();
            }
        } else {
            // Currently selected theme
            if ($pos = array_search('delete', $actions, true)) {
                // Remove 'delete' action
                unset($actions[$pos]);
            }
            if ($pos = array_search('deactivate', $actions, true)) {
                // Remove 'deactivate' action
                unset($actions[$pos]);
            }
        }

        $distributed = in_array($id, explode(',', App::themes()->getProtectedModules()));
        if ($distributed && ($pos = array_search('delete', $actions, true))) {
            // Remove 'delete' action for officially distributed themes
            unset($actions[$pos]);
        }

        return array_merge(
            $submits,
            parent::getActions($define, $actions)
        );
    }

    /**
     * Gets the global actions.
     *
     * @param      array<string>    $actions         The actions
     * @param      bool             $with_selection  The with selection
     *
     * @return     array<string>   The global actions.
     */
    protected function getGlobalActions(array $actions, bool $with_selection = false): array
    {
        $submits = [];

        foreach ($actions as $action) {
            switch ($action) {
                # Update (from store)
                case 'update':

                    if (App::auth()->isSuperAdmin() && $this->path_writable) {
                        $submits[] = (new Submit(['update'], $with_selection ?
                                __('Update selected themes') :
                                __('Update all themes from this list')))
                            ->render();
                        $submits[] = App::nonce()->formNonce()->render();
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetGlobalActions -- ModulesList
                    $tmp = App::behavior()->callBehavior('adminModulesListGetGlobalActions', $this);

                    if ($tmp !== '') {
                        $submits[] = $tmp;
                    }

                    break;
            }
        }

        return $submits;
    }

    /**
     * Does actions.
     *
     * @throws     Exception
     */
    public function doActions(): void
    {
        if ($_POST === [] || !empty($_REQUEST['conf'])) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : [];

        if (!empty($_POST['select'])) {
            # Can select only one theme at a time!
            if (is_array($_POST['select'])) {
                $modules = array_keys($_POST['select']);
                $define  = $this->modules->getDefine((string) $modules[0]);

                if (!$define->isDefined()) {
                    throw new Exception(__('No such theme.'));
                }

                $theme = App::blog()->settings()->get('system')->getStr('theme', false);

                $name = is_string($name = $define->get('name')) ? $name : '';

                # --BEHAVIOR-- themeBeforeSelect -- string, string
                App::behavior()->callBehavior('themeBeforeSelect', $define->getId(), $theme);

                App::blog()->settings()->get('system')->put('theme', $define->getId(), App::blogWorkspace()::NS_STRING);
                App::blog()->triggerBlog();

                # --BEHAVIOR-- themeAfterSelect -- string, string
                App::behavior()->callBehavior('themeAfterSelect', $define->getId(), $theme);

                // Empty theme (and theme's parent if any) template cache
                $this->emptyThemeTemplatesCache();

                App::backend()->notices()->addSuccessNotice(sprintf(__('Theme %s has been successfully selected.'), Html::escapeHTML($name)));
                Http::redirect($this->getURL('', true, 'themes'));
            }
        } else {
            if (!$this->isWritablePath()) {
                return;
            }

            if (App::auth()->isSuperAdmin() && !empty($_POST['activate'])) {
                if (is_array($_POST['activate'])) {
                    $modules = array_keys($_POST['activate']);
                }

                $count = 0;
                foreach ($modules as $id) {
                    if (is_string($id)) {
                        $define = $this->modules->getDefine($id);
                        if (!$define->isDefined()) {
                            continue;
                        }
                        if ($define->get('state') == ModuleDefine::STATE_ENABLED) {
                            continue;
                        }

                        # --BEHAVIOR-- themeBeforeActivate -- string
                        App::behavior()->callBehavior('themeBeforeActivate', $define->getId());

                        $this->modules->activateModule($define->getId());

                        # --BEHAVIOR-- themeAfterActivate -- string
                        App::behavior()->callBehavior('themeAfterActivate', $define->getId());

                        $count++;
                    }
                }

                if ($count === 0) {
                    throw new Exception(__('No such theme.'));
                }

                App::backend()->notices()->addSuccessNotice(
                    __('Theme has been successfully activated.', 'Themes have been successuflly activated.', $count)
                );
                Http::redirect($this->getURL());
            } elseif (App::auth()->isSuperAdmin() && !empty($_POST['deactivate'])) {
                if (is_array($_POST['deactivate'])) {
                    $modules = array_keys($_POST['deactivate']);
                }

                $failed = false;
                $count  = 0;
                foreach ($modules as $id) {
                    if (is_string($id)) {
                        $define = $this->modules->getDefine($id);
                        if (!$define->isDefined()) {
                            continue;
                        }
                        if ($define->get('state') == ModuleDefine::STATE_HARD_DISABLED) {
                            continue;
                        }

                        if (!$define->get('root_writable')) {
                            $failed = true;

                            continue;
                        }

                        # --BEHAVIOR-- themeBeforeDeactivate -- ModuleDefine
                        App::behavior()->callBehavior('themeBeforeDeactivateV2', $define);

                        $this->modules->deactivateModule($define->getId());

                        # --BEHAVIOR-- themeAfterDeactivate -- ModuleDefine
                        App::behavior()->callBehavior('themeAfterDeactivateV2', $define);

                        $count++;
                    }
                }

                if ($count === 0) {
                    throw new Exception(__('No such theme.'));
                }

                if ($failed) {
                    App::backend()->notices()->addWarningNotice(__('Some themes have not been deactivated.'));
                } else {
                    App::backend()->notices()->addSuccessNotice(
                        __('Theme has been successfully deactivated.', 'Themes have been successuflly deactivated.', $count)
                    );
                }
                Http::redirect($this->getURL());
            } elseif (App::auth()->isSuperAdmin() && !empty($_POST['clone'])) {
                if (is_array($_POST['clone'])) {
                    $modules = array_keys($_POST['clone']);
                }

                $count = 0;
                foreach ($modules as $id) {
                    if (is_string($id)) {
                        $define = $this->modules->getDefine($id);
                        if (!$define->isDefined()) {
                            continue;
                        }
                        if ($define->get('state') != ModuleDefine::STATE_ENABLED) {
                            continue;
                        }

                        # --BEHAVIOR-- themeBeforeClone -- string
                        App::behavior()->callBehavior('themeBeforeClone', $define->getId());

                        $this->modules->cloneModule($define->getId());

                        # --BEHAVIOR-- themeAfterClone -- string
                        App::behavior()->callBehavior('themeAfterClone', $define->getId());

                        $count++;
                    }
                }

                if ($count === 0) {
                    throw new Exception(__('No such theme.'));
                }

                App::backend()->notices()->addSuccessNotice(
                    __('Theme has been successfully cloned.', 'Themes have been successuflly cloned.', $count)
                );
                Http::redirect($this->getURL());
            } elseif (App::auth()->isSuperAdmin() && !empty($_POST['delete'])) {
                if (is_array($_POST['delete'])) {
                    $modules = array_keys($_POST['delete']);
                }

                $failed = false;
                $count  = 0;
                foreach ($modules as $id) {
                    if (is_string($id)) {
                        $disabled = isset($_POST['disabled']) && is_array($_POST['disabled']) && !empty($_POST['disabled'][$id]);
                        $define   = $this->modules->getDefine($id, ['state' => ($disabled ? '!' : '') . ModuleDefine::STATE_ENABLED]);
                        if (!$define->isDefined()) {
                            continue;
                        }

                        $root = is_string($root = $define->get('root')) ? $root : '';
                        if ($root === '') {
                            continue;
                        }

                        $symlink = is_link($root);
                        if (!$symlink && !$this->isDeletablePath($root)) {
                            $failed = true;

                            continue;
                        }

                        # --BEHAVIOR-- themeBeforeDelete -- ModuleDefine
                        App::behavior()->callBehavior('themeBeforeDeleteV2', $define);

                        $this->modules->deleteModule($define->getId(), $disabled);

                        # --BEHAVIOR-- themeAfterDelete -- ModuleDefine
                        App::behavior()->callBehavior('themeAfterDeleteV2', $define);

                        $count++;
                    }
                }

                if (!$count && $failed) {
                    throw new Exception(__("You don't have permissions to delete this theme."));
                }

                if ($count === 0) {
                    throw new Exception(__('No such theme.'));
                }

                if ($failed) {
                    App::backend()->notices()->addWarningNotice(__('Some themes have not been delete.'));
                } else {
                    App::backend()->notices()->addSuccessNotice(
                        __('Theme has been successfully deleted.', 'Themes have been successuflly deleted.', $count)
                    );
                }
                Http::redirect($this->getURL());
            } elseif (App::auth()->isSuperAdmin() && !empty($_POST['install'])) {
                if (is_array($_POST['install'])) {
                    $modules = array_keys($_POST['install']);
                }

                $count = 0;
                foreach ($this->store->getDefines() as $define) {
                    if (!in_array($define->getId(), $modules)) {
                        continue;
                    }

                    $file = is_string($file = $define->get('file')) ? $file : '';
                    if ($file === '') {
                        continue;
                    }

                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($file);

                    # --BEHAVIOR-- themeBeforeAdd -- ModuleDefine
                    App::behavior()->callBehavior('themeBeforeAddV2', $define);

                    $this->store->process($file, $dest);

                    # --BEHAVIOR-- themeAfterAdd -- ModuleDefine
                    App::behavior()->callBehavior('themeAfterAddV2', $define);

                    $count++;
                }

                if ($count === 0) {
                    throw new Exception(__('No such theme.'));
                }

                App::backend()->notices()->addSuccessNotice(
                    __('Theme has been successfully installed.', 'Themes have been successfully installed.', $count)
                );
                Http::redirect($this->getURL());
            } elseif (App::auth()->isSuperAdmin() && !empty($_POST['update'])) {
                if (is_array($_POST['update'])) {
                    $modules = array_keys($_POST['update']);
                }

                $locked  = [];
                $count   = 0;
                $defines = $this->store->getDefines(true);
                foreach ($defines as $define) {
                    if (!in_array($define->getId(), $modules)) {
                        continue;
                    }

                    if ($define->updLocked()) {
                        $locked[] = $define->get('name');

                        continue;
                    }

                    $root = is_string($root = $define->get('root')) ? $root : '';
                    if ($root === '') {
                        continue;
                    }

                    $file = is_string($file = $define->get('file')) ? $file : '';
                    if ($file === '') {
                        continue;
                    }

                    $dest = implode(DIRECTORY_SEPARATOR, [Path::dirWithSym($root), '..', basename($file)]);

                    # --BEHAVIOR-- themeBeforeUpdate -- ModuleDefine
                    App::behavior()->callBehavior('themeBeforeUpdateV2', $define);

                    $this->store->process($file, $dest);

                    # --BEHAVIOR-- themeAfterUpdate -- ModuleDefine
                    App::behavior()->callBehavior('themeAfterUpdateV2', $define);

                    $count++;
                }

                $tab = $count === count($defines) ? 'themes' : 'update';

                if ($count !== 0) {
                    App::backend()->notices()->addSuccessNotice(
                        __('Theme has been successfully updated.', 'Themes have been successfully updated.', $count)
                    );
                } elseif ($locked !== []) {
                    $locked = array_filter($locked, is_string(...));
                    App::backend()->notices()->addWarningNotice(
                        sprintf(__('Following themes updates are locked: %s'), implode(', ', $locked))
                    );
                } else {
                    throw new Exception(__('No such theme.'));
                }
                Http::redirect($this->getURL('', true, $tab));
            }

            # Manual actions
            elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
                || !empty($_POST['fetch_pkg'])   && !empty($_POST['pkg_url'])) {
                if (empty($_POST['your_pwd']) || !is_string($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                $dest = '';
                if (!empty($_POST['upload_pkg'])) {
                    /**
                     * @var array{name: string, type: string, size: int, tmp_name: string, error?: int, full_path: string}  $pkg_file
                     */
                    $pkg_file = $_FILES['pkg_file'];
                    Files::uploadStatus($pkg_file);

                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . $pkg_file['name'];
                    if (!move_uploaded_file($pkg_file['tmp_name'], $dest)) {
                        throw new Exception(__('Unable to move uploaded file.'));
                    }
                } else {
                    $url = is_string($url = $_POST['pkg_url']) ? $url : '';
                    if ($url !== '') {
                        $url  = urldecode($url);
                        $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($url);
                        $this->store->download($url, $dest);
                    }
                }

                if ($dest === '') {
                    throw new Exception(__('Upload or download error'));
                }

                # --BEHAVIOR-- themeBeforeAdd --
                App::behavior()->callBehavior('themeBeforeAdd', null);

                $ret_code = $this->store->install($dest);

                # --BEHAVIOR-- themeAfterAdd --
                App::behavior()->callBehavior('themeAfterAdd', null);

                App::backend()->notices()->addSuccessNotice(
                    $ret_code === $this->modules::PACKAGE_UPDATED ?
                    __('The theme has been successfully updated.') :
                    __('The theme has been successfully installed.')
                );
                Http::redirect($this->getURL('', true, 'themes'));
            } else {
                # --BEHAVIOR-- adminModulesListDoActions -- ModulesList, array<int,string>, string
                App::behavior()->callBehavior('adminModulesListDoActions', $this, $modules, 'theme');
            }
        }
    }

    /**
     * Get path of module configuration file.
     *
     * @note Required previously set file info
     *
     * @return null|string    Full path of config file or null
     */
    public function includeConfiguration(): ?string
    {
        if ($this->config_class === '' && !$this->config_file) {
            return null;
        }
        $this->setRedir($this->getURL('', true, 'themes'));

        ob_start();

        if ($this->config_class !== '' && $this->config_class::init() && $this->config_class::process()) {
            $this->config_class::render();

            return null;
        }

        return $this->config_file;
    }

    /**
     * Remove any existing compiled theme (and theme's parent) template cache files
     *
     * @since 2.31
     */
    private function emptyThemeTemplatesCache(): void
    {
        $theme = App::blog()->settings()->get('system')->getStr('theme', false);
        $paths = [
            App::config()->varRoot() . '/themes/' . App::blog()->id() . '/' . $theme . '/tpl',
            App::blog()->themesPath() . '/' . $theme . '/tpl',
        ];

        $parent_theme = is_string($parent_theme = App::themes()->moduleInfo($theme, 'parent')) ? $parent_theme : '';
        if ($parent_theme !== '') {
            $paths[] = App::blog()->themesPath() . '/' . $parent_theme . '/tpl';
        }

        // Instantiate a shadow template engine with only path(s) from theme (and theme's parent if any)
        $engine = new Template(App::config()->cacheRoot(), 'App::frontend()->template()');
        $engine->setPath($paths);

        // Template stack
        $stack = [];
        // Loop on template paths
        foreach ($paths as $path) {
            if (is_dir($path) && is_readable($path)) {
                $files = Files::scandir($path);
                foreach ($files as $filename) {
                    if (preg_match('/^(.*)\.(html|xml|xsl)$/', $filename, $matches) && !in_array($filename, $stack)) {
                        $stack[] = $filename;
                        $cache   = $engine->getFileCachePath((string) $engine->getFilePath($filename));
                        if (file_exists($cache)) {
                            try {
                                unlink($cache);
                            } catch (Exception) {
                                // Ignore deletion error (may be fixed later by administrator using full template cache emptying)
                            }
                        }
                    }
                }
            }
        }
    }
}
