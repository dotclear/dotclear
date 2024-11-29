<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

use Dotclear\Core\Process;
use Dotclear\Helper\Text;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief Modules uninstall features handler.
 * @ingroup Uninstaller
 *
 * Provides an object to handle modules uninstall features.
 */
class Uninstaller
{
    /**
     * The module Uninstall class name.
     *
     * @var     string  UNINSTALL_CLASS_NAME
     */
    public const UNINSTALL_CLASS_NAME = 'Uninstall';

    /**
     * The cleaners stack.
     *
     * @var     CleanersStack   $cleaners
     */
    public readonly CleanersStack $cleaners;

    /**
     * Current module.
     *
     * @var     null|ModuleDefine   $module
     */
    private ?ModuleDefine $module = null;

    /**
     * Loaded modules stack.
     *
     * @var     array<string,ModuleDefine>  $modules
     */
    private array $modules = [];

    /**
     * List of modules with custom actions render.
     *
     * @var     array<int,string>   $renders
     */
    private array $renders = [];

    /**
     * List of registered user actions
     *
     * @var     array<string,ActionsStack>  $user_actions
     */
    private array $user_actions = [];

    /**
     * List of registered direct actions.
     *
     * @var     array<string,ActionsStack>  $direct_actions
     */
    private array $direct_actions = [];

    /**
     * Uninstaller instance.
     *
     * @var     Uninstaller     $uninstaller
     */
    private static $uninstaller;

    /**
     * Constructor load cleaners.
     */
    public function __construct()
    {
        $this->cleaners = new CleanersStack();
    }

    /**
     * Get singleton instance.
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public static function instance(): Uninstaller
    {
        if (!is_a(self::$uninstaller, Uninstaller::class)) {
            self::$uninstaller = new Uninstaller();
        }

        return self::$uninstaller;
    }

    /**
     * Load modules.
     *
     * This also resets previously loaded modules and actions.
     *
     * @param   array<int,ModuleDefine>     $modules    List of modules Define
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public function loadModules(array $modules): Uninstaller
    {
        // reset unsintaller
        $this->module         = null;
        $this->modules        = [];
        $this->renders        = [];
        $this->user_actions   = [];
        $this->direct_actions = [];

        foreach ($modules as $module) {
            if (!($module instanceof ModuleDefine)) {   // @phpstan-ignore-line
                continue;
            }
            $class = $module->get('namespace') . '\\' . self::UNINSTALL_CLASS_NAME;
            if ($module->getId() != My::id() && is_a($class, Process::class, true)) {
                $this->modules[$module->getId()] = $this->module = $module;
                // check class prerequiretics
                if ($class::init()) {
                    // if class process returns true
                    if ($class::process()) {
                        // add custom action (served by class render method )
                        $this->renders[] = $module->getId();
                    }
                    $this->module = null;
                }
            }
        }
        uasort(
            $this->modules,
            fn ($a, $b) => Text::removeDiacritics(mb_strtolower(is_string($a->get('name')) ? $a->get('name') : $a->getId())) <=> Text::removeDiacritics(mb_strtolower(is_string($b->get('name')) ? $b->get('name') : $b->getId()))
        );

        return $this;
    }

    /**
     * Check if the module <var>$id</var> has action custom fields.
     *
     * @param   string  $id     Module ID
     *
     * @return  boolean     Success
     */
    public function hasRender(string $id): bool
    {
        return isset($this->modules[$id]) && in_array($id, $this->renders);
    }

