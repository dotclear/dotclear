<?php
/**
 * @brief User workspace for preferences handler
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

class dcWorkspace
{
    protected $con;     ///< <b>connection</b> Database connection object
    protected $table;   ///< <b>string</b> Preferences table name
    protected $user_id; ///< <b>string</b> User ID

    protected $global_prefs = []; ///< <b>array</b> Global prefs array
    protected $local_prefs  = []; ///< <b>array</b> Local prefs array
    protected $prefs        = []; ///< <b>array</b> Associative prefs array
    protected $ws;                ///< <b>string</b> Current workspace

    protected const WS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';
    protected const WS_ID_SCHEMA   = '/^[a-zA-Z][a-zA-Z0-9_]+$/';

    /**
     * Object constructor. Retrieves user prefs and puts them in $prefs
     * array. Local (user) prefs have a highest priority than global prefs.
     *
     * @param      dcCore     $core     The core
     * @param      string     $user_id  The user identifier
     * @param      string     $name     The name
     * @param      mixed      $rs       The recordset
     *
     * @throws     Exception
     */
    public function __construct(&$core, $user_id, $name, $rs = null)
    {
        if (preg_match(self::WS_NAME_SCHEMA, $name)) {
            $this->ws = $name;
        } else {
            throw new Exception(sprintf(__('Invalid dcWorkspace: %s'), $name));
        }

        $this->con     = &$core->con;
        $this->table   = $core->prefix . 'pref';
        $this->user_id = &$user_id;

        try {
            $this->getPrefs($rs);
        } catch (Exception $e) {
            if (version_compare($core->getVersion('core'), '2.3', '>')) {
                trigger_error(__('Unable to retrieve prefs:') . ' ' . $this->con->error(), E_USER_ERROR);
            }
        }
    }

    private function getPrefs($rs = null)
    {
        if ($rs == null) {
            $strReq = 'SELECT user_id, pref_id, pref_value, ' .
            'pref_type, pref_label, pref_ws ' .
            'FROM ' . $this->table . ' ' .
            "WHERE (user_id = '" . $this->con->escape($this->user_id) . "' " .
            'OR user_id IS NULL) ' .
            "AND pref_ws = '" . $this->con->escape($this->ws) . "' " .
                'ORDER BY pref_id ASC ';

            try {
                $rs = $this->con->select($strReq);
            } catch (Exception $e) {
                throw $e;
            }
        }
        while ($rs->fetch()) {
            if ($rs->f('pref_ws') != $this->ws) {
                break;
            }
            $id    = trim($rs->f('pref_id'));
            $value = $rs->f('pref_value');
            $type  = $rs->f('pref_type');

            if ($type == 'array') {
                $value = @json_decode($value, true);
            } else {
                if ($type == 'float' || $type == 'double') {
                    $type = 'float';
                } elseif ($type != 'boolean' && $type != 'integer') {
                    $type = 'string';
                }
            }

            settype($value, $type);

            $array = $rs->user_id ? 'local' : 'global';

            $this->{$array . '_prefs'}[$id] = [
                'ws'     => $this->ws,
                'value'  => $value,
                'type'   => $type,
                'label'  => (string) $rs->f('pref_label'),
                'global' => $rs->user_id == '',
            ];
        }

        $this->prefs = $this->global_prefs;

        foreach ($this->local_prefs as $id => $v) {
            $this->prefs[$id] = $v;
        }

        return true;
    }

    /**
     * Returns true if a pref exist, else false
     *
     * @param      string   $id      The identifier
     * @param      boolean  $global  The global
     *
     * @return     boolean
     */
    public function prefExists($id, $global = false)
    {
        $array = $global ? 'global' : 'local';

        return isset($this->{$array . '_prefs'}[$id]);
    }

    /**
     * Returns pref value if exists.
     *
     * @param      string  $n      Pref name
     *
     * @return     mixed
     */
    public function get($n)
    {
        if (isset($this->prefs[$n]) && isset($this->prefs[$n]['value'])) {
            return $this->prefs[$n]['value'];
        }
    }

    /**
     * Returns global pref value if exists.
     *
     * @param      string  $n      Pref name
     *
     * @return     mixed
     */
    public function getGlobal($n)
    {
        if (isset($this->global_prefs[$n]) && isset($this->global_prefs[$n]['value'])) {
            return $this->global_prefs[$n]['value'];
        }
    }

    /**
     * Returns local pref value if exists.
     *
     * @param      string  $n      Pref name
     *
     * @return     mixed
     */
    public function getLocal($n)
    {
        if (isset($this->local_prefs[$n]) && isset($this->local_prefs[$n]['value'])) {
            return $this->local_prefs[$n]['value'];
        }
    }
    /**

     */
    /**
     * Magic __get method.
     *
     * @copydoc ::get
     *
     * @param      string  $n      Pref name
     *
     * @return     mixed
     */
    public function __get($n)
    {
        return $this->get($n);
    }

    /**
     * Sets a pref in $prefs property. This sets the pref for script
     * execution time only and if pref exists.
     *
     * @param      string  $n      The pref name
     * @param      mixed   $v      The pref value
     */
    public function set($n, $v)
    {
        if (isset($this->prefs[$n])) {
            $this->prefs[$n]['value'] = $v;
        }
    }

    /**
     * Magic __set method.
     *
     * @copydoc ::set
     *
     * @param      string  $n      The pref name
     * @param      mixed   $v      The pref value
     */
    public function __set($n, $v)
    {
        $this->set($n, $v);
    }

    /**
     * Creates or updates a pref.
     *
     * $type could be 'string', 'integer', 'float', 'boolean' or null. If $type is
     * null and pref exists, it will keep current pref type.
     *
     * $value_change allow you to not change pref. Useful if you need to change
     * a pref label or type and don't want to change its value.
     *
     * @param      string     $id            The pref identifier
     * @param      mixed      $value         The pref value
     * @param      string     $type          The pref type
     * @param      string     $label         The pref label
     * @param      bool       $value_change  Change pref value or not
     * @param      bool       $global        Pref is global
     *
     * @throws     Exception
     */
    public function put($id, $value, $type = null, $label = null, $value_change = true, $global = false)
    {
        if (!preg_match(self::WS_ID_SCHEMA, $id)) {
            throw new Exception(sprintf(__('%s is not a valid pref id'), $id));
        }

        # We don't want to change pref value
        if (!$value_change) {
            if (!$global && $this->prefExists($id, false)) {
                $value = $this->local_prefs[$id]['value'];
            } elseif ($this->prefExists($id, true)) {
                $value = $this->global_prefs[$id]['value'];
            }
        }

        # Pref type
        if ($type == 'double') {
            $type = 'float';
        } elseif ($type === null) {
            if (!$global && $this->prefExists($id, false)) {
                $type = $this->local_prefs[$id]['type'];
            } elseif ($this->prefExists($id, true)) {
                $type = $this->global_prefs[$id]['type'];
            } else {
                if (is_array($value)) {
                    $type = 'array';
                } else {
                    $type = 'string';
                }
            }
        } elseif ($type != 'boolean' && $type != 'integer' && $type != 'float' && $type != 'array') {
            $type = 'string';
        }

        # We don't change label
        if ($label == null) {
            if (!$global && $this->prefExists($id, false)) {
                $label = $this->local_prefs[$id]['label'];
            } elseif ($this->prefExists($id, true)) {
                $label = $this->global_prefs[$id]['label'];
            }
        }

        if ($type != 'array') {
            settype($value, $type);
        } else {
            $value = json_encode($value);
        }

        $cur             = $this->con->openCursor($this->table);
        $cur->pref_value = ($type == 'boolean') ? (string) (int) $value : (string) $value;
        $cur->pref_type  = $type;
        $cur->pref_label = $label;

        #If we are local, compare to global value
        if (!$global && $this->prefExists($id, true)) {
            $g         = $this->global_prefs[$id];
            $same_pref = ($g['ws'] == $this->ws && $g['value'] == $value && $g['type'] == $type && $g['label'] == $label);

            # Drop pref if same value as global
            if ($same_pref && $this->prefExists($id, false)) {
                $this->drop($id);
            } elseif ($same_pref) {
                return;
            }
        }

        if ($this->prefExists($id, $global) && $this->ws == $this->prefs[$id]['ws']) {
            if ($global) {
                $where = 'WHERE user_id IS NULL ';
            } else {
                $where = "WHERE user_id = '" . $this->con->escape($this->user_id) . "' ";
            }

            $cur->update($where . "AND pref_id = '" . $this->con->escape($id) . "' AND pref_ws = '" . $this->con->escape($this->ws) . "' ");
        } else {
            $cur->pref_id = $id;
            $cur->user_id = $global ? null : $this->user_id;
            $cur->pref_ws = $this->ws;

            $cur->insert();
        }
    }

    /**
     * Rename an existing pref in a Workspace
     *
     * @param      string     $oldId  The old identifier
     * @param      string     $newId  The new identifier
     *
     * @throws     Exception
     *
     * @return     bool       false is error, true if renamed
     */
    public function rename($oldId, $newId)
    {
        if (!$this->ws) {
            throw new Exception(__('No workspace specified'));
        }

        if (!array_key_exists($oldId, $this->prefs) || array_key_exists($newId, $this->prefs)) {
            return false;
        }

        if (!preg_match(self::WS_ID_SCHEMA, $newId)) {
            throw new Exception(sprintf(__('%s is not a valid pref id'), $newId));
        }

        // Rename the pref in the prefs array
        $this->prefs[$newId] = $this->prefs[$oldId];
        unset($this->prefs[$oldId]);

        // Rename the pref in the database
        $strReq = 'UPDATE ' . $this->table .
        " SET pref_id = '" . $this->con->escape($newId) . "' " .
        " WHERE pref_ws = '" . $this->con->escape($this->ws) . "' " .
        " AND pref_id = '" . $this->con->escape($oldId) . "' ";
        $this->con->execute($strReq);

        return true;
    }

    /**
     * Removes an existing pref. Workspace
     *
     * @param      string     $id            The pref identifier
     * @param      bool       $force_global  Force global pref drop
     *
     * @throws     Exception  (description)
     */
    public function drop($id, $force_global = false)
    {
        if (!$this->ws) {
            throw new Exception(__('No workspace specified'));
        }

        $strReq = 'DELETE FROM ' . $this->table . ' ';

        if (($force_global) || ($this->user_id === null)) {
            $strReq .= 'WHERE user_id IS NULL ';
            $global = true;
        } else {
            $strReq .= "WHERE user_id = '" . $this->con->escape($this->user_id) . "' ";
            $global = false;
        }

        $strReq .= "AND pref_id = '" . $this->con->escape($id) . "' ";
        $strReq .= "AND pref_ws = '" . $this->con->escape($this->ws) . "' ";

        $this->con->execute($strReq);

        if ($this->prefExists($id, $global)) {
            $array = $global ? 'global' : 'local';
            unset($this->{$array . '_prefs'}[$id]);
        }

        $this->prefs = $this->global_prefs;
        foreach ($this->local_prefs as $id => $v) {
            $this->prefs[$id] = $v;
        }
    }

    /**
     * Removes every existing specific pref. in a workspace
     *
     * @param      string     $id      Pref ID
     * @param      boolean    $global  Remove global pref too
     */
    public function dropEvery($id, $global = false)
    {
        if (!$this->ws) {
            throw new Exception(__('No workspace specified'));
        }

        $strReq = 'DELETE FROM ' . $this->table . ' WHERE ';
        if (!$global) {
            $strReq .= 'user_id IS NOT NULL AND ';
        }
        $strReq .= "pref_id = '" . $this->con->escape($id) . "' AND pref_ws = '" . $this->con->escape($this->ws) . "' ";

        $this->con->execute($strReq);
    }

    /**
     * Removes all existing pref. in a Workspace
     *
     * @param      bool       $force_global  Remove global prefs too
     *
     * @throws     Exception
     */
    public function dropAll($force_global = false)
    {
        if (!$this->ws) {
            throw new Exception(__('No workspace specified'));
        }

        $strReq = 'DELETE FROM ' . $this->table . ' ';

        if (($force_global) || ($this->user_id === null)) {
            $strReq .= 'WHERE user_id IS NULL ';
            $global = true;
        } else {
            $strReq .= "WHERE user_id = '" . $this->con->escape($this->user_id) . "' ";
            $global = false;
        }

        $strReq .= "AND pref_ws = '" . $this->con->escape($this->ws) . "' ";

        $this->con->execute($strReq);

        $array = $global ? 'global' : 'local';
        unset($this->{$array . '_prefs'});
        $this->{$array . '_prefs'} = [];

        $array       = $global ? 'local' : 'global';
        $this->prefs = $this->{$array . '_prefs'};
    }

    /**
     * Dumps a workspace.
     *
     * @return     string
     */
    public function dumpWorkspace()
    {
        return $this->ws;
    }

    /**
     * Dumps preferences.
     *
     * @return     array
     */
    public function dumpPrefs()
    {
        return $this->prefs;
    }

    /**
     * Dumps local preferences.
     *
     * @return     array
     */
    public function dumpLocalPrefs()
    {
        return $this->local_prefs;
    }

    /**
     * Dumps global preferences.
     *
     * @return     array
     */
    public function dumpGlobalPrefs()
    {
        return $this->global_prefs;
    }
}
