<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\App;

/**
 * @brief   Module defined properties.
 *
 * Provides an object to handle modules properties (themes or plugins).
 *
 * @since   2.25
 */
class ModuleDefine
{
    /**
     * Module state : enabled.
     *
     * @var     int     STATE_ENABLED */
    public const STATE_ENABLED = 0;

    /**
     * Module state : disbaled.
     *
     * @var     int     STATE_INIT_DISABLED
     */
    public const STATE_INIT_DISABLED = 1;

    /**
     * Module state : soft disabled.
     *
     * @var     int     STATE_SOFT_DISABLED
     */
    public const STATE_SOFT_DISABLED = 2;

    /**
     * Module state : hard disabled.
     *
     * @var     int     STATE_HARD_DISABLED
     */
    public const STATE_HARD_DISABLED = 4;

    /**
     * Undefined module's name.
     *
     * @var     string  DEFAULT_NAME
     */
    public const DEFAULT_NAME = 'undefined';

    /**
     * Undefined module's type.
     *
     * @var     string  DEFAULT_TYPE
     */
    public const DEFAULT_TYPE = 'undefined';

    /**
     * Default module's priority.
     *
     * @var     int     DEFAULT_PRIORITY
     */
    public const DEFAULT_PRIORITY = 1000;

    /**
     * Dependencies : implies.
     *
     * @var     array<int,string>   $implies
     */
    private array $implies = [];

    /**
     * Dependencies : missing.
     *
     * @var     array<string,string>    $missing
     */
    private array $missing = [];

    /**
     * Dependencies : using.
     *
     * @var     array<int,string>   $using
     */
    private array $using = [];

    /**
     * Module properties.
     *
     * @var     array<string,mixed>     $properties
     */
    private array $properties = [];

    /**
     * Module default properties.
     *
     * @var     array<string, mixed>   $default
     */
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
        'permissions'        => null,
        'priority'           => self::DEFAULT_PRIORITY,
        'standalone_config'  => false,
        'information_config' => false,
        'requires'           => [],
        'settings'           => [],

        // optionnal++
        'label'      => '',
        'support'    => '',
        'details'    => '',
        'repository' => '',

        // theme specifics
        'parent' => null,
        'tplset' => null,

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
    public function __construct(
        private readonly string $id
    ) {
        $this->default['tplset'] = App::config()->defaultTplset();

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

    /**
     * Add imply dependency.
     *
     * @param   string  $dep    The module ID
     */
    public function addImplies(string $dep): void
    {
        $this->implies[] = $dep;
    }

    /**
     * Get implies dependencies.
     *
     * @return  array<int,string>   The dependencies
     */
    public function getImplies(): array
    {
        return $this->implies;
    }

    /**
     * Add missing dependency.
     *
     * @param   string  $dep        The module ID
     * @param   string  $reason     The reason
     */
    public function addMissing(string $dep, string $reason): void
    {
        $this->missing[$dep] = $reason;
    }

    /**
     * Get missing dependencies.
     *
     * @return  array<string,string>   The dependencies and reasons
     */
    public function getMissing(): array
    {
        return $this->missing;
    }

    /**
     * Add using dependency.
     *
     * @param   string  $dep        The module ID
     */
    public function addUsing(string $dep): void
    {
        $this->using[] = $dep;
    }

    /**
     * Get using dependencies.
     *
     * @return  array<int,string>   The dependencies
     */
    public function getUsing(): array
    {
        return $this->using;
    }

    /**
     * Get module ID.
     *
     * @return  string  The module ID
     */
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
        return [
            ...$this->default,
            ...$this->properties,
            'id'             => $this->id,
            'enabled'        => $this->get('state') == self::STATE_ENABLED,
            'implies'        => $this->implies,
            'cannot_enable'  => $this->missing,
            'cannot_disable' => $this->using,
        ];
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
        if ($identifier === 'id') {
            return $this->id;
        }

        return $this->properties[$identifier] ?? $this->default[$identifier] ?? null;
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
