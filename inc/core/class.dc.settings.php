<?php
/**
 * @brief Blog settings handler
 *
 * dcSettings provides blog settings management. This class instance exists as
 * dcBlog $settings property. You should create a new settings instance when
 * updating another blog settings.
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

class dcSettings
{
    protected $core;    ///< <b>core</b> Dotclear core object
    protected $con;     ///< <b>connection</b> Database connection object
    protected $table;   ///< <b>string</b> Settings table name
    protected $blog_id; ///< <b>string</b> Blog ID

    protected $namespaces = []; ///< <b>array</b> Associative namespaces array

    protected $ns; ///< <b>string</b> Current namespace

    protected const NS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';

    /**
     * Object constructor. Retrieves blog settings and puts them in $namespaces
     * array. Local (blog) settings have a highest priority than global settings.
     *
     * @param      dcCore   $core     The core
     * @param      mixed    $blog_id  The blog identifier
     */
    public function __construct(dcCore $core, $blog_id)
    {
        $this->core    = &$core;
        $this->con     = &$core->con;
        $this->table   = $core->prefix . 'setting';
        $this->blog_id = &$blog_id;
        $this->loadSettings();
    }

    /**
    Retrieves all namespaces (and their settings) from database, with one query.
     */
    private function loadSettings()
    {
        $strReq = 'SELECT blog_id, setting_id, setting_value, ' .
        'setting_type, setting_label, setting_ns ' .
        'FROM ' . $this->table . ' ' .
        "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' " .
            'OR blog_id IS NULL ' .
            'ORDER BY setting_ns ASC, setting_id DESC';

        $rs = null;

        try {
            $rs = $this->con->select($strReq);
        } catch (Exception $e) {
            trigger_error(__('Unable to retrieve namespaces:') . ' ' . $this->con->error(), E_USER_ERROR);
        }

        /* Prevent empty tables (install phase, for instance) */
        if ($rs->isEmpty()) {
            return;
        }

        do {
            $ns = trim($rs->f('setting_ns'));
            if (!$rs->isStart()) {
                // we have to go up 1 step, since namespaces construction performs a fetch()
                // at very first time
                $rs->movePrev();
            }
            $this->namespaces[$ns] = new dcNamespace($this->core, $this->blog_id, $ns, $rs);
        } while (!$rs->isStart());
    }

    /**
     * Create a new namespace. If the namespace already exists, return it without modification.
     *
     * @param      string  $ns     Namespace name
     *
     * @return     dcNamespace
     */
    public function addNamespace($ns)
    {
        if (!$this->exists($ns)) {
            $this->namespaces[$ns] = new dcNamespace($this->core, $this->blog_id, $ns);
        }

        return $this->namespaces[$ns];
    }

    /**
     * Rename a namespace.
     *
     * @param      string     $oldNs  The old ns
     * @param      string     $newNs  The new ns
     *
     * @throws     Exception
     *
     * @return     bool      return true if no error, else false
     */
    public function renNamespace($oldNs, $newNs)
    {
        if (!$this->exists($oldNs) || $this->exists($newNs)) {
            return false;
        }

        if (!preg_match(self::NS_NAME_SCHEMA, $newNs)) {
            throw new Exception(sprintf(__('Invalid setting namespace: %s'), $newNs));
        }

        // Rename the namespace in the namespace array
        $this->namespaces[$newNs] = $this->namespaces[$oldNs];
        unset($this->namespaces[$oldNs]);

        // Rename the namespace in the database
        $strReq = 'UPDATE ' . $this->table .
        " SET setting_ns = '" . $this->con->escape($newNs) . "' " .
        " WHERE setting_ns = '" . $this->con->escape($oldNs) . "' ";
        $this->con->execute($strReq);

        return true;
    }

    /**
     * Delete a whole namespace with all settings pertaining to it.
     *
     * @param      string  $ns     Namespace name
     *
     * @return     bool
     */
    public function delNamespace($ns)
    {
        if (!$this->exists($ns)) {
            return false;
        }

        // Remove the namespace from the namespace array
        unset($this->namespaces[$ns]);

        // Delete all settings from the namespace in the database
        $strReq = 'DELETE FROM ' . $this->table .
        " WHERE setting_ns = '" . $this->con->escape($ns) . "' ";
        $this->con->execute($strReq);

        return true;
    }

    /**
     * Returns full namespace with all settings pertaining to it.
     *
     * @param      string  $ns     Namespace name
     *
     * @return     dcNamespace
     */
    public function get($ns)
    {
        return ($this->namespaces[$ns] ?? null);
    }

    /**
     * Magic __get method.
     *
     * @copydoc ::get
     *
     * @param      string  $n      namespace name
     *
     * @return     dcNamespace
     */
    public function __get($n)
    {
        return $this->get($n);
    }

    /**
     * Check if a namespace exists
     *
     * @param      string  $ns     Namespace name
     *
     * @return     boolean
     */
    public function exists($ns)
    {
        return array_key_exists($ns, $this->namespaces);
    }

    /**
     * Dumps namespaces.
     *
     * @return     array
     */
    public function dumpNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Returns a list of settings matching given criteria, for any blog.
     * <b>$params</b> is an array taking the following
     * optionnal parameters:
     *
     * - ns : retrieve setting from given namespace
     * - id : retrieve only settings corresponding to the given id
     *
     * @param      array   $params  The parameters
     *
     * @return     record  The global settings.
     */
    public function getGlobalSettings($params = [])
    {
        $strReq = 'SELECT * from ' . $this->table . ' ';
        $where  = [];
        if (!empty($params['ns'])) {
            $where[] = "setting_ns = '" . $this->con->escape($params['ns']) . "'";
        }
        if (!empty($params['id'])) {
            $where[] = "setting_id = '" . $this->con->escape($params['id']) . "'";
        }
        if (isset($params['blog_id'])) {
            if (!empty($params['blog_id'])) {
                $where[] = "blog_id = '" . $this->con->escape($params['blog_id']) . "'";
            } else {
                $where[] = 'blog_id IS NULL';
            }
        }
        if (count($where) != 0) {
            $strReq .= ' WHERE ' . join(' AND ', $where);
        }
        $strReq .= ' ORDER by blog_id';

        return $this->con->select($strReq);
    }

    /**
     * Updates a setting from a given record.
     *
     * @param      record  $rs     The setting to update
     */
    public function updateSetting($rs)
    {
        $cur                = $this->con->openCursor($this->table);
        $cur->setting_id    = $rs->setting_id;
        $cur->setting_value = $rs->setting_value;
        $cur->setting_type  = $rs->setting_type;
        $cur->setting_label = $rs->setting_label;
        $cur->blog_id       = $rs->blog_id;
        $cur->setting_ns    = $rs->setting_ns;
        if ($cur->blog_id == null) {
            $where = 'WHERE blog_id IS NULL ';
        } else {
            $where = "WHERE blog_id = '" . $this->con->escape($cur->blog_id) . "' ";
        }
        $cur->update($where . "AND setting_id = '" . $this->con->escape($cur->setting_id) . "' AND setting_ns = '" . $this->con->escape($cur->setting_ns) . "' ");
    }

    /**
     * Drops a setting from a given record.
     *
     * @param      record  $rs     The setting to drop
     *
     * @return     int  Number of deleted records (0 if setting does not exist)
     */
    public function dropSetting($rs)
    {
        $strReq = 'DELETE FROM ' . $this->table . ' ';
        if ($rs->blog_id == null) {
            $strReq .= 'WHERE blog_id IS NULL ';
        } else {
            $strReq .= "WHERE blog_id = '" . $this->con->escape($rs->blog_id) . "' ";
        }
        $strReq .= "AND setting_id = '" . $this->con->escape($rs->setting_id) . "' AND setting_ns = '" . $this->con->escape($rs->setting_ns) . "' ";

        return $this->con->execute($strReq);
    }
}
