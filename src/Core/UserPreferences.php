<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\UserPreferencesInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Exception;

/**
 * @brief   User prefs handler.
 *
 * UserPreferences provides user preferences management. This class instance exists as
 * Auth $prefs property. You should create a new prefs instance when
 * updating another user prefs.
 *
 * @since   2.28, container services have been added to constructor
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
     * @var     array<string, UserWorkspaceInterface>   $workspaces
     */
    protected $workspaces = [];

    /**
     * Constructor.
     *
     * @param   ConnectionInterface     $con                The database connection instance
     * @param   UserWorkspaceInterface  $workspace          The user workspace handler
     * @param   null|string             $user_id            The user ID
     * @param   null|string             $user_workspace     The workspace ID
     */
    public function __construct(
        protected ConnectionInterface $con,
        protected UserWorkspaceInterface $workspace,
        ?string $user_id = null,
        ?string $user_workspace = null
    ) {
        $this->table = $this->con->prefix() . $this->workspace::WS_TABLE_NAME;

        if ($user_id) {
            $this->workspaces = [];
            $this->user_id    = $user_id;

            try {
                $this->loadPrefs($user_workspace);
            } catch (Exception $e) {
                trigger_error(__('Unable to retrieve workspaces:') . ' ' . $this->con->error(), E_USER_ERROR);
            }
        }
    }

    public function load(string $user_id, ?string $user_workspace = null): UserPreferencesInterface
    {
        return new self($this->con, $this->workspace, $user_id, $user_workspace);
    }

    /**
     * Loads preferences.
     *
     * @param   null|string     $user_workspace  The workspace
     *
     * @throws  Exception
     */
    private function loadPrefs(?string $user_workspace = null): void
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
        if ($user_workspace !== null) {
            $sql->and('pref_ws = ' . $sql->quote($user_workspace));
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
            $user_workspace = trim((string) $rs->f('pref_ws'));
            if (!$rs->isStart()) {
                // we have to go up 1 step, since workspaces construction performs a fetch()
                // at very first time
                $rs->movePrev();
            }
            $this->workspaces[$user_workspace] = $this->workspace->load($this->user_id, $user_workspace, $rs);
        } while (!$rs->isStart());
    }

    public function addWorkspace(string $user_workspace): UserWorkspaceInterface
    {
        if (!$this->exists($user_workspace)) {
            $this->workspaces[$user_workspace] = $this->workspace->load($this->user_id, $user_workspace);
        }

        return $this->workspaces[$user_workspace];
    }

    public function renWorkspace(string $old_workspace, string $new_workspace): bool
    {
        if (!$this->exists($old_workspace) || $this->exists($new_workspace)) {
            return false;
        }

        if (!preg_match($this->workspace::WS_NAME_SCHEMA, $new_workspace)) {
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
        $this->workspaces[$new_workspace] = $this->workspace->load($this->user_id, $new_workspace);

        // Remove the old workspace from the workspace array
        unset($this->workspaces[$old_workspace]);

        return true;
    }

    public function delWorkspace(string $user_workspace): bool
    {
        if (!$this->exists($user_workspace)) {
            return false;
        }

        // Remove the workspace from the workspace array
        unset($this->workspaces[$user_workspace]);

        // Delete all preferences from the workspace in the database
        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('pref_ws = ' . $sql->quote($user_workspace));

        $sql->delete();

        return true;
    }

    public function get(string $user_workspace): UserWorkspaceInterface
    {
        return $this->addWorkspace($user_workspace);
    }

    public function __get(string $user_workspace): UserWorkspaceInterface
    {
        return $this->addWorkspace($user_workspace);
    }

    public function exists(string $user_workspace): bool
    {
        return array_key_exists($user_workspace, $this->workspaces);
    }

    /**
     * Dumps workspaces.
     *
     * @return     array<string, UserWorkspaceInterface>
     */
    public function dumpWorkspaces(): array
    {
        return $this->workspaces;
    }
}
