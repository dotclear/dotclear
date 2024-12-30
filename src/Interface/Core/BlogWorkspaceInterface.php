<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Exception\BadRequestException;

/**
 * @brief   Blog namespace for settings handler interface.
 *
 * @since   2.28
 */
interface BlogWorkspaceInterface
{
    // Constants

    /**
     * Namespace (blog parameters) table name.
     *
     * @var    string   NS_TABLE_NAME
     */
    public const NS_TABLE_NAME = 'setting';

    /**
     * Regexp namespace name schema.
     *
     * @var    string  NS_NAME_SCHEMA
     */
    public const NS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';

    /**
     * Regexp namespace ID schema.
     *
     * @var    string  Regexp NS_ID_SCHEMA */
    public const NS_ID_SCHEMA = '/^[a-zA-Z]\w+$/';

    /**
     * Settings types stored in table subset of settype() allowed type : string.
     *
     * @var    string   NS_STRING
     */
    public const NS_STRING = 'string';

    /**
     * Settings types stored in table subset of settype() allowed type : float.
     *
     * @var    string   NS_FLOAT
     */
    public const NS_FLOAT = 'float';

    /**
     * Settings types stored in table subset of settype() allowed type : bool
     *
     * @var    string  NS_BOOL
     */
    public const NS_BOOL = 'boolean';

    /**
     * Settings types stored in table subset of settype() allowed type : int.
     *
     * @var    string  NS_INT
     */
    public const NS_INT = 'integer';

    /**
     * Settings types stored in table subset of settype() allowed type : array.
     *
     * @var    string   NS_ARRAY
     */
    public const NS_ARRAY = 'array';

    /**
     * Settings types converted to another type : double.
     *
     * @var    string  NS_DOUBLE
     */
    public const NS_DOUBLE = 'double';     // -> NS_FLOAT

    /**
     * Settings types aliases : text = string.
     *
     * @var    string  NS_TEXT
     */
    public const NS_TEXT = self::NS_STRING;

    /**
     * Settings types aliases : boolean = bool.
     *
     * @var    string  NS_BOOLEAN
     */
    public const NS_BOOLEAN = self::NS_BOOL;

    /**
     * Settings types aliases : integer = int.
     *
     * @var    string  NS_INTEGER
     */
    public const NS_INTEGER = self::NS_INT;

    /**
     * Create a new instance a BLogWorkspace.
     *
     * @param   null|string         $blog_id    The blog identifier
     * @param   string              $workspace  The namespace ID
     * @param   MetaRecord          $rs         The recordset
     */
    public function createFromBlog(?string $blog_id, string $workspace, ?MetaRecord $rs = null): BlogWorkspaceInterface;

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
     * @throws  BadRequestException
     */
    public function put(string $name, $value, ?string $type = null, ?string $label = null, bool $ignore_value = true, bool $global = false): void;

    /**
     * Rename an existing setting in a wrokspace.
     *
     * @param   string  $old_name   The old setting identifier
     * @param   string  $new_name   The new setting identifier
     *
     * @throws  BadRequestException
     */
    public function rename(string $old_name, string $new_name): bool;

    /**
     * Removes an existing setting in a workspace.
     *
     * @param   string  $name   The setting identifier
     *
     * @throws  BadRequestException
     */
    public function drop(string $name): void;

    /**
     * Removes every existing specific setting in a workspace.
     *
     * @param   string      $name       Setting ID
     * @param   boolean     $global     Remove global setting too
     *
     * @throws  BadRequestException
     */
    public function dropEvery(string $name, bool $global = false): void;

    /**
     * Removes all existing settings in a workspace.
     *
     * @param   bool    $force_global   Force global pref drop
     *
     * @throws  BadRequestException
     */
    public function dropAll(bool $force_global = false): void;

    /**
     * Get the workspace name.
     */
    public function dumpWorkspace(): string;

    /**
     * Dumps settings.
     *
     * @return  array<string, array<string, mixed>>
     */
    public function dumpSettings(): array;

    /**
     * Dumps local settings.
     *
     * @return  array<string, array<string, mixed>>
     */
    public function dumpLocalSettings(): array;

    /**
     * Dumps global settings.
     *
     * @return  array<string, array<string, mixed>>
     */
    public function dumpGlobalSettings(): array;

    /**
     * Alias of dumpWorkspace.
     *
     * @deprecated  since 2.28, use self::dumpWorkspace()  instead
     */
    public function dumpNamespace(): string;
}
