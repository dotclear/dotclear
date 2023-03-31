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

use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;

class dcWorkspace
{
    // Constants

    /**
     * Workspace (user preferences) table name
     *
     * @var        string
     */
    public const WS_TABLE_NAME = 'pref';

    /**
     * Regexp workspace name schema
     *
     * @var        string
     */
    public const WS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';

    /**
     * Regexp workspace ID schema
     *
     * @var        string
     */
    protected const WS_ID_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9_]+$/';

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
     * Global preferences
     *
     * @var array
     */
    protected $global_prefs = [];

    /**
     * Local preferences
     *
     * @var array
     */
    protected $local_prefs = [];

    /**
     * User preferences
     *
     * @var array
     */
    protected $prefs = [];

    /**
     * Current workspace name
     *
     * @var string
     */
    protected $workspace;

    /**
     * Object constructor. Retrieves user prefs and puts them in $prefs
     * array. Local (user) prefs have a highest priority than global prefs.
     *
     * @param      string        $user_id  The user identifier
     * @param      string        $name     The workspace name
     * @param      dcRecord      $rs       The recordset
     *
     * @throws     Exception
     */
    public function __construct(string $user_id, string $name, ?dcRecord $rs = null)
    {
        if (preg_match(self::WS_NAME_SCHEMA, $name)) {
            $this->workspace = $name;
        } else {
            throw new Exception(sprintf(__('Invalid dcWorkspace: %s'), $name));
        }

        $this->con     = dcCore::app()->con;
        $this->table   = dcCore::app()->prefix . self::WS_TABLE_NAME;
        $this->user_id = $user_id;

        try {
            $this->getPrefs($rs);
        } catch (Exception $e) {
            trigger_error(__('Unable to retrieve prefs:') . ' ' . $this->con->error(), E_USER_ERROR);
        }
    }

    /**
     * Gets the preferences.
     *
     * @param      dcRecord      $rs       The recordset
     */
    private function getPrefs(?dcRecord $rs = null): void
    {
        if ($rs === null) {
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
                ->and('pref_ws = ' . $sql->quote($this->workspace))
                ->order('pref_id ASC');

            try {
                $rs = $sql->select();
            } catch (Exception $e) {
                throw $e;
            }
        }
        while ($rs->fetch()) {
            if ($rs->f('pref_ws') !== $this->workspace) {
                break;
            }
            $name  = trim((string) $rs->f('pref_id'));
            $value = $rs->f('pref_value');
            $type  = $rs->f('pref_type');

            if ($type === 'array') {
                $value = @json_decode($value, true);
            } else {
                if ($type === 'float' || $type === 'double') {
                    $type = 'float';
                } elseif ($type !== 'boolean' && $type !== 'integer') {
                    $type = 'string';
                }
            }

            settype($value, $type);

            $array = ($rs->user_id ? 'local' : 'global') . '_prefs';

            $this->{$array}[$name] = [
                'ws'     => $this->workspace,
                'value'  => $value,
                'type'   => $type,
                'label'  => (string) $rs->f('pref_label'),
                'global' => (!$rs->user_id),
            ];
        }

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    /**
     * Returns true if a pref exist, else false
     *
     * @param      string   $name    The identifier
     * @param      bool     $global  The global
     *
     * @return     bool
     */
    public function prefExists(string $name, bool $global = false): bool
    {
        $array = ($global ? 'global' : 'local') . '_prefs';

        return isset($this->{$array}[$name]);
    }

    /**
     * Returns pref value if exists.
     *
     * @param      string  $name      Pref name
     *
     * @return     mixed
     */
    public function get(string $name)
    {
        if (isset($this->prefs[$name]) && isset($this->prefs[$name]['value'])) {
            return $this->prefs[$name]['value'];
        }
    }

    /**
     * Returns global pref value if exists.
     *
     * @param      string  $name      Pref name
     *
     * @return     mixed
     */
    public function getGlobal(string $name)
    {
        if (isset($this->global_prefs[$name]) && isset($this->global_prefs[$name]['value'])) {
            return $this->global_prefs[$name]['value'];
        }
    }

    /**
     * Returns local pref value if exists.
     *
     * @param      string  $name      Pref name
     *
     * @return     mixed
     */
    public function getLocal(string $name)
    {
        if (isset($this->local_prefs[$name]) && isset($this->local_prefs[$name]['value'])) {
            return $this->local_prefs[$name]['value'];
        }
    }
    /**

     */
    /**
     * Magic __get method.
     *
     * @param      string  $name      Pref name
     *
     * @return     mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Sets a pref in $prefs property. This sets the pref for script
     * execution time only and if pref exists.
     *
     * @param      string  $name      The pref name
     * @param      mixed   $value     The pref value
     */
    public function set(string $name, $value)
    {
        if (isset($this->prefs[$name])) {
            $this->prefs[$name]['value'] = $value;
        }
    }

    /**
     * Magic __set method.
     *
     * @param      string  $name      The pref name
     * @param      mixed   $value     The pref value
     */
    public function __set(string $name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Creates or updates a pref.
     *
     * $type could be 'string', 'integer', 'float', 'boolean' or null. If $type is
     * null and pref exists, it will keep current pref type.
     *
     * $ignore_value allow you to not change pref. Useful if you need to change
     * a pref label or type and don't want to change its value.
     *
     * @param      string     $name          The pref identifier
     * @param      mixed      $value         The pref value
     * @param      string     $type          The pref type
     * @param      string     $label         The pref label
     * @param      bool       $ignore_value  Change pref value or not
     * @param      bool       $global        Pref is global
     *
     * @throws     Exception
     */
    public function put(string $name, $value, ?string $type = null, ?string $label = null, bool $ignore_value = true, bool $global = false): void
    {
        if (!preg_match(self::WS_ID_SCHEMA, $name)) {
            throw new Exception(sprintf(__('%s is not a valid pref id'), $name));
        }

        // We don't want to change pref value
        if (!$ignore_value) {
            if (!$global && $this->prefExists($name, false)) {
                $value = $this->local_prefs[$name]['value'];
            } elseif ($this->prefExists($name, true)) {
                $value = $this->global_prefs[$name]['value'];
            }
        }

        // Pref type
        if ($type === 'double') {
            $type = 'float';
        } elseif ($type === null) {
            if (!$global && $this->prefExists($name, false)) {
                $type = $this->local_prefs[$name]['type'];
            } elseif ($this->prefExists($name, true)) {
                $type = $this->global_prefs[$name]['type'];
            } else {
                if (is_array($value)) {
                    $type = 'array';
                } else {
                    $type = 'string';
                }
            }
        } elseif ($type !== 'boolean' && $type !== 'integer' && $type !== 'float' && $type !== 'array') {
            $type = 'string';
        }

        // We don't change label
        if (!$label) {
            if (!$global && $this->prefExists($name, false)) {
                $label = $this->local_prefs[$name]['label'];
            } elseif ($this->prefExists($name, true)) {
                $label = $this->global_prefs[$name]['label'];
            }
        }

        if ($type !== 'array') {
            settype($value, $type);
        } else {
            $value = json_encode($value);
        }

        $cur = $this->con->openCursor($this->table);

        $cur->pref_value = ($type === 'boolean') ? (string) (int) $value : (string) $value;
        $cur->pref_type  = $type;
        $cur->pref_label = $label;

        #If we are local, compare to global value
        if (!$global && $this->prefExists($name, true)) {
            $g         = $this->global_prefs[$name];
            $same_pref = ($g['ws'] === $this->workspace && $g['value'] === $value && $g['type'] === $type && $g['label'] === $label);

            # Drop pref if same value as global
            if ($same_pref && $this->prefExists($name, false)) {
                $this->drop($name);
            } elseif ($same_pref) {
                return;
            }
        }

        if ($this->prefExists($name, $global) && $this->workspace === $this->prefs[$name]['ws']) {
            $sql = new UpdateStatement();

            if ($global) {
                $sql->where('user_id IS NULL');
            } else {
                $sql->where('user_id = ' . $sql->quote($this->user_id));
            }
            $sql
                ->and('pref_id = ' . $sql->quote($name))
                ->and('pref_ws = ' . $sql->quote($this->workspace));

            $sql->update($cur);
        } else {
            $cur->pref_id = $name;
            $cur->user_id = $global ? null : $this->user_id;
            $cur->pref_ws = $this->workspace;

            $cur->insert();
        }
    }

    /**
     * Rename an existing pref in a Workspace
     *
     * @param      string     $old_name  The old identifier
     * @param      string     $new_name  The new identifier
     *
     * @throws     Exception
     *
     * @return     bool       false is error, true if renamed
     */
    public function rename(string $old_name, string $new_name): bool
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        if (!array_key_exists($old_name, $this->prefs) || array_key_exists($new_name, $this->prefs)) {
            return false;
        }

        if (!preg_match(self::WS_ID_SCHEMA, $new_name)) {
            throw new Exception(sprintf(__('%s is not a valid pref id'), $new_name));
        }

        // Rename the pref in the prefs array
        $this->prefs[$new_name] = $this->prefs[$old_name];
        unset($this->prefs[$old_name]);

        // Rename the pref in the database
        $sql = new UpdateStatement();
        $sql
            ->ref($this->table)
            ->set('pref_id = ' . $sql->quote($new_name))
            ->where('pref_ws = ' . $sql->quote($this->workspace))
            ->and('pref_id = ' . $sql->quote($old_name));

        $sql->update();

        // Reload preferences from database
        $this->global_prefs = $this->local_prefs = $this->prefs = [];
        $this->getPrefs();

        return true;
    }

    /**
     * Removes an existing pref. Workspace
     *
     * @param      string     $name          The pref identifier
     * @param      bool       $force_global  Force global pref drop
     *
     * @throws     Exception
     */
    public function drop(string $name, bool $force_global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (($force_global) || ($this->user_id === null)) {
            $sql->where('user_id IS NULL');
            $global = true;
        } else {
            $sql->where('user_id = ' . $sql->quote($this->user_id));
            $global = false;
        }

        $sql
            ->and('pref_id = ' . $sql->quote($name))
            ->and('pref_ws = ' . $sql->quote($this->workspace));

        $sql->delete();

        if ($this->prefExists($name, $global)) {
            $array = ($global ? 'global' : 'local') . '_prefs';
            unset($this->{$array}[$name]);
        }

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    /**
     * Removes every existing specific pref. in a workspace
     *
     * @param      string     $name    Pref ID
     * @param      bool       $global  Remove global pref too
     */
    public function dropEvery(string $name, bool $global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (!$global) {
            $sql->where($sql->isNotNull('user_id'));
        }
        $sql
            ->and('pref_id = ' . $sql->quote($name))
            ->and('pref_ws = ' . $sql->quote($this->workspace));

        $sql->delete();

        if ($this->prefExists($name, false)) {
            unset($this->local_prefs[$name]);
        }
        if ($global && $this->prefExists($name, true)) {
            unset($this->global_prefs[$name]);
        }

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    /**
     * Removes all existing pref. in a Workspace
     *
     * @param      bool       $force_global  Remove global prefs too
     *
     * @throws     Exception
     */
    public function dropAll(bool $force_global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (($force_global) || ($this->user_id === null)) {
            $sql->where('user_id IS NULL');
            $global = true;
        } else {
            $sql->where('user_id = ' . $sql->quote($this->user_id));
            $global = false;
        }

        $sql->and('pref_ws = ' . $sql->quote($this->workspace));

        $sql->delete();

        // Reset global/local preferencess
        $array          = ($global ? 'global' : 'local') . '_prefs';
        $this->{$array} = [];

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    /**
     * Dumps a workspace.
     *
     * @return     string
     */
    public function dumpWorkspace(): string
    {
        return $this->workspace;
    }

    /**
     * Dumps preferences.
     *
     * @return     array
     */
    public function dumpPrefs(): array
    {
        return $this->prefs;
    }

    /**
     * Dumps local preferences.
     *
     * @return     array
     */
    public function dumpLocalPrefs(): array
    {
        return $this->local_prefs;
    }

    /**
     * Dumps global preferences.
     *
     * @return     array
     */
    public function dumpGlobalPrefs(): array
    {
        return $this->global_prefs;
    }
}
