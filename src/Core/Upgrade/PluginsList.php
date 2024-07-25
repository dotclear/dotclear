<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Interface\Module\ModulesInterface;
use Dotclear\Module\ModuleDefine;
use Dotclear\Module\Store;
use Exception;

/**
 * @breif   Helper for upgrade list of plugins.
 *
 * Provides an object to parse XML feed of modules from a repository.
 *
 * @since   2.29
 */
class PluginsList extends ModulesList
{
    /**
     * Store instance.
     *
     * @var     Store   $store
     */
    public readonly Store $store;

    /**
     * Constructor.
     *
     * Note that this creates Store instance.
     *
     * @param   ModulesInterface    $modules        Modules instance
     * @param   string              $modules_root   Modules root directories
     * @param   null|string         $xml_url        URL of modules feed from repository
     * @param   null|bool           $force          Force query repository
     */
    public function __construct(ModulesInterface $modules, string $modules_root, ?string $xml_url, ?bool $force = false)
    {
        $this->modules = $modules;
        $this->store   = new Store($modules, $xml_url, $force);

        $this->setUrl(App::upgrade()->url()->get('upgrade.plugins'));

        $this->setPath($modules_root);
        $this->setIndex(__('other'));
    }

    /**
     * Get settings URLs if any.
     *
     * @param   string      $id     Module ID
     * @param   boolean     $check  Check permission
     * @param   boolean     $self   Include self URL (→ plugin index.php URL)
     *
     * @return  array<string>   Array of settings URLs
     */
    public static function getSettingsUrls(string $id, bool $check = false, bool $self = true): array
    {
        $settings_urls = [];

        return $settings_urls;
    }

    /**
     * Get action buttons to add to modules list.
     *
     * @param   ModuleDefine    $define     Module info
     * @param   array<string>   $actions    Actions keys
     *
     * @return  array<string>  Array of actions buttons
     */
    protected function getActions(ModuleDefine $define, array $actions): array
    {
        $submits = [];
        $id      = $define->getId();

        // mark module state
        if ($define->get('state') != ModuleDefine::STATE_ENABLED) {
            $submits[] = (new Hidden(['disabled[' . Html::escapeHTML($id) . ']'], '1'))
            ->render();
        }

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {
                # Deactivate
                case 'activate':
                    // do not allow activation of duplciate modules already activated
                    $multi = !self::$allow_multi_install && count($this->modules->getDefines(['id' => $id, 'state' => ModuleDefine::STATE_ENABLED])) > 0;
                    if ($define->get('root_writable') && empty($define->getMissing()) && !$multi) {
                        $submits[] = (new Submit(['activate[' . Html::escapeHTML($id) . ']'], __('Activate')))
                        ->render();
                    }

                    break;

                    # Activate
                case 'deactivate':
                    if ($define->get('root_writable') && empty($define->getUsing())) {
                        $submits[] = (new Submit(['deactivate[' . Html::escapeHTML($id) . ']'], __('Deactivate')))
                            ->class('reset')
                        ->render();
                    }

                    break;

                    # Delete
                case 'delete':
                    if (!$define->distributed && $this->isDeletablePath($define->get('root')) && empty($define->getUsing())) {
                        $dev       = !preg_match('!^' . $this->path_pattern . '!', $define->get('root')) && App::config()->devMode() ? ' debug' : '';
                        $submits[] = (new Submit(['delete[' . Html::escapeHTML($id) . ']'], __('Delete')))
                            ->class(array_filter(['delete', $dev]))
                        ->render();
                    }

                    break;

                    # Install (from store)
                case 'install':
                    if ($this->path_writable) {
                        $submits[] = (new Submit(['install[' . Html::escapeHTML($id) . ']'], __('Install')))
                        ->render();
                    }

                    break;

                    # Update (from store)
                case 'update':
                    if ($this->path_writable && !$define->updLocked()) {
                        $submits[] = (new Submit(['update[' . Html::escapeHTML($id) . ']'], __('Update')))
                        ->render();
                    }

                    break;
            }
        }

