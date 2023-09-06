<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Module;

/**
 * Module defined properties.
 *
 * Provides an object to handle modules properties (themes or plugins).
 *
 * @since 2.25
 */
class ModuleDefine
{
    /** @var    int     Module state : enabled */
    public const STATE_ENABLED = 0;
    /** @var    int     Module state : disbaled */
    public const STATE_INIT_DISABLED = 1;
    /** @var    int     Module state : soft disabled */
    public const STATE_SOFT_DISABLED = 2;
    /** @var    int     Module state : hard disabled */
    public const STATE_HARD_DISABLED = 4;

    /** @var    string  Undefined module's name  */
    public const DEFAULT_NAME = 'undefined';

    /** @var    string  Undefined module's type */
    public const DEFAULT_TYPE = 'undefined';

    /** @var    int     Default module's priority */
    public const DEFAULT_PRIORITY = 1000;

    /** @var    string  Module id, must be module's root path */
    private string $id;

    /** @var array  Dependencies : implies */
    private array $implies = [];
    /** @var array  Dependencies : missing */
    private array $missing = [];
    /** @var array  Dependencies : using */
    private array $using = [];

    /** @var    array   Module properties */
    private array $properties = [];

    /** @var    array   Module default properties */
    private array $default = [
        // set by dc
        'state'         => self::STATE_INIT_DISABLED,
        'root'          => null,
        'namespace'     => null,
        'root_writable' => false,
        'distributed'   => false,

        // required
        'name'    => self::DEFAULT_NAME,
        'desc'    => '',
        'author'  => '',
        'version' => '0',
        'type'    => self::DEFAULT_TYPE,

        // optionnal
        'permissions'       => null,
        'priority'          => self::DEFAULT_PRIORITY,
        'standalone_config' => false,
        'requires'          => [],
        'settings'          => [],

        // optionnal++
        'label'      => '',
        'support'    => '',
        'details'    => '',
        'repository' => '',

        // theme specifics
        'parent' => null,
        'tplset' => DC_DEFAULT_TPLSET,

        // store specifics
        'file'            => '',
        'current_version' => 0,

        // DA specifics
        'section' => '',
        'tags'    => '',
        'sshot'   => '',
        'score'   => 0,
        'dc_min'  => '',

        // modules list specifics
        'sid'   => '',
        'sname' => '',

        // special widgets
        'widgettitleformat'     => '',
        'widgetsubtitleformat'  => '',
        'widgetcontainerformat' => '',
    ];

    /**
     * Create a module definition.
     *
     * @param   string  $id The module identifier (root path)
     */
    public function __construct(string $id)
    {
        $this->id = $id;
        $this->init();
    }

    /**
     * Initialize module's properties.
     *
     * Module's define class must use this to set
     * their properties.
     */
    protected function init(): void
    {
    }

    /**
     * Check if module is defined.
     *
     * @return  bool    True if module is defined
     */
    public function isDefined(): bool
    {
        return $this->get('name') != self::DEFAULT_NAME;
    }

    /**
     * Check if module update is locked.
     *
     * @return  bool    True if update is disabled
     */
    public function updLocked(): bool
    {
        return is_string($this->get('root')) && file_exists($this->get('root') . DIRECTORY_SEPARATOR . Modules::MODULE_FILE_LOCKED);
    }

    public function addImplies(string $dep): void
    {
        $this->implies[] = $dep;
    }

    public function getImplies(): array
    {
        return $this->implies;
    }

    public function addMissing(string $dep, string $reason): void
    {
        $this->missing[$dep] = $reason;
    }

    public function getMissing(): array
    {
        return $this->missing;
    }

    public function addUsing(string $dep): void
    {
        $this->using[] = $dep;
    }

    public function getUsing(): array
    {
        return $this->using;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets array of properties.
     *
     * Mainly used for backward compatibility.
     *
     * @return  array<string,mixed>     The properties
     */
    public function dump(): array
    {
        return array_merge($this->default, $this->properties, [
            'id'             => $this->id,
            'enabled'        => $this->get('state') == self::STATE_ENABLED,
            'implies'        => $this->implies,
            'cannot_enable'  => $this->missing,
            'cannot_disable' => $this->using,
        ]);
    }

    /**
     * Store a property and its value.
     *
     * The property key MUST exist in default properties
     *
     * @param   string  $identifier     The identifier
     * @param   mixed   $value          The value
     *
     * @return  ModuleDefine
     */
    public function set(string $identifier, $value = null): ModuleDefine
    {
        if (array_key_exists($identifier, $this->default)) {
            $this->properties[$identifier] = $value;
        }

        return $this;
    }

    /**
     * Magic function, store a property and its value.
     *
     * @param   string  $identifier     The identifier
     * @param   mixed   $value          The value
     */
    public function __set(string $identifier, $value = null): void
    {
        $this->set($identifier, $value);
    }

    /**
     * Gets the specified property value (null if does not exist).
     *
     * This returns null if property does not exists
     *
     * @param   string  $identifier     The identifier
     *
     * @return  mixed
     */
    public function get(string $identifier): mixed
    {
        if ($identifier == 'id') {
            return $this->id;
        }

        if (array_key_exists($identifier, $this->properties)) {
            return $this->properties[$identifier];
        }

        return array_key_exists($identifier, $this->default) ? $this->default[$identifier] : null;
    }

    /**
     * Gets the specified property value (null if does not exist).
     *
     * @param   string  $identifier     The identifier
     *
     * @return  mixed
     */
    public function __get(string $identifier): mixed
    {
        return $this->get($identifier);
    }

    /**
     * Test if a property exists.
     *
     * @param   string  $identifier     The identifier
     *
     * @return  bool
     */
    public function __isset(string $identifier): bool
    {
        return isset($this->properties[$identifier]);
    }

    /**
     * Unset a property.
     *
     * @param   string  $identifier  The identifier
     */
    public function __unset(string $identifier): void
    {
        if (array_key_exists($identifier, $this->default)) {
            $this->properties[$identifier] = $this->default[$identifier];
        }
    }
}
