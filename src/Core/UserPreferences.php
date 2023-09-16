<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Interface\Core\UserPreferencesInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Exception;

/**
 * @brief   User prefs handler.
 *
 * UserPreferences provides user preferences management. This class instance exists as
 * Auth $prefs property. You should create a new prefs instance when
 * updating another user prefs.
 */
class UserPreferences implements UserPreferencesInterface
{
    /**
     * Preferences table name.
     *
     * @var     string  $table
     */
    protected $table;

    /**
     * User ID.
     *
     * @var     string  $user_id
     */
    protected $user_id;

    /**
     * Associative workspaces array.
     *
     * @var     array   $workspaces
     */
    protected $workspaces = [];

    public function __construct(string $user_id, ?string $workspace = null)
    {
        $this->table   = App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME;
        $this->user_id = $user_id;

        try {
            $this->loadPrefs($workspace);
        } catch (Exception $e) {
            trigger_error(__('Unable to retrieve workspaces:') . ' ' . App::con()->error(), E_USER_ERROR);
        }
    }

    /**
     * Loads preferences.
     *
     * @param   null|string     $workspace  The workspace
     *
     * @throws  Exception
     */
    private function loadPrefs(?string $workspace = null): void
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                'user_id',
                'pref_id',
                'pref_value',
                'pref_type',
                'pref_label',
                'pref_ws',
            ])
            ->from($this->table)
            ->where($sql->orGroup([
                'user_id = ' . $sql->quote($this->user_id),
                'user_id IS NULL',
            ]))
            ->order([
                'pref_ws ASC',
                'pref_id ASC',
            ]);
        if ($workspace !== null) {
            $sql->and('pref_ws = ' . $sql->quote($workspace));
        }

        try {
            $rs = $sql->select();
        } catch (Exception $e) {
            throw $e;
        }

        /* Prevent empty tables (install phase, for instance) */
        if ($rs->isEmpty()) {
            return;
        }

        do {
            $workspace = trim((string) $rs->f('pref_ws'));
            if (!$rs->isStart()) {
                // we have to go up 1 step, since workspaces construction performs a fetch()
                // at very first time
                $rs->movePrev();
            }
            $this->workspaces[$workspace] = App::userWorkspace()->init($this->user_id, $workspace, $rs);
        } while (!$rs->isStart());
    }

    public function addWorkspace(string $workspace): UserWorkspaceInterface
    {
        if (!$this->exists($workspace)) {
            $this->workspaces[$workspace] = App::userWorkspace()->init($this->user_id, $workspace);
        }

        return $this->workspaces[$workspace];
    }

    public function renWorkspace(string $old_workspace, string $new_workspace): bool
    {
        if (!$this->exists($old_workspace) || $this->exists($new_workspace)) {
            return false;
        }

        if (!preg_match(App::userWorkspace()::WS_NAME_SCHEMA, $new_workspace)) {
            throw new Exception(sprintf(__('Invalid UserWorkspace: %s'), $new_workspace));
        }

        // Rename the workspace in the database
        $sql = new UpdateStatement();
        $sql
            ->ref($this->table)
            ->set('pref_ws = ' . $sql->quote($new_workspace))
            ->where('pref_ws = ' . $sql->quote($old_workspace));
        $sql->update();

        // Reload the renamed workspace in the workspace array
        $this->workspaces[$new_workspace] = App::userWorkspace()->init($this->user_id, $new_workspace);

        // Remove the old workspace from the workspace array
        unset($this->workspaces[$old_workspace]);

        return true;
    }

    public function delWorkspace(string $workspace): bool
    {
        if (!$this->exists($workspace)) {
            return false;
        }

        // Remove the workspace from the workspace array
        unset($this->workspaces[$workspace]);

        // Delete all preferences from the workspace in the database
        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('pref_ws = ' . $sql->quote($workspace));

        $sql->delete();

        return true;
    }

    public function get(string $workspace): UserWorkspaceInterface
    {
        return $this->addWorkspace($workspace);
    }

    public function __get(string $workspace): UserWorkspaceInterface
    {
        return $this->addWorkspace($workspace);
    }

    public function exists(string $workspace): bool
    {
        return array_key_exists($workspace, $this->workspaces);
    }

    public function dumpWorkspaces(): array
    {
        return $this->workspaces;
    }
}
