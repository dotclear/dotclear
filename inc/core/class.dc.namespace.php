<?php
/**
 * @brief Blog namespace for settings handler
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcNamespace
{
    protected $con;     ///< <b>connection</b> Database connection object
    protected $table;   ///< <b>string</b> Settings table name
    protected $blog_id; ///< <b>string</b> Blog ID

    protected $global_settings = []; ///< <b>array</b> Global settings array
    protected $local_settings  = []; ///< <b>array</b> Local settings array
    protected $settings        = []; ///< <b>array</b> Associative settings array
    protected $ns;                   ///< <b>string</b> Current namespace

    /**
    Object constructor. Retrieves blog settings and puts them in $settings
    array. Local (blog) settings have a highest priority than global settings.

    @param    name        <b>string</b>        ID for this namespace
     */
    public function __construct(&$core, $blog_id, $name, $rs = null)
    {
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]+$/', $name)) {
            $this->ns = $name;
        } else {
            throw new Exception(sprintf(__('Invalid setting dcNamespace: %s'), $name));
        }

        $this->con     = &$core->con;
        $this->table   = $core->prefix . 'setting';
        $this->blog_id = &$blog_id;

        $this->getSettings($rs);
    }

    private function getSettings($rs = null)
    {
        if ($rs == null) {
            $strReq = 'SELECT blog_id, setting_id, setting_value, ' .
            'setting_type, setting_label, setting_ns ' .
            'FROM ' . $this->table . ' ' .
            "WHERE (blog_id = '" . $this->con->escape($this->blog_id) . "' " .
            'OR blog_id IS NULL) ' .
            "AND setting_ns = '" . $this->con->escape($this->ns) . "' " .
                'ORDER BY setting_id DESC ';

            try {
                $rs = $this->con->select($strReq);
            } catch (Exception $e) {
                trigger_error(__('Unable to retrieve settings:') . ' ' . $this->con->error(), E_USER_ERROR);
            }
        }
        while ($rs->fetch()) {
            if ($rs->f('setting_ns') != $this->ns) {
                break;
            }
            $id    = trim($rs->f('setting_id'));
            $value = $rs->f('setting_value');
            $type  = $rs->f('setting_type');

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

            $array = $rs->blog_id ? 'local' : 'global';

            $this->{$array . '_settings'}[$id] = [
                'ns'     => $this->ns,
                'value'  => $value,
                'type'   => $type,
                'label'  => (string) $rs->f('setting_label'),
                'global' => $rs->blog_id == ''
            ];
        }

        $this->settings = $this->global_settings;

        foreach ($this->local_settings as $id => $v) {
            $this->settings[$id] = $v;
        }

        return true;
    }

    /**
     * Returns true if a setting exist, else false
     *
     * @param      string   $id      The identifier
     * @param      boolean  $global  The global
     *
     * @return     boolean
     */
    public function settingExists($id, $global = false)
    {
        $array = $global ? 'global' : 'local';
        return isset($this->{$array . '_settings'}[$id]);
    }

    /**
    Returns setting value if exists.

    @param    n        <b>string</b>        Setting name
    @return    <b>mixed</b>
     */
    public function get($n)
    {
        if (isset($this->settings[$n]['value'])) {
            return $this->settings[$n]['value'];
        }

        return;
    }

    /**
    Returns global setting value if exists.

    @param    n        <b>string</b>        setting name
    @return    <b>mixed</b>
     */
    public function getGlobal($n)
    {
        if (isset($this->global_settings[$n]['value'])) {
            return $this->global_settings[$n]['value'];
        }

        return;
    }

    /**
    Returns local setting value if exists.

    @param    n        <b>string</b>        setting name
    @return    <b>mixed</b>
     */
    public function getLocal($n)
    {
        if (isset($this->local_settings[$n]['value'])) {
            return $this->local_settings[$n]['value'];
        }

        return;
    }

    /**
    Magic __get method.
    @copydoc ::get
     */
    public function __get($n)
    {
        return $this->get($n);
    }

    /**
    Sets a setting in $settings property. This sets the setting for script
    execution time only and if setting exists.

    @param    n        <b>string</b>        Setting name
    @param    v        <b>mixed</b>        Setting value
     */
    public function set($n, $v)
    {
        if (isset($this->settings[$n])) {
            $this->settings[$n]['value'] = $v;
        }
    }

    /**
    Magic __set method.
    @copydoc ::set
     */
    public function __set($n, $v)
    {
        $this->set($n, $v);
    }

    /**
    Creates or updates a setting.

    $type could be 'string', 'integer', 'float', 'boolean' or null. If $type is
    null and setting exists, it will keep current setting type.

    $value_change allow you to not change setting. Useful if you need to change
    a setting label or type and don't want to change its value.

    @param    id            <b>string</b>        Setting ID
    @param    value        <b>mixed</b>        Setting value
    @param    type            <b>string</b>        Setting type
    @param    label        <b>string</b>        Setting label
    @param    value_change    <b>boolean</b>        Change setting value or not
    @param    global        <b>boolean</b>        Setting is global
     */
    public function put($id, $value, $type = null, $label = null, $value_change = true, $global = false)
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]+$/', $id)) {
            throw new Exception(sprintf(__('%s is not a valid setting id'), $id));
        }

        # We don't want to change setting value
        if (!$value_change) {
            if (!$global && $this->settingExists($id, false)) {
                $value = $this->local_settings[$id]['value'];
            } elseif ($this->settingExists($id, true)) {
                $value = $this->global_settings[$id]['value'];
            }
        }

        # Setting type
        if ($type == 'double') {
            $type = 'float';
        } elseif ($type === null) {
            if (!$global && $this->settingExists($id, false)) {
                $type = $this->local_settings[$id]['type'];
            } elseif ($this->settingExists($id, true)) {
                $type = $this->global_settings[$id]['type'];
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
            if (!$global && $this->settingExists($id, false)) {
                $label = $this->local_settings[$id]['label'];
            } elseif ($this->settingExists($id, true)) {
                $label = $this->global_settings[$id]['label'];
            }
        }

        if ($type != 'array') {
            settype($value, $type);
        } else {
            $value = json_encode($value);
        }

        $cur                = $this->con->openCursor($this->table);
        $cur->setting_value = ($type == 'boolean') ? (string) (integer) $value : (string) $value;
        $cur->setting_type  = $type;
        $cur->setting_label = $label;

        #If we are local, compare to global value
        if (!$global && $this->settingExists($id, true)) {
            $g            = $this->global_settings[$id];
            $same_setting = $g['ns'] == $this->ns && $g['value'] == $value
                && $g['type'] == $type && $g['label'] == $label;

            # Drop setting if same value as global
            if ($same_setting && $this->settingExists($id, false)) {
                $this->drop($id);
            } elseif ($same_setting) {
                return;
            }
        }

        if ($this->settingExists($id, $global) && $this->ns == $this->settings[$id]['ns']) {
            if ($global) {
                $where = 'WHERE blog_id IS NULL ';
            } else {
                $where = "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' ";
            }

            $cur->update($where . "AND setting_id = '" . $this->con->escape($id) . "' AND setting_ns = '" . $this->con->escape($this->ns) . "' ");
        } else {
            $cur->setting_id = $id;
            $cur->blog_id    = $global ? null : $this->blog_id;
            $cur->setting_ns = $this->ns;

            $cur->insert();
        }
    }

    /**
    Rename an existing setting in a Namespace

    @param     $oldId     <b>string</b>     Current setting name
    @param     $newId     <b>string</b>     New setting name
    @return     <b>boolean</b>
     */
    public function rename($oldId, $newId)
    {
        if (!$this->ns) {
            throw new Exception(__('No namespace specified'));
        }

        if (!array_key_exists($oldId, $this->settings) || array_key_exists($newId, $this->settings)) {
            return false;
        }

        // Rename the setting in the settings array
        $this->settings[$newId] = $this->settings[$oldId];
        unset($this->settings[$oldId]);

        // Rename the setting in the database
        $strReq = 'UPDATE ' . $this->table .
        " SET setting_id = '" . $this->con->escape($newId) . "' " .
        " WHERE setting_ns = '" . $this->con->escape($this->ns) . "' " .
        " AND setting_id = '" . $this->con->escape($oldId) . "' ";
        $this->con->execute($strReq);
        return true;
    }

    /**
    Removes an existing setting in a Namespace

    @param    id        <b>string</b>        Setting ID
     */
    public function drop($id)
    {
        if (!$this->ns) {
            throw new Exception(__('No namespace specified'));
        }

        $strReq = 'DELETE FROM ' . $this->table . ' ';

        if ($this->blog_id === null) {
            $strReq .= 'WHERE blog_id IS NULL ';
        } else {
            $strReq .= "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' ";
        }

        $strReq .= "AND setting_id = '" . $this->con->escape($id) . "' ";
        $strReq .= "AND setting_ns = '" . $this->con->escape($this->ns) . "' ";

        $this->con->execute($strReq);
    }

    /**
     * Removes every existing specific setting in a namespace
     *
     * @param      string     $id      Setting ID
     * @param      boolean    $global  Remove global setting too
     */
    public function dropEvery($id, $global = false)
    {
        if (!$this->ns) {
            throw new Exception(__('No namespace specified'));
        }

        $strReq = 'DELETE FROM ' . $this->table . ' ';
        if (!$global) {
            $strReq .= 'WHERE blog_id IS NOT NULL ';
        }
        $strReq .= "AND setting_id = '" . $this->con->escape($id) . "' ";
        $strReq .= "AND setting_ns = '" . $this->con->escape($this->ns) . "' ";

        $this->con->execute($strReq);
    }

    /**
    Removes all existing settings in a Namespace

    @param    force_global    <b>boolean</b>    Force global pref drop
     */
    public function dropAll($force_global = false)
    {
        if (!$this->ns) {
            throw new Exception(__('No namespace specified'));
        }

        $strReq = 'DELETE FROM ' . $this->table . ' ';

        if (($force_global) || ($this->blog_id === null)) {
            $strReq .= 'WHERE blog_id IS NULL ';
            $global = true;
        } else {
            $strReq .= "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' ";
            $global = false;
        }

        $strReq .= "AND setting_ns = '" . $this->con->escape($this->ns) . "' ";

        $this->con->execute($strReq);

        $array = $global ? 'global' : 'local';
        unset($this->{$array . '_settings'});
        $this->{$array . '_settings'} = [];

        $array          = $global ? 'local' : 'global';
        $this->settings = $this->{$array . '_settings'};
    }

    /**
    Returns $ns property content.

    @return    <b>string</b>
     */
    public function dumpNamespace()
    {
        return $this->ns;
    }

    /**
    Returns $settings property content.

    @return    <b>array</b>
     */
    public function dumpSettings()
    {
        return $this->settings;
    }

    /**
    Returns $local_settings property content.

    @return    <b>array</b>
     */
    public function dumpLocalSettings()
    {
        return $this->local_settings;
    }

    /**
    Returns $global_settings property content.

    @return    <b>array</b>
     */
    public function dumpGlobalSettings()
    {
        return $this->global_settings;
    }

}