        return $submits;
    }

    /**
     * Get global action buttons to add to modules list.
     *
     * @param   array<string>   $actions            Actions keys
     * @param   bool            $with_selection     Limit action to selected modules
     *
     * @return  array<string>   Array of actions buttons
     */
    protected function getGlobalActions(array $actions, bool $with_selection = false): array
    {
        $submits = [];

        # Use loop to keep requested order
        foreach ($actions as $action) {
            switch ($action) {
                # Deactivate
                case 'activate':
                    if ($this->path_writable) {
                        $submits[] = (new Submit(['activate'], $with_selection ?
                            __('Activate selected plugins') :
                            __('Activate all plugins from this list')))
                        ->render();
                    }

                    break;

                    # Activate
                case 'deactivate':
                    if ($this->path_writable) {
                        $submits[] = (new Submit(['deactivate'], $with_selection ?
                            __('Deactivate selected plugins') :
                            __('Deactivate all plugins from this list')))
                        ->render();
                    }

                    break;

                    # Update (from store)
                case 'update':
                    if ($this->path_writable) {
                        $submits[] = (new Submit(['update'], $with_selection ?
                            __('Update selected plugins') :
                            __('Update all plugins from this list')))
                        ->render();
                    }

                    break;
            }
        }

        return $submits;
    }

    /**
     * Execute POST action.
     *
     * Set a notice on success through Notices::addSuccessNotice
     *
     * @throws  Exception   Module not find or command failed
     */
    public function doActions(): void
    {
        if (empty($_POST) || !empty($_REQUEST['conf'])
                          || !$this->isWritablePath()) {
            return;
        }

        $modules = !empty($_POST['modules']) && is_array($_POST['modules']) ? array_values($_POST['modules']) : [];

        if (!empty($_POST['delete'])) {
            if (is_array($_POST['delete'])) {
                $modules = array_keys($_POST['delete']);
            }

            $failed = false;
            $count  = 0;
            foreach ($modules as $id) {
                $disabled = !empty($_POST['disabled'][$id]);
                $define   = $this->modules->getDefine($id, ['state' => ($disabled ? '!' : '') . ModuleDefine::STATE_ENABLED]);
                // module is not defined
                if (!$define->isDefined()) {
                    throw new Exception(__('No such plugin.'));
                }
                if (!$this->isDeletablePath($define->get('root'))) {
                    $failed = true;

                    continue;
                }

                $this->modules->deleteModule($define->getId(), $disabled);

                $count++;
            }

            if (!$count && $failed) {
                throw new Exception(__("You don't have permissions to delete this plugin."));
            } elseif ($failed) {
                Notices::addWarningNotice(__('Some plugins have not been delete.'));
            } else {
                Notices::addSuccessNotice(
                    __('Plugin has been successfully deleted.', 'Plugins have been successuflly deleted.', $count)
                );
            }
            Http::redirect($this->getURL());
        } elseif (!empty($_POST['install'])) {
            if (is_array($_POST['install'])) {
                $modules = array_keys($_POST['install']);
            }

            $count = 0;
            foreach ($this->store->getDefines() as $define) {
                if (!in_array($define->getId(), $modules)) {
                    continue;
                }

                $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($define->get('file'));

                $this->store->process($define->get('file'), $dest);

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            Notices::addSuccessNotice(
                __('Plugin has been successfully installed.', 'Plugins have been successfully installed.', $count)
            );
            Http::redirect($this->getURL());
        } elseif (!empty($_POST['activate'])) {
            if (is_array($_POST['activate'])) {
                $modules = array_keys($_POST['activate']);
            }

            $count = 0;
            foreach ($modules as $id) {
                $define = $this->modules->getDefine($id, ['state' => '!' . ModuleDefine::STATE_ENABLED]);
                if (!$define->isDefined()) {
                    continue;
                }

                $this->modules->activateModule($define->getId());

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            Notices::addSuccessNotice(
                __('Plugin has been successfully activated.', 'Plugins have been successuflly activated.', $count)
            );
            Http::redirect($this->getURL());
        } elseif (!empty($_POST['deactivate'])) {
            if (is_array($_POST['deactivate'])) {
                $modules = array_keys($_POST['deactivate']);
            }

            $failed = false;
            $count  = 0;
            foreach ($modules as $id) {
                $define = $this->modules->getDefine($id, ['state' => '!' . ModuleDefine::STATE_HARD_DISABLED]);
                if (!$define->isDefined()) {
                    continue;
                }

                if (!$define->get('root_writable')) {
                    $failed = true;

                    continue;
                }

                $this->modules->deactivateModule($define->getId());

                $count++;
            }

            if (!$count) {
                throw new Exception(__('No such plugin.'));
            }

            if ($failed) {
                Notices::addWarningNotice(__('Some plugins have not been deactivated.'));
            } else {
                Notices::addSuccessNotice(
                    __('Plugin has been successfully deactivated.', 'Plugins have been successuflly deactivated.', $count)
                );
            }
            Http::redirect($this->getURL());
        } elseif (!empty($_POST['update'])) {
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

                if (!self::$allow_multi_install) {
                    $dest = implode(DIRECTORY_SEPARATOR, [Path::dirWithSym($define->get('root')), '..', basename($define->get('file'))]);
                } else {
                    $dest = $this->getPath() . DIRECTORY_SEPARATOR . basename($define->get('file'));
                    if ($define->get('root') != $dest) {
                        @file_put_contents($define->get('root') . DIRECTORY_SEPARATOR . $this->modules::MODULE_FILE_DISABLED, '');
                    }
                }
                $this->store->process($define->get('file'), $dest);

                $count++;
            }

            $tab = $count == count($defines) ? '#plugins' : '#update';

            if ($count) {
                Notices::addSuccessNotice(
                    __('Plugin has been successfully updated.', 'Plugins have been successfully updated.', $count)
                );
            } elseif (!empty($locked)) {
                Notices::addWarningNotice(
                    sprintf(__('Following plugins updates are locked: %s'), implode(', ', $locked))
                );
            } else {
                throw new Exception(__('No such plugin.'));
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

            $ret_code = $this->store->install($dest);

            Notices::addSuccessNotice(
                $ret_code === $this->modules::PACKAGE_UPDATED ?
                __('The plugin has been successfully updated.') :
                __('The plugin has been successfully installed.')
            );
            Http::redirect($this->getURL() . '#plugins');
        }
    }

    /**
     * Helper to sanitize a string.
     *
     * Used for search or id.
     *
     * @param   string  $str    String to sanitize
     *
     * @return  string  Sanitized string
     */
    public static function sanitizeString(string $str): string
    {
        return (string) preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
    }

    /**
     * Helper to check if a module's ns class or file exists.
     *
     * @param   string  $id     The module identifier
     * @param   string  $class  The module class name
     * @param   string  $file   The module file name
     *
     * @return  bool    True if one exists
     */
    protected static function hasFileOrClass(string $id, string $class, string $file): bool
    {
        return false;
    }
}
