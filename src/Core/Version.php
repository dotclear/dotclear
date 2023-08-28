<?php
/**
 * Version handler.
 *
 * Handle id,version pairs through database.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;

class Version
{
    /** @var     string  Versions database table name */
    public const VERSION_TABLE_NAME = 'version';

    /** @var    array<string,string>    The version stack */
    private array $stack = [];

    /**
     * Constructor.
     *
     * @param   AbstractHandler     $con    The dc connection instance
     */
    public function __construct(
        private AbstractHandler $con
    ) {
        $this->loadVersions();
    }

    /**
     * Get the version of a module.
     *
     * Since 2.28 getVersion() always returns string.
     *
     * @param 	string 	$module 	The module
     *
     * @return 	string 	The version.
     */
    public function getVersion(string $module = 'core'): string
    {
        return $this->stack[$module] ?? '';
    }

    /**
     * Get all known versions.
     *
     * @return  array<string,string>    The versions.
     */
    public function getVersions(): array
    {
        return $this->stack;
    }

    /**
     * Set the version of a module.
     *
     * @param   string  $module     The module
     * @param   string  $version    The version
     */
    public function setVersion(string $module, string $version): void
    {
        $cur = $this->con->openCursor($this->con->prefix() . self::VERSION_TABLE_NAME);
        $cur->setField('module', $module);
        $cur->setField('version', $version);

        if ($this->getVersion($module) === '') {
            $cur->insert();
        } else {
            $sql = new UpdateStatement();
            $sql->where('module = ' . $sql->quote($module));
            $sql->update($cur);
        }

        $this->stack[$module] = $version;
    }

    /**
     * Remove a module version entry.
     *
     * @param      string  $module  The module
     */
    public function unsetVersion(string $module): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->con->prefix() . self::VERSION_TABLE_NAME)
            ->where('module = ' . $sql->quote($module));

        $sql->delete();

        unset($this->stack[$module]);
    }

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
    public function compareVersion(string $module, string $version): int
    {
        return version_compare($version, $this->getVersion($module));
    }

    /**
     * Test if version is newer than the registered one.
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     bool     True if it is newer
     */
    public function newerVersion(string $module, string $version): bool
    {
        return $this->compareVersion($module, $version) === 1;
    }

    /**
     * Load versions from database.
     */
    private function loadVersions(): void
    {
        if (empty($this->stack)) {
            $rs = (new SelectStatement())
                ->columns([
                    'module',
                    'version',
                ])
                ->from($this->con->prefix() . self::VERSION_TABLE_NAME)
                ->select();

            while ($rs->fetch()) {
                $this->stack[(string) $rs->f('module')] = (string) $rs->f('version');
            }
        }
    }
}
