<?php
/**
 * @brief User prefs handler
 *
 * dcPrefs provides user preferences management. This class instance exists as
 * dcAuth $prefs property. You should create a new prefs instance when
 * updating another user prefs.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcPrefs
{
    // Properties

    /**
     * Database connection object
     *
     * @var object
     */
    protected $con;

    /**
     * Preferences table name
     *
     * @var string
     */
    protected $table;

    /**
     * User ID
     *
     * @var string
     */
    protected $user_id;

    /**
     * Associative workspaces array
     *
     * @var        array
     */
    protected $workspaces = [];

    /**
     * Object constructor. Retrieves user prefs and puts them in $workspaces
     * array. Local (user) prefs have a highest priority than global prefs.
     *
     * @param      string      $user_id   The user identifier
     * @param      string      $workspace The workspace to load
     */
    public function __construct(string $user_id, ?string $workspace = null)
    {
        $this->con     = dcCore::app()->con;
        $this->table   = dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME;
        $this->user_id = $user_id;

        try {
            $this->loadPrefs($workspace);
        } catch (Exception $e) {
            trigger_error(__('Unable to retrieve workspaces:') . ' ' . $this->con->error(), E_USER_ERROR);
        }
    }

    /**
     * Loads preferences.
     *
     * @param      null|string  $workspace  The workspace
     */
    private function loadPrefs(?string $workspace = null): void
    {
        $sql = new dcSelectStatement();
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
            $this->workspaces[$workspace] = new dcWorkspace($this->user_id, $workspace, $rs);
        } while (!$rs->isStart());
    }

    /**
     * Create a new workspace. If the workspace already exists, return it without modification.
     *
     * @param      string  $workspace     Workspace name
     *
     * @return     dcWorkspace
     */
    public function addWorkspace(string $workspace): dcWorkspace
    {
        if (!$this->exists($workspace)) {
            $this->workspaces[$workspace] = new dcWorkspace($this->user_id, $workspace);
        }

        return $this->workspaces[$workspace];
    }

    /**
     * Rename a workspace.
     *
     * @param      string     $old_workspace  The old workspace name
     * @param      string     $new_workspace  The new workspace name
     *
     * @throws     Exception  (description)
     *
     * @return     bool
     */
    public function renWorkspace(string $old_workspace, string $new_workspace): bool
    {
        if (!$this->exists($old_workspace) || $this->exists($new_workspace)) {
            return false;
        }

        if (!preg_match(dcWorkspace::WS_NAME_SCHEMA, $new_workspace)) {
            throw new Exception(sprintf(__('Invalid dcWorkspace: %s'), $new_workspace));
        }

        // Rename the workspace in the workspace array
        $this->workspaces[$new_workspace] = $this->workspaces[$old_workspace];
        unset($this->workspaces[$old_workspace]);

        // Rename the workspace in the database
        $sql = new dcUpdateStatement();
        $sql
            ->ref($this->table)
            ->set('pref_ws = ' . $sql->quote($new_workspace))
            ->where('pref_ws = ' . $sql->quote($old_workspace));
        $sql->update();

        return true;
    }

    /**
     * Delete a whole workspace with all preferences pertaining to it.
     *
     * @param      string  $workspace     Workspace name
     *
     * @return     bool
     */
    public function delWorkspace(string $workspace): bool
    {
        if (!$this->exists($workspace)) {
            return false;
        }

        // Remove the workspace from the workspace array
        unset($this->workspaces[$workspace]);

        // Delete all preferences from the workspace in the database
        $sql = new dcDeleteStatement();
        $sql
            ->from($this->table)
            ->where('pref_ws = ' . $sql->quote($workspace));

        $sql->delete();

        return true;
    }

    /**
     * Returns full workspace with all prefs pertaining to it.
     *
     * @param      string  $workspace     Workspace name
     *
     * @return     dcWorkspace
     */
    public function get(string $workspace): dcWorkspace
    {
        return $this->addWorkspace($workspace);
    }

    /**
     * Magic __get method.
     *
     * @copydoc ::get
     *
     * @param      string  $workspace     Workspace name
     *
     * @return     dcWorkspace
     */
    public function __get(string $workspace): dcWorkspace
    {
        return $this->get($workspace);
    }

    /**
     * Check if a workspace exists
     *
     * @param      string  $workspace     Workspace name
     *
     * @return     boolean
     */
    public function exists(string $workspace): bool
    {
        return array_key_exists($workspace, $this->workspaces);
    }

    /**
     * Dumps workspaces.
     *
     * @return     array
     */
    public function dumpWorkspaces(): array
    {
        return $this->workspaces;
    }
}
