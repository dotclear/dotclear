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
class dcSettings
{
    // Properties

    /**
     * Database connection object
     *
     * @var object
     */
    protected $con;

    /**
     * Settings table name
     *
     * @var string
     */
    protected $table;

    /**
     * Blog ID
     *
     * @var string
     */
    protected $blog_id;

    /**
     * Associative namespaces array
     *
     * @var        array
     */
    protected $namespaces = [];

    /**
     * Object constructor. Retrieves blog settings and puts them in $namespaces
     * array. Local (blog) settings have a highest priority than global settings.
     *
     * @param      mixed    $blog_id  The blog identifier
     */
    public function __construct($blog_id)
    {
        $this->con     = dcCore::app()->con;
        $this->table   = dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME;
        $this->blog_id = $blog_id;
        $this->loadSettings();
    }

    /**
    Retrieves all namespaces (and their settings) from database, with one query.
     */
    private function loadSettings(): void
    {
        $sql = new dcSelectStatement();
        $sql
            ->columns([
                'blog_id',
                'setting_id',
                'setting_value',
                'setting_type',
                'setting_label',
                'setting_ns',
            ])
            ->from($this->table)
            ->where('blog_id = ' . $sql->quote($this->blog_id))
            ->or('blog_id IS NULL')
            ->order([
                'setting_ns ASC',
                'setting_id DESC',
            ]);

        try {
            $rs = $sql->select();
        } catch (Exception $e) {
            trigger_error(__('Unable to retrieve namespaces:') . ' ' . $this->con->error(), E_USER_ERROR);
        }

        /* Prevent empty tables (install phase, for instance) */
        if ($rs->isEmpty()) {
            return;
        }

        do {
            $ns = trim((string) $rs->f('setting_ns'));
            if (!$rs->isStart()) {
                // we have to go up 1 step, since namespaces construction performs a fetch()
                // at very first time
                $rs->movePrev();
            }
            $this->namespaces[$ns] = new dcNamespace($this->blog_id, $ns, $rs);
        } while (!$rs->isStart());
    }

    /**
     * Create a new namespace. If the namespace already exists, return it without modification.
     *
     * @param      string  $namespace     Namespace name
     *
     * @return     dcNamespace
     */
    public function addNamespace(string $namespace): dcNamespace
    {
        if (!$this->exists($namespace)) {
            $this->namespaces[$namespace] = new dcNamespace($this->blog_id, $namespace);
        }

        return $this->namespaces[$namespace];
    }

    /**
     * Rename a namespace.
     *
     * @param      string     $old_namespace  The old ns
     * @param      string     $new_namespace  The new ns
     *
     * @throws     Exception
     *
     * @return     bool      return true if no error, else false
     */
    public function renNamespace(string $old_namespace, string $new_namespace): bool
    {
        if (!$this->exists($old_namespace) || $this->exists($new_namespace)) {
            return false;
        }

        if (!preg_match(dcNamespace::NS_NAME_SCHEMA, $new_namespace)) {
            throw new Exception(sprintf(__('Invalid setting namespace: %s'), $new_namespace));
        }

        // Rename the namespace in the namespace array
        $this->namespaces[$new_namespace] = $this->namespaces[$old_namespace];
        unset($this->namespaces[$old_namespace]);

        // Rename the namespace in the database
        $sql = new dcUpdateStatement();
        $sql
            ->ref($this->table)
            ->set('setting_ns = ' . $sql->quote($new_namespace))
            ->where('setting_ns = ' . $sql->quote($old_namespace));
        $sql->update();

        return true;
    }

    /**
     * Delete a whole namespace with all settings pertaining to it.
     *
     * @param      string  $namespace     Namespace name
     *
     * @return     bool
     */
    public function delNamespace(string $namespace): bool
    {
        if (!$this->exists($namespace)) {
            return false;
        }

        // Remove the namespace from the namespace array
        unset($this->namespaces[$namespace]);

        // Delete all settings from the namespace in the database
        $sql = new dcDeleteStatement();
        $sql
            ->from($this->table)
            ->where('setting_ns = ' . $sql->quote($namespace));

        $sql->delete();

        return true;
    }

    /**
     * Returns full namespace with all settings pertaining to it.
     *
     * @param      string  $namespace     Namespace name
     *
     * @return     dcNamespace
     */
    public function get(string $namespace): dcNamespace
    {
        return $this->addNamespace($namespace);
    }

    /**
     * Magic __get method.
     *
     * @param      string  $namespace      namespace name
     *
     * @return     dcNamespace
     */
    public function __get(string $namespace): dcNamespace
    {
        return $this->get($namespace);
    }

    /**
     * Check if a namespace exists
     *
     * @param      string  $namespace     Namespace name
     *
     * @return     bool
     */
    public function exists(string $namespace): bool
    {
        return array_key_exists($namespace, $this->namespaces);
    }

    /**
     * Dumps namespaces.
     *
     * @return     array
     */
    public function dumpNamespaces(): array
    {
        return $this->namespaces;
    }
}
