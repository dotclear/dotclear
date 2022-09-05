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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcPrefs
{
    /**
     * @deprecated since 2.23
     */
    protected $core;    ///< <b>core</b> Dotclear core object

    protected $con;     ///< <b>connection</b> Database connection object
    protected $table;   ///< <b>string</b> Prefs table name
    protected $user_id; ///< <b>string</b> User ID

    protected $workspaces = []; ///< <b>array</b> Associative workspaces array

    protected $ws; ///< <b>string</b> Current workspace

    protected const WS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';

    /**
     * Object constructor. Retrieves user prefs and puts them in $workspaces
     * array. Local (user) prefs have a highest priority than global prefs.
     *
     * @param      dcCore      $core      The core
     * @param      string      $user_id   The user identifier
     * @param      string|null $workspace The workspace to load
     */
    public function __construct(dcCore $core, $user_id, $workspace = null)
    {
        $this->core    = dcCore::app();
        $this->con     = dcCore::app()->con;
        $this->table   = dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME;
        $this->user_id = $user_id;

        try {
            $this->loadPrefs($workspace);
        } catch (Exception $e) {
            if (version_compare(dcCore::app()->getVersion('core'), '2.3', '>')) {
                trigger_error(__('Unable to retrieve workspaces:') . ' ' . $this->con->error(), E_USER_ERROR);
            }
        }
    }

    /**
    Retrieves all (or only one) workspaces (and their prefs) from database, with one query.
     */
    private function loadPrefs($workspace = null)
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
            $ws = trim((string) $rs->f('pref_ws'));
            if (!$rs->isStart()) {
                // we have to go up 1 step, since workspaces construction performs a fetch()
                // at very first time
                $rs->movePrev();
            }
            $this->workspaces[$ws] = new dcWorkspace(dcCore::app(), $this->user_id, $ws, $rs);
        } while (!$rs->isStart());
    }

    /**
     * Create a new workspace. If the workspace already exists, return it without modification.
     *
     * @param      string  $ws     Workspace name
     *
     * @return     dcWorkspace
     */
    public function addWorkspace($ws)
    {
        if (!$this->exists($ws)) {
            $this->workspaces[$ws] = new dcWorkspace(dcCore::app(), $this->user_id, $ws);
        }

        return $this->workspaces[$ws];
    }

    /**
     * Rename a workspace.
     *
     * @param      string     $oldWs  The old workspace name
     * @param      string     $newWs  The new workspace name
     *
     * @throws     Exception  (description)
     *
     * @return     bool
     */
    public function renWorkspace($oldWs, $newWs)
    {
        if (!$this->exists($oldWs) || $this->exists($newWs)) {
            return false;
        }

        if (!preg_match(self::WS_NAME_SCHEMA, $newWs)) {
            throw new Exception(sprintf(__('Invalid dcWorkspace: %s'), $newWs));
        }

        // Rename the workspace in the workspace array
        $this->workspaces[$newWs] = $this->workspaces[$oldWs];
        unset($this->workspaces[$oldWs]);

        // Rename the workspace in the database
        $sql = new dcUpdateStatement();
        $sql
            ->ref($this->table)
            ->set('pref_ws = ' . $sql->quote($newWs))
            ->where('pref_ws = ' . $sql->quote($oldWs));
        $sql->update();

        return true;
    }

    /**
     * Delete a whole workspace with all preferences pertaining to it.
     *
     * @param      string  $ws     Workspace name
     *
     * @return     bool
     */
    public function delWorkspace($ws)
    {
        if (!$this->exists($ws)) {
            return false;
        }

        // Remove the workspace from the workspace array
        unset($this->workspaces[$ws]);

        // Delete all preferences from the workspace in the database
        $sql = new dcDeleteStatement();
        $sql
            ->from($this->table)
            ->where('pref_ws = ' . $sql->quote($ws));

        $sql->delete();

        return true;
    }

    /**
     * Returns full workspace with all prefs pertaining to it.
     *
     * @param      string  $ws     Workspace name
     *
     * @return     mixed
     */
    public function get($ws)
    {
        if ($this->exists($ws)) {
            return $this->workspaces[$ws];
        }
    }

    /**
     * Magic __get method.
     *
     * @copydoc ::get
     *
     * @param      string  $n     Workspace name
     *
     * @return     mixed
     */
    public function __get($n)
    {
        return $this->get($n);
    }

    /**
     * Check if a workspace exists
     *
     * @param      string  $ws     Workspace name
     *
     * @return     boolean
     */
    public function exists($ws)
    {
        return array_key_exists($ws, $this->workspaces);
    }

    /**
     * Dumps workspaces.
     *
     * @return     array
     */
    public function dumpWorkspaces()
    {
        return $this->workspaces;
    }
}
