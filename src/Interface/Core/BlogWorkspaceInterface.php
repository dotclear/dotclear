<?php
/**
 * Blog namespace for settings handler.
 *
 * Handle id,version pairs through database.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;

interface BlogWorkspaceInterface
{
    // Constants

    /** @var    string  Namespace (blog parameters) table name */
    public const NS_TABLE_NAME = 'setting';

    /** @var    string  Regexp namespace name schema */
    public const NS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';

    /** @var    string  Regexp namespace ID schema */
    public const NS_ID_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9_]+$/';

    /** @var    string  Settings types stored in table subset of settype() allowed type : string */
    public const NS_STRING = 'string';
    /** @var    string  Settings types stored in table subset of settype() allowed type : float */
    public const NS_FLOAT = 'float';
    /** @var    string  Settings types stored in table subset of settype() allowed type : bool */
    public const NS_BOOL = 'boolean';
    /** @var    string  Settings types stored in table subset of settype() allowed type : int */
    public const NS_INT = 'integer';
    /** @var    string  Settings types stored in table subset of settype() allowed type : array */
    public const NS_ARRAY = 'array';

    /** @var    string  Settings types converted to another type : double */
    public const NS_DOUBLE = 'double';     // -> NS_FLOAT

    /** @var    string  Settings types aliases : text = double */
    public const NS_TEXT = self::NS_STRING;
    /** @var    string  Settings types aliases : boolean = bool */
    public const NS_BOOLEAN = self::NS_BOOL;
    /** @var    string  Settings types aliases : integer = int */
    public const NS_INTEGER = self::NS_INT;

    /**
     * Constructor.
     *
     * Retrieves blog settings and puts them in $settings array.
     * Local (blog) settings have a highest priority than global settings.
     *
     * @param   null|string         $blog_id    The blog identifier
     * @param   null|string         $workspace  The namespace ID
     * @param   MetaRecord          $rs         The recordset
     *
     * @throws  \Exception
     */
    public function __construct(?string $blog_id = null, ?string $workspace = null, ?MetaRecord $rs = null);

    /**
     * Creat e a new instance a BLogWrokspace.
     *
     * @param   null|string         $blog_id    The blog identifier
     * @param   string              $workspace  The namespace ID
     * @param   MetaRecord          $rs         The recordset
     *
     * @return  BlogWorkspaceInterface
     */
    public function init(?string $blog_id, string $workspace, ?MetaRecord $rs = null): BlogWorkspaceInterface;

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The blog workspace database table cursor
     */
    public function openBlogWorkspaceCursor(): Cursor;

    /**
     * Check if setting exists.
     *
     * @param   string  $name       The identifier
     * @param   bool    $global     The global
     *
     * @return  bool    True if it exists
     */
    public function settingExists(string $name, bool $global = false): bool;

    /**
     * Get a setting value.
     *
     * Search first on local settings, then global ones.
     *
     * @param   string  $name   Setting name
     *
     * @return  mixed   Returns setting value if exists.
     */
    public function get($name);

    /**
     * Get a global setting value.
     *
     * @param   string  $name   Setting name
     *
     * @return  mixed   Returns global setting value if exists.
     */
    public function getGlobal($name);

    /**
     * Get a local setting value.
     *
     * @param   string  $name   Setting name
     *
     * @return  mixed   Returns local setting value if exists.
     */
    public function getLocal($name);

    /**
     * Magic alias of self::get().
     *
     * @param   string  $name   Setting name
     *
     * @return  mixed
     */
    public function __get($name);

    /**
     * Sets a setting in $settings property.
     *
     * This sets the setting for script
     * execution time only and if setting exists.
     *
     * @param   string  $name   The setting name
     * @param   mixed   $value  The setting value
     */
    public function set($name, $value): void;

    /**
     * Magic alias of self::set().
     *
     * @param   string  $name   The setting name
     * @param   mixed   $value  The setting value
     */
    public function __set($name, $value): void;

    /**
     * Creates or updates a setting.
     *
     * $type could be self::NS_STRING, self::NS_INT, self::NS_FLOAT, self::NS_BOOL, self::NS_ARRAY or null. If $type is
     * null and setting exists, it will keep current setting type.
     *
     * $ignore_value allow you to not change setting. Useful if you need to change
     * a setting label or type and don't want to change its value.
     *
     * @param   string  $name           The setting identifier
     * @param   mixed   $value          The setting value
     * @param   string  $type           The setting type
     * @param   string  $label          The setting label
     * @param   bool    $ignore_value   Change setting value or not
     * @param   bool    $global         Setting is global
     *
     * @throws  \Exception
     */
    public function put(string $name, $value, ?string $type = null, ?string $label = null, bool $ignore_value = true, bool $global = false): void;

    /**
     * Rename an existing setting in a wrokspace.
     *
     * @param   string  $old_name   The old setting identifier
     * @param   string  $new_name   The new setting identifier
     *
     * @throws  \Exception
     *
     * @return  bool
     */
    public function rename(string $old_name, string $new_name): bool;

    /**
     * Removes an existing setting in a workspace.
     *
     * @param   string  $name   The setting identifier
     *
     * @throws  \Exception
     */
    public function drop(string $name): void;

    /**
     * Removes every existing specific setting in a workspace.
     *
     * @param   string      $name       Setting ID
     * @param   boolean     $global     Remove global setting too
     *
     * @throws  \Exception
     */
    public function dropEvery(string $name, bool $global = false): void;

    /**
     * Removes all existing settings in a workspace.
     *
     * @param   bool    $force_global   Force global pref drop
     *
     * @throws  \Exception
     */
    public function dropAll(bool $force_global = false): void;

    /**
     * Get the workspace name.
     *
     * @return  string
     */
    public function dumpWorkspace(): string;

    /**
     * Dumps settings.
     *
     * @return  array
     */
    public function dumpSettings(): array;

    /**
     * Dumps local settings.
     *
     * @return  array
     */
    public function dumpLocalSettings(): array;

    /**
     * Dumps global settings.
     *
     * @return  array
     */
    public function dumpGlobalSettings(): array;

    /**
     * Alias of dumpWorkspace.
     *
     * @deprecated  since 2.28, use self::dumpWorkspace()  instead
     */
    public function dumpNamespace(): string;
}