    /**
     * Add a predefined action to user unsintall features.
     *
     * This method should be called from module Uninstall::proces() method.
     * User will be prompted before doing these actions.
     *
     * Leave $default param to null to let Cleaner decide.
     *
     * @param   string      $cleaner    The cleaner ID
     * @param   string      $action     The action ID
     * @param   string      $ns         Name of setting related to module
     * @param   null|null   $default    The default state of form field (checked)
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public function addUserAction(string $cleaner, string $action, string $ns, ?bool $default = null): Uninstaller
    {
        if (null !== $this->module && null !== ($res = $this->addAction($cleaner, $action, $ns, $default))) {
            if (!isset($this->user_actions[$this->module->getId()])) {
                $this->user_actions[$this->module->getId()] = new ActionsStack();
            }
            $this->user_actions[$this->module->getId()]->get($cleaner)->set($res);
        }

        return $this;
    }

    /**
     * Add a predefined action to direct uninstall features.
     *
     * This method should be called from module Uninstall::process() method.
     * Direct actions will be called from behavior xxxBeforeDelete and
     * user will NOT be prompted before these actions execution.
     * Note: If module is disabled, direct actions are not executed.
     *
     * @param   string      $cleaner    The cleaner ID
     * @param   string      $action     The action ID
     * @param   string      $ns         Name of setting related to module
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public function addDirectAction(string $cleaner, string $action, string $ns): Uninstaller
    {
        if (null !== $this->module && null !== ($res = $this->addAction($cleaner, $action, $ns, true))) {
            if (!isset($this->direct_actions[$this->module->getId()])) {
                $this->direct_actions[$this->module->getId()] = new ActionsStack();
            }
            $this->direct_actions[$this->module->getId()]->get($cleaner)->set($res);
        }

        return $this;
    }

    /**
     * Get modules <var>$id</var> predefined user actions associative array
     *
     * @param   string  $id     The module ID
     *
     * @return  ActionsStack   List module user actions group by cleaner
     */
    public function getUserActions(string $id): ActionsStack
    {
        return $this->user_actions[$id] ?? new ActionsStack();
    }

    /**
     * Get modules <var>$id</var> predefined direct actions associative array
     *
     * @param   string  $id     The module ID
     *
     * @return  ActionsStack   List module direct actions group by cleaner
     */
    public function getDirectActions(string $id): ActionsStack
    {
        return $this->direct_actions[$id] ?? new ActionsStack();
    }

    /**
     * Get module <var>$id</var> custom actions fields.
     *
     * @param   string  $id     The module ID
     *
     * @return  string  HTML render of custom form fields
     */
    public function render(string $id): string
    {
        $output = '';
        if ($this->hasRender($id)) {
            $class = $this->modules[$id]->get('namespace') . '\\' . self::UNINSTALL_CLASS_NAME;

            ob_start();

            try {
                $class::render();
                $output = (string) ob_get_contents();
            } catch (Exception) {
            }
            ob_end_clean();
        }

        return $output;
    }

    /**
     * Execute a predifined action.
     *
     * This function call dcAdvancedCleaner to do actions.
     *
     * @param   string      $cleaner    The cleaner ID
     * @param   string      $action     The action ID
     * @param   string      $ns         Name of setting related to module.
     *
     * @return  boolean     The success
     */
    public function execute(string $cleaner, string $action, string $ns): bool
    {
        // unknown cleaner/action or no ns
        if (!isset($this->cleaners->get($cleaner)->actions[$action]) || empty($ns)) {
            return false;
        }

        $this->cleaners->execute($cleaner, $action, $ns);

        return true;
    }

    /**
     * Add a predefined action to unsintall features.
     *
     * @param   string      $cleaner    The cleaner ID
     * @param   string      $action     The action ID
     * @param   string      $ns         Name of setting related to module
     * @param   null|bool   $default    The default state of form field (checked)
     *
     * @return  null|ActionDescriptor   The action description
     */
    private function addAction(string $cleaner, string $action, string $ns, ?bool $default)
    {
        // no current module or no cleaner id or no ns or unknown cleaner action
        if (null === $this->module
            || empty($cleaner)
            || empty($ns)
            || !isset($this->cleaners->get($cleaner)->actions[$action])
        ) {
            return null;
        }

        // fill action properties
        return new ActionDescriptor(
            id: $action,
            ns: $ns,
            select: $this->cleaners->get($cleaner)->actions[$action]->select,
            query: sprintf($this->cleaners->get($cleaner)->actions[$action]->query, $ns),
            success: sprintf($this->cleaners->get($cleaner)->actions[$action]->success, $ns),
            error: sprintf($this->cleaners->get($cleaner)->actions[$action]->error, $ns),
            default: is_null($default) ? $this->cleaners->get($cleaner)->actions[$action]->default : $default
        );
    }
}
