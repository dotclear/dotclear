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
class dcNamespace
{
    // Constants

    /**
     * Namespace (blog parameters) table name
     *
     * @var        string
     */
    public const NS_TABLE_NAME = 'setting';

    /**
     * Regexp namespace name schema
     *
     * @var        string
     */
    public const NS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';

    /**
     * Regexp namespace ID schema
     *
     * @var        string
     */
    protected const NS_ID_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9_]+$/';

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
     * Global settings
     *
     * @var array
     */
    protected $global_settings = [];

    /**
     * Local settings
     *
     * @var array
     */
    protected $local_settings = [];

    /**
     * Blog settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Current namespace
     *
     * @var string
     */
    protected $namespace;

    /**
     * Object constructor. Retrieves blog settings and puts them in $settings
     * array. Local (blog) settings have a highest priority than global settings.
     *
     * @param      mixed      $blog_id  The blog identifier
     * @param      string     $name     The namespace ID
     * @param      dcRecord   $rs       The recordset
     *
     * @throws     Exception
     */
    public function __construct($blog_id, string $name, ?dcRecord $rs = null)
    {
        if (preg_match(self::NS_NAME_SCHEMA, $name)) {
            $this->namespace = $name;
        } else {
            throw new Exception(sprintf(__('Invalid setting dcNamespace: %s'), $name));
        }

        $this->con     = dcCore::app()->con;
        $this->table   = dcCore::app()->prefix . self::NS_TABLE_NAME;
        $this->blog_id = $blog_id;

        $this->getSettings($rs);
    }

    /**
     * Gets the settings.
     *
     * @param      dcRecord  $rs     The recordset
     */
    private function getSettings(?dcRecord $rs = null)
    {
        if ($rs === null) {
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
                ->where($sql->orGroup([
                    'blog_id = ' . $sql->quote($this->blog_id),
                    'blog_id IS NULL',
                ]))
                ->and('setting_ns = ' . $sql->quote($this->namespace))
                ->order('setting_id DESC');

            try {
                $rs = $sql->select();
            } catch (Exception $e) {
                trigger_error(__('Unable to retrieve settings:') . ' ' . $this->con->error(), E_USER_ERROR);
            }
        }
        while ($rs->fetch()) {
            if ($rs->f('setting_ns') !== $this->namespace) {
                break;
            }
            $id    = trim((string) $rs->f('setting_id'));
            $value = $rs->f('setting_value');
            $type  = $rs->f('setting_type');

            if ($type === 'array') {
                $value = @json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } else {
                if ($type === 'float' || $type === 'double') {
                    $type = 'float';
                } elseif ($type !== 'boolean' && $type !== 'integer') {
                    $type = 'string';
                }
            }

            settype($value, $type);

            $array = ($rs->blog_id ? 'local' : 'global') . '_settings';

            $this->{$array}[$id] = [
                'ns'     => $this->namespace,
                'value'  => $value,
                'type'   => $type,
                'label'  => (string) $rs->f('setting_label'),
                'global' => (!$rs->blog_id),
            ];
        }

        // Blog settings (local) overwrite global ones
        $this->settings = array_merge($this->global_settings, $this->local_settings);
    }

    /**
     * Returns true if a setting exist, else false
     *
     * @param      string   $name      The identifier
     * @param      bool     $global    The global
     *
     * @return     bool
     */
    public function settingExists(string $name, bool $global = false): bool
    {
        $array = ($global ? 'global' : 'local') . '_settings';

        return isset($this->{$array}[$name]);
    }

    /**
     * Returns setting value if exists.
     *
     * @param      string  $name      Setting name
     *
     * @return     mixed
     */
    public function get($name)
    {
        if (isset($this->settings[$name]) && isset($this->settings[$name]['value'])) {
            return $this->settings[$name]['value'];
        }
    }

    /**
     * Returns global setting value if exists.
     *
     * @param      string  $name      Setting name
     *
     * @return     mixed
     */
    public function getGlobal($name)
    {
        if (isset($this->global_settings[$name]) && isset($this->global_settings[$name]['value'])) {
            return $this->global_settings[$name]['value'];
        }
    }

    /**
     * Returns local setting value if exists.
     *
     * @param      string  $name      Setting name
     *
     * @return     mixed
     */
    public function getLocal($name)
    {
        if (isset($this->local_settings[$name]) && isset($this->local_settings[$name]['value'])) {
            return $this->local_settings[$name]['value'];
        }
    }

    /**
     * Magic __get method.
     *
     * @param      string  $name      Setting name
     *
     * @return     mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Sets a setting in $settings property. This sets the setting for script
     * execution time only and if setting exists.
     *
     * @param      string  $name       The setting name
     * @param      mixed   $value      The setting value
     */
    public function set($name, $value)
    {
        if (isset($this->settings[$name])) {
            $this->settings[$name]['value'] = $value;
        }
    }

    /**
     * Magic __set method.
     *
     * @param      string  $name       The setting name
     * @param      mixed   $value      The setting value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Creates or updates a setting.
     *
     * $type could be 'string', 'integer', 'float', 'boolean', 'array' or null. If $type is
     * null and setting exists, it will keep current setting type.
     *
     * $ignore_value allow you to not change setting. Useful if you need to change
     * a setting label or type and don't want to change its value.
     *
     * @param      string     $name          The setting identifier
     * @param      mixed      $value         The setting value
     * @param      string     $type          The setting type
     * @param      string     $label         The setting label
     * @param      bool       $ignore_value  Change setting value or not
     * @param      bool       $global        Setting is global
     *
     * @throws     Exception
     */
    public function put(string $name, $value, ?string $type = null, ?string $label = null, bool $ignore_value = true, bool $global = false): void
    {
        if (!preg_match(self::NS_ID_SCHEMA, $name)) {
            throw new Exception(sprintf(__('%s is not a valid setting id'), $name));
        }

        # We don't want to change setting value
        if (!$ignore_value) {
            if (!$global && $this->settingExists($name, false)) {
                $value = $this->local_settings[$name]['value'];
            } elseif ($this->settingExists($name, true)) {
                $value = $this->global_settings[$name]['value'];
            }
        }

        # Setting type
        if ($type === 'double') {
            $type = 'float';
        } elseif ($type === null) {
            if (!$global && $this->settingExists($name, false)) {
                $type = $this->local_settings[$name]['type'];
            } elseif ($this->settingExists($name, true)) {
                $type = $this->global_settings[$name]['type'];
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

        # We don't change label
        if (!$label) {
            if (!$global && $this->settingExists($name, false)) {
                $label = $this->local_settings[$name]['label'];
            } elseif ($this->settingExists($name, true)) {
                $label = $this->global_settings[$name]['label'];
            }
        }

        if ($type !== 'array') {
            settype($value, $type);
        } else {
            $value = json_encode($value, JSON_THROW_ON_ERROR);
        }

        $cur = $this->con->openCursor($this->table);

        $cur->setting_value = ($type === 'boolean') ? (string) (int) $value : (string) $value;
        $cur->setting_type  = $type;
        $cur->setting_label = $label;

        #If we are local, compare to global value
        if (!$global && $this->settingExists($name, true)) {
            $g            = $this->global_settings[$name];
            $same_setting = ($g['ns'] === $this->namespace && $g['value'] === $value && $g['type'] === $type && $g['label'] === $label);

            # Drop setting if same value as global
            if ($same_setting && $this->settingExists($name, false)) {
                $this->drop($name);
            } elseif ($same_setting) {
                return;
            }
        }

        if ($this->settingExists($name, $global) && $this->namespace == $this->settings[$name]['ns']) {
            $sql = new dcUpdateStatement();

            if ($global) {
                $sql->where('blog_id IS NULL');
            } else {
                $sql->where('blog_id = ' . $sql->quote($this->blog_id));
            }
            $sql
                ->and('setting_id = ' . $sql->quote($name))
                ->and('setting_ns = ' . $sql->quote($this->namespace));

            $sql->update($cur);
        } else {
            $cur->setting_id = $name;
            $cur->blog_id    = $global ? null : $this->blog_id;
            $cur->setting_ns = $this->namespace;

            $cur->insert();
        }
    }

    /**
     * Rename an existing setting in a Namespace
     *
     * @param      string     $old_name  The old setting identifier
     * @param      string     $new_name  The new setting identifier
     *
     * @throws     Exception
     *
     * @return     bool
     */
    public function rename(string $old_name, string $new_name): bool
    {
        if (!$this->namespace) {
            throw new Exception(__('No namespace specified'));
        }

        if (!array_key_exists($old_name, $this->settings) || array_key_exists($new_name, $this->settings)) {
            return false;
        }

        if (!preg_match(self::NS_ID_SCHEMA, $new_name)) {
            throw new Exception(sprintf(__('%s is not a valid setting id'), $new_name));
        }

        // Rename the setting in the settings array
        $this->settings[$new_name] = $this->settings[$old_name];
        unset($this->settings[$old_name]);

        // Rename the setting in the database
        $sql = new dcUpdateStatement();
        $sql
            ->ref($this->table)
            ->set('setting_id = ' . $sql->quote($new_name))
            ->where('setting_ns = ' . $sql->quote($this->namespace))
            ->and('setting_id = ' . $sql->quote($old_name));

        $sql->update();

        return true;
    }

    /**
     * Removes an existing setting in a Namespace.
     *
     * @param      string     $name     The setting identifier
     *
     * @throws     Exception
     */
    public function drop(string $name): void
    {
        if (!$this->namespace) {
            throw new Exception(__('No namespace specified'));
        }

        $sql = new dcDeleteStatement();
        $sql
            ->from($this->table);

        if ($this->blog_id === null) {
            $sql->where('blog_id IS NULL');
        } else {
            $sql->where('blog_id = ' . $sql->quote($this->blog_id));
        }

        $sql
            ->and('setting_id = ' . $sql->quote($name))
            ->and('setting_ns = ' . $sql->quote($this->namespace));

        $sql->delete();
    }

    /**
     * Removes every existing specific setting in a namespace
     *
     * @param      string     $name      Setting ID
     * @param      boolean    $global  Remove global setting too
     *
     * @throws     Exception
     */
    public function dropEvery(string $name, bool $global = false): void
    {
        if (!$this->namespace) {
            throw new Exception(__('No namespace specified'));
        }

        $sql = new dcDeleteStatement();
        $sql
            ->from($this->table);

        if (!$global) {
            $sql->where('blog_id IS NOT NULL');
        }
        $sql
            ->and('setting_id = ' . $sql->quote($name))
            ->and('setting_ns = ' . $sql->quote($this->namespace));

        $sql->delete();
    }

    /**
     * Removes all existing settings in a Namespace.
     *
     * @param      bool       $force_global  Force global pref drop
     *
     * @throws     Exception
     */
    public function dropAll(bool $force_global = false): void
    {
        if (!$this->namespace) {
            throw new Exception(__('No namespace specified'));
        }

        $sql = new dcDeleteStatement();
        $sql
            ->from($this->table);

        if (($force_global) || ($this->blog_id === null)) {
            $sql->where('blog_id IS NULL');
            $global = true;
        } else {
            $sql->where('blog_id = ' . $sql->quote($this->blog_id));
            $global = false;
        }

        $sql->and('setting_ns = ' . $sql->quote($this->namespace));

        $sql->delete();

        $array          = ($global ? 'global' : 'local') . '_settings';
        $this->{$array} = [];

        // Blog settings (local) overwrite global ones
        $this->settings = array_merge($this->global_settings, $this->local_settings);
    }

    /**
     * Dumps a namespace.
     *
     * @return     string
     */
    public function dumpNamespace()
    {
        return $this->namespace;
    }

    /**
     * Dumps settings.
     *
     * @return     array
     */
    public function dumpSettings()
    {
        return $this->settings;
    }

    /**
     * Dumps local settings.
     *
     * @return     array
     */
    public function dumpLocalSettings()
    {
        return $this->local_settings;
    }

    /**
     * Dumps global settings.
     *
     * @return     array
     */
    public function dumpGlobalSettings()
    {
        return $this->global_settings;
    }
}
