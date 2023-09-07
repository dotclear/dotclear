<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\Cursor;

/**
 * Version hanlder interface.
 *
 * Tracks core or modules id,version pairs.
 */
interface VersionInterface
{
    /**
     * The Version database table name.
     *
     * @var     string  VERSION_TABLE_NAME
     */
    public const VERSION_TABLE_NAME = 'version';

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The version database table cursor
     */
    public function openVersionCursor(): Cursor;

    /**
     * Get the version of a module.
     *
     * Since 2.28 getVersion() always returns string.
     *
     * @param 	string 	$module 	The module
     *
     * @return 	string 	The version.
     */
    public function getVersion(string $module = 'core'): string;

    /**
     * Get all known versions.
     *
     * @return  array<string,string>    The versions.
     */
    public function getVersions(): array;

    /**
     * Set the version of a module.
     *
     * @param   string  $module     The module
     * @param   string  $version    The version
     */
    public function setVersion(string $module, string $version): void;

    /**
     * Remove a module version entry.
     *
     * @param      string  $module  The module
     */
    public function unsetVersion(string $module): void;

    /**
     * Compare the given version of a module with the registered one.
     *
     * Returned values:
     *
     * -1 : newer version already installed
     * 0 : same version installed
     * 1 : older version is installed
     *
     * @param   string  $module     The module
     * @param   string  $version    The version
     *
     * @return  int     The test result
     */
    public function compareVersion(string $module, string $version): int;

    /**
     * Test if version is newer than the registered one.
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     bool     True if it is newer
     */
    public function newerVersion(string $module, string $version): bool;
}
