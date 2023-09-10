<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Autoloader;
use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Interface\Module\ModulesInterface;
use Dotclear\Module\ModuleDefine;
use Exception;
use form;

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
     * @param    ModulesInterface   $modules        ModulesInterface instance
     * @param    string             $modules_root   Modules root directories
     * @param    string             $xml_url        URL of modules feed from repository
     * @param    null|bool          $force          Force query repository
     */
    public function __construct(ModulesInterface $modules, string $modules_root, string $xml_url, ?bool $force = false)
    {
        parent::__construct($modules, $modules_root, $xml_url, $force);
        $this->page_url = App::backend()->url->get('admin.blog.theme');
    }

    /**
     * Display themes list
     *
     * @param      array  $cols       The cols
     * @param      array  $actions    The actions
     * @param      bool   $nav_limit  The navigation limit
     */
    public function displayModules(array $cols = ['name', 'config', 'version', 'desc'], array $actions = [], bool $nav_limit = false): ThemesList
    {
        echo
        '<form action="' . $this->getURL() . '" method="post" class="modules-form-actions">' .
        '<div id="' . Html::escapeHTML($this->list_id) . '" class="modules' . (in_array('expander', $cols) ? ' expandable' : '') . ' one-box">';

        $sort_field = $this->getSort();

        # Sort modules by id
        if ($this->getSearch() === null) {
            uasort($this->defines, fn ($a, $b) => $a->get($sort_field) <=> $b->get($sort_field));
        }

        $res   = '';
        $count = 0;
        foreach ($this->defines as $define) {
            $id = $define->getId();

            # Show only requested modules
            if ($nav_limit && $this->getSearch() === null) {
                $char = substr($define->get($sort_field), 0, 1);
                if (!in_array($char, $this->nav_list)) {
                    $char = $this->nav_special;
                }
                if ($this->getIndex() != $char) {
                    continue;
                }
            }

            $current = App::blog()->settings()->system->theme == $id && $this->modules->moduleExists($id);
            $distrib = $define->get('distributed') ? ' dc-box' : '';

            $git = ((defined('DC_DEV') && DC_DEV) || App::config()->debugMode()) && file_exists($define->get('root') . DIRECTORY_SEPARATOR . '.git');

            $line = '<div class="box ' . ($current ? 'medium current-theme' : 'theme') . $distrib . ($git ? ' module-git' : '') . '">';

            if (in_array('name', $cols) && !$current) {
                $line .= '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .= '<label for="' . Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id) . '">' .
                    form::checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name')) .
                        '</label>';
                } else {
                    $line .= form::hidden(['modules[' . $count . ']'], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name'));
                }

                $line .= App::nonce()->getFormNonce() .
                '</h4>';
            }

            # Display score only for debug purpose
            if (in_array('score', $cols) && $this->getSearch() !== null && App::config()->debugMode()) {
                $line .= '<p class="module-score debug">' . sprintf(__('Score: %s'), $define->get('score')) . '</p>';
            }

            if (in_array('sshot', $cols)) {
                # Screenshot from url
                if (preg_match('#^http(s)?://#', $define->get('sshot'))) {
                    $sshot = $define->get('sshot');
                }
                # Screenshot from installed module
                elseif (file_exists(App::blog()->themesPath() . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'screenshot.jpg')) {
                    $sshot = $this->getURL('shot=' . rawurlencode($id));
                }
                # Default screenshot
                else {
                    $sshot = 'images/noscreenshot.png';
                }

                $line .= '<div class="module-sshot"><img src="' . $sshot . '" loading="lazy" alt="' .
                sprintf(__('%s screenshot.'), Html::escapeHTML($define->get('name'))) . '" /></div>';
            }

            $line .= $current ? '' : '<details><summary>' . __('Details') . '</summary>';
            $line .= '<div class="module-infos">';

            if (in_array('name', $cols) && $current) {
                $line .= '<h4 class="module-name">';

                if (in_array('checkbox', $cols)) {
                    $line .= '<label for="' . Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id) . '">' .
                    form::checkbox(['modules[' . $count . ']', Html::escapeHTML($this->list_id) . '_modules_' . Html::escapeHTML($id)], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name')) .
                        '</label>';
                } else {
                    $line .= form::hidden(['modules[' . $count . ']'], Html::escapeHTML($id)) .
                    Html::escapeHTML($define->get('name'));
                }

                $line .= '</h4>';
            }

            $line .= '<p>';

            if (in_array('desc', $cols)) {
                $line .= '<span class="module-desc">' . Html::escapeHTML(__($define->get('desc'))) . '</span> ';
            }

            if (in_array('author', $cols)) {
                $line .= '<span class="module-author">' . sprintf(__('by %s'), Html::escapeHTML($define->get('author'))) . '</span> ';
            }

            if (in_array('version', $cols)) {
                $line .= '<span class="module-version">' . sprintf(__('version %s'), Html::escapeHTML($define->get('version'))) . '</span> ';
            }

            if (in_array('current_version', $cols)) {
                $line .= '<span class="module-current-version">' . sprintf(__('(current version %s)'), Html::escapeHTML($define->get('current_version'))) . '</span> ';
            }

            if (in_array('parent', $cols) && !empty($define->get('parent'))) {
                if ($this->modules->moduleExists($define->get('parent'))) {
                    $line .= '<span class="module-parent-ok">' . sprintf(__('(built on "%s")'), Html::escapeHTML($define->get('parent'))) . '</span> ';
                } else {
                    $line .= '<span class="module-parent-missing">' . sprintf(__('(requires "%s")'), Html::escapeHTML($define->get('parent'))) . '</span> ';
                }
            }

            if (in_array('repository', $cols) && DC_ALLOW_REPOSITORIES) {
                $line .= '<span class="module-repository">' . (!empty($define->get('repository')) ? __('Third-party repository') : __('Official repository')) . '</span> ';
            }

            if ($define->updLocked()) {
                $line .= '<span class="module-locked">' . __('update locked') . '</span> ';
            }

            $has_details = in_array('details', $cols) && !empty($define->get('details'));
            $has_support = in_array('support', $cols) && !empty($define->get('support'));
            if ($has_details || $has_support) {
                $line .= '<span class="mod-more">';

                if ($has_details) {
                    $line .= '<a class="module-details" href="' . $define->get('details') . '">' . __('Details') . '</a>';
                }

                if ($has_support) {
                    $line .= ' - <a class="module-support" href="' . $define->get('support') . '">' . __('Support') . '</a>';
                }

                $line .= '</span>';
            }

            $line .= '</p>' .
                '</div>';
            $line .= '<div class="module-actions">';

            # Plugins actions
            if ($current) {
                # _GET actions
                if (file_exists(Path::real(App::blog()->themesPath() . DIRECTORY_SEPARATOR . $id) . DIRECTORY_SEPARATOR . 'style.css')) {
                    $theme_url = preg_match('#^http(s)?://#', (string) App::blog()->settings()->system->themes_url) ?
                    Http::concatURL(App::blog()->settings()->system->themes_url, '/' . $id) :
                    Http::concatURL(App::blog()->url(), App::blog()->settings()->system->themes_url . '/' . $id);
                    $line .= '<p><a href="' . $theme_url . '/style.css">' . __('View stylesheet') . '</a></p>';
                }

                $line .= '<div class="current-actions">';

                // by class name
                $class = $define->get('namespace') . Autoloader::NS_SEP . $this->modules::MODULE_CLASS_CONFIG;
                if (!empty($define->get('namespace')) && class_exists($class)) {
                    $config = $class::init();
                    // by file name
                } else {
                    $config = file_exists(Path::real(App::blog()->themesPath() . DIRECTORY_SEPARATOR . $id) . DIRECTORY_SEPARATOR . $this->modules::MODULE_FILE_CONFIG);
                }

                if ($config) {
                    $line .= '<p><a href="' . $this->getURL('module=' . $id . '&amp;conf=1', false) . '" class="button submit">' . __('Configure theme') . '</a></p>';
                }

                # --BEHAVIOR-- adminCurrentThemeDetails -- string, ModuleDefine
                $line .= App::behavior()->callBehavior('adminCurrentThemeDetailsV2', $define->getId(), $define);

                $line .= '</div>';
            }

            # _POST actions
            if (!empty($actions)) {
                $line .= '<p class="module-post-actions">' . implode(' ', $this->getActions($define, $actions)) . '</p>';
            }

            $line .= '</div>';
            $line .= $current ? '' : '</details>';

            $line .= '</div>';

            $count++;

            $res = $current ? $line . $res : $res . $line;
        }

        echo
            $res .
            '</div>';

        if (!$count && $this->getSearch() === null) {
            echo
            '<p class="message">' . __('No themes matched your search.') . '</p>';
        } elseif ((in_array('checkbox', $cols) || $count > 1) && !empty($actions) && App::auth()->isSuperAdmin()) {
            $buttons = $this->getGlobalActions($actions, in_array('checkbox', $cols));

            if (!empty($buttons)) {
                if (in_array('checkbox', $cols)) {
                    echo
                        '<p class="checkboxes-helpers"></p>';
                }
                echo '<div>' . implode(' ', $buttons) . '</div>';
            }
        }

        echo
            '</form>';

        return $this;
    }

    /**
     * Gets the actions.
     *
     * @param      ModuleDefine     $define   The module define
     * @param      array            $actions  The actions
     *
     * @return     array  The actions.
     */
    protected function getActions(ModuleDefine $define, array $actions): array
    {
        $submits = [];
        $id      = $define->getId();

        // mark module state
        if ($define->get('state') != ModuleDefine::STATE_ENABLED) {
            $submits[] = '<input type="hidden" name="disabled[' . Html::escapeHTML($id) . ']" value="1" />';
        }

        if ($id != App::blog()->settings()->system->theme) {
            # Select theme to use on curent blog
            if (in_array('select', $actions)) {
                $submits[] = '<input type="submit" name="select[' . Html::escapeHTML($id) . ']" value="' . __('Use this one') . '" />';
            }
            if (in_array('try', $actions)) {
                $preview_url = App::blog()->url() . App::url()->getURLFor('try', App::auth()->userID() . '/' . Http::browserUID(DC_MASTER_KEY . App::auth()->userID() . App::auth()->cryptLegacy(App::auth()->userID())) . '/' . $id);

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = App::auth()->prefs()->interface->blank_preview;

                $preview_class  = $blank_preview ? '' : ' modal';
                $preview_target = $blank_preview ? '' : ' target="_blank"';

                $submits[] = '<a href="' . $preview_url . '" class="button theme-preview' . $preview_class . '" accesskey="p"' . $preview_target . '>' . __('Preview') . '</a>';
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

        if ($define->get('distributed') && ($pos = array_search('delete', $actions, true))) {
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
     * @param      array   $actions         The actions
     * @param      bool    $with_selection  The with selection
     *
     * @return     array   The global actions.
     */
    protected function getGlobalActions(array $actions, bool $with_selection = false): array
    {
        $submits = [];

        foreach ($actions as $action) {
            switch ($action) {
                # Update (from store)
                case 'update':

                    if (App::auth()->isSuperAdmin() && $this->path_writable) {
                        $submits[] = '<input type="submit" name="update" value="' . (
                            $with_selection ?
                            __('Update selected themes') :
                            __('Update all themes from this list')
                        ) . '" />' . App::nonce()->getFormNonce();
                    }

                    break;

                    # Behavior
                case 'behavior':

                    # --BEHAVIOR-- adminModulesListGetGlobalActions -- ModulesList
                    $tmp = App::behavior()->callBehavior('adminModulesListGetGlobalActions', $this);

                    if (!empty($tmp)) {
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
    public function doActions()
    {
        if (empty($_POST) || !empty($_REQUEST['conf'])) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : [];

        if (!empty($_POST['select'])) {
            # Can select only one theme at a time!
            if (is_array($_POST['select'])) {
                $modules = array_keys($_POST['select']);
                $define  = $this->modules->getDefine($modules[0]);

                if (!$define->isDefined()) {
                    throw new Exception(__('No such theme.'));
                }

                App::blog()->settings()->system->put('theme', $define->getId());
                App::blog()->triggerBlog();

                Notices::addSuccessNotice(sprintf(__('Theme %s has been successfully selected.'), Html::escapeHTML($define->get('name'))));
                Http::redirect($this->getURL() . '#themes');
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
                    $define = $this->modules->getDefine($id);
                    if (!$define->isDefined() || $define->get('state') == ModuleDefine::STATE_ENABLED) {
                        continue;
                    }

                    # --BEHAVIOR-- themeBeforeActivate -- string
                    App::behavior()->callBehavior('themeBeforeActivate', $define->getId());

                    $this->modules->activateModule($define->getId());

                    # --BEHAVIOR-- themeAfterActivate -- string
                    App::behavior()->callBehavior('themeAfterActivate', $define->getId());

                    $count++;
                }

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                Notices::addSuccessNotice(
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
                    $define = $this->modules->getDefine($id);
                    if (!$define->isDefined() || $define->get('state') == ModuleDefine::STATE_HARD_DISABLED) {
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

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                if ($failed) {
                    Notices::addWarningNotice(__('Some themes have not been deactivated.'));
                } else {
                    Notices::addSuccessNotice(
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
                    $define = $this->modules->getDefine($id);
                    if (!$define->isDefined() || $define->get('state') != ModuleDefine::STATE_ENABLED) {
                        continue;
                    }

                    # --BEHAVIOR-- themeBeforeClone -- string
                    App::behavior()->callBehavior('themeBeforeClone', $define->getId());

                    $this->modules->cloneModule($define->getId());

                    # --BEHAVIOR-- themeAfterClone -- string
                    App::behavior()->callBehavior('themeAfterClone', $define->getId());

                    $count++;
                }

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                Notices::addSuccessNotice(
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
                    $disabled = !empty($_POST['disabled'][$id]);
                    ;
                    $define = $this->modules->getDefine($id, ['state' => ($disabled ? '!' : '') . ModuleDefine::STATE_ENABLED]);
                    if (!$define->isDefined()) {
                        continue;
                    }
                    if (!$this->isDeletablePath($define->get('root'))) {
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

                if (!$count && $failed) {
                    throw new Exception(__("You don't have permissions to delete this theme."));
                } elseif (!$count) {
                    throw new Exception(__('No such theme.'));
                } elseif ($failed) {
                    Notices::addWarningNotice(__('Some themes have not been delete.'));
                } else {
                    Notices::addSuccessNotice(
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

                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($define->get('file'));

                    # --BEHAVIOR-- themeBeforeAdd -- ModuleDefine
                    App::behavior()->callBehavior('themeBeforeAddV2', $define);

                    $this->store->process($define->get('file'), $dest);

                    # --BEHAVIOR-- themeAfterAdd -- ModuleDefine
                    App::behavior()->callBehavior('themeAfterAddV2', $define);

                    $count++;
                }

                if (!$count) {
                    throw new Exception(__('No such theme.'));
                }

                Notices::addSuccessNotice(
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

                    $dest = implode(DIRECTORY_SEPARATOR, [Path::dirWithSym($define->get('root')), '..', basename($define->get('file'))]);

                    # --BEHAVIOR-- themeBeforeUpdate -- ModuleDefine
                    App::behavior()->callBehavior('themeBeforeUpdateV2', $define);

                    $this->store->process($define->get('file'), $dest);

                    # --BEHAVIOR-- themeAfterUpdate -- ModuleDefine
                    App::behavior()->callBehavior('themeAfterUpdateV2', $define);

                    $count++;
                }

                $tab = $count == count($defines) ? '#themes' : '#update';   // @phpstan-ignore-line

                if ($count) {
                    Notices::addSuccessNotice(
                        __('Theme has been successfully updated.', 'Themes have been successfully updated.', $count)
                    );
                } elseif (!empty($locked)) {
                    Notices::addWarningNotice(
                        sprintf(__('Following themes updates are locked: %s'), implode(', ', $locked))
                    );
                } else {
                    throw new Exception(__('No such theme.'));
                }
                Http::redirect($this->getURL() . $tab);
            }

            # Manual actions
            elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])
                || !empty($_POST['fetch_pkg'])   && !empty($_POST['pkg_url'])) {
                if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                if (!empty($_POST['upload_pkg'])) {
                    Files::uploadStatus($_FILES['pkg_file']);

                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . $_FILES['pkg_file']['name'];
                    if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'], $dest)) {
                        throw new Exception(__('Unable to move uploaded file.'));
                    }
                } else {
                    $url  = urldecode($_POST['pkg_url']);
                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($url);
                    $this->store->download($url, $dest);
                }

                # --BEHAVIOR-- themeBeforeAdd --
                App::behavior()->callBehavior('themeBeforeAdd', null);

                $ret_code = $this->store->install($dest);

                # --BEHAVIOR-- themeAfterAdd --
                App::behavior()->callBehavior('themeAfterAdd', null);

                Notices::addSuccessNotice(
                    $ret_code == $this->modules::PACKAGE_UPDATED ?
                    __('The theme has been successfully updated.') :
                    __('The theme has been successfully installed.')
                );
                Http::redirect($this->getURL() . '#themes');
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
     * @return mixed    Full path of config file or null
     */
    public function includeConfiguration()
    {
        if (empty($this->config_class) && !$this->config_file) {
            return;
        }
        $this->setRedir($this->getURL() . '#themes');

        ob_start();

        if (!empty($this->config_class) && $this->config_class::init() && $this->config_class::process()) {
            $this->config_class::render();

            return null;
        }

        return $this->config_file;
    }
}
