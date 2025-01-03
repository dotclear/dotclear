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
 * @brief   User workspace for preferences handler interface.
 *
 * @since   2.28
 */
interface UserWorkspaceInterface
{
    /**
     * Workspace (user preferences) table name.
     *
     * @var    string  WS_TABLE_NAME
     */
    public const WS_TABLE_NAME = 'pref';

    /**
     * Regexp workspace name schema.
     *
     * @var    string  WS_NAME_SCHEMA
     */
    public const WS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';

    /**
     * Regexp workspace ID schema.
     *
     * @var    string  WS_ID_SCHEMA
     */
    public const WS_ID_SCHEMA = '/^[a-zA-Z]\w+$/';

    /**
     * Preferences types stored in table, subset of settype() allowed type : string.
     *
     * @var    string   WS_STRING
     */
    public const WS_STRING = 'string';

    /**
     * Preferences types stored in table, subset of settype() allowed type : float.
     *
     * @var    string   WS_FLOAT
     */
    public const WS_FLOAT = 'float';

    /**
     * Preferences types stored in table, subset of settype() allowed type : bool.
     *
     * @var    string   WS_BOOL
     */
    public const WS_BOOL = 'boolean';

    /**
     * Preferences types stored in table, subset of settype() allowed type : int.
     *
     * @var    string   WS_INT
     */
    public const WS_INT = 'integer';

    /**
     * Preferences types stored in table, subset of settype() allowed type : array.
     *
     * @var    string   WS_ARRAY
     */
    public const WS_ARRAY = 'array';

    /**
     * Preferences types converted to another type : double.
     *
     * @var    string   WS_DOUBLE
     */
    public const WS_DOUBLE = 'double';     // -> NS_FLOAT

    /**
     * Preferences types aliases : text = string.
     *
     * @var    string   WS_TEXT
     */
    public const WS_TEXT = self::WS_STRING;

    /**
     * Preferences types aliases : boolean = bool.
     *
     * @var    string   WS_BOOLEAN
     */
    public const WS_BOOLEAN = self::WS_BOOL;

    /**
     * Preferences types aliases : inetegr = int.
     *
     * @var    string   WS_INTEGER
     */
    public const WS_INTEGER = self::WS_INT;

    /**
     * Create a new instance a UserWorkspace.
     *
     * @param   null|string     $user_id    The user identifier
     * @param   string          $workspace  The workspace name
     * @param   MetaRecord      $rs         The recordset
     */
    public function createFromUser(?string $user_id, string $workspace, ?MetaRecord $rs = null): UserWorkspaceInterface;

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The user workspace database table cursor
     */
    public function openUserWorkspaceCursor(): Cursor;

    /**
     * Check if a user preference exists.
     *
     * @param   string  $name       The identifier
     * @param   bool    $global     The global
     */
    public function prefExists(string $name, bool $global = false): bool;

    /**
     * Get a preference value.
     *
     * Search first on local preferences, then global ones.
     *
     * @param   string  $name   Pref name
     *
     * @return  mixed   Returns preference value if exists.
     */
    public function get(string $name);

    /**
     * Get a global preference value.
     *
     * @param   string  $name   Pref name
     *
     * @return  mixed   Returns preference value if exists.
     */
    public function getGlobal(string $name);

    /**
     * Get a local preference value.
     *
     * @param   string  $name   Pref name
     *
     * @return  mixed   Returns preference value if exists.
     */
    public function getLocal(string $name);

    /**
     * Magic alias of self::get().
     *
     * @param   string  $name   Preference name
     *
     * @return  mixed
     */
    public function __get(string $name);

    /**
     * Sets a pref in preference property.
     *
     * This sets the pref for script
     * execution time only and if pref exists.
     *
     * @param   string  $name   The pref name
     * @param   mixed   $value  The pref value
     */
    public function set(string $name, $value): void;

    /**
     * Magic alias of self::set().
     *
     * @param   string  $name   The pref name
     * @param   mixed   $value  The pref value
     */
    public function __set(string $name, $value): void;

    /**
     * Creates or updates a preference.
     *
     * $type could be self::WS_STRING, self::WS_INT, self::WS_FLOAT, self::WS_BOOL or null. If $type is
     * null and pref exists, it will keep current pref type.
     *
     * $ignore_value allow you to not change pref. Useful if you need to change
     * a pref label or type and don't want to change its value.
     *
     * @param   string  $name           The pref identifier
     * @param   mixed   $value          The pref value
     * @param   string  $type           The pref type
     * @param   string  $label          The pref label
     * @param   bool    $ignore_value   Change pref value or not
     * @param   bool    $global         Pref is global
     *
     * @throws  BadRequestException
     */
    public function put(string $name, $value, ?string $type = null, ?string $label = null, bool $ignore_value = true, bool $global = false): void;

    /**
     * Rename an existing preference in a Workspace.
     *
     * @param   string  $old_name   The old identifier
     * @param   string  $new_name   The new identifier
     *
     * @throws  BadRequestException
     *
     * @return  bool    false is error, true if renamed
     */
    public function rename(string $old_name, string $new_name): bool;

    /**
     * Removes an existing preference Workspace.
     *
     * @param   string  $name           The pref identifier
     * @param   bool    $force_global   Force global pref drop
     *
     * @throws  BadRequestException
     */
    public function drop(string $name, bool $force_global = false): void;

    /**
     * Removes every existing specific pref in a workspace.
     *
     * @param   string  $name       Pref ID
     * @param   bool    $global     Remove global pref too
     *
     * @throws  BadRequestException
     */
    public function dropEvery(string $name, bool $global = false): void;

    /**
     * Removes all existing preference in a Workspace.
     *
     * @param   bool    $force_global   Remove global prefs too
     *
     * @throws  BadRequestException
     */
    public function dropAll(bool $force_global = false): void;

    /**
     * Dumps a workspace.
     */
    public function dumpWorkspace(): string;

    /**
     * Dumps preferences.
     *
     * @return  array<string, array<string, mixed>>
     */
    public function dumpPrefs(): array;

    /**
     * Dumps local preferences.
     *
     * @return  array<string, array<string, mixed>>
     */
    public function dumpLocalPrefs(): array;

    /**
     * Dumps global preferences.
     *
     * @return  array<string, array<string, mixed>>
     */
    public function dumpGlobalPrefs(): array;
}
