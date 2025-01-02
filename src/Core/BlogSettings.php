<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Exception\BadRequestException;
use Dotclear\Exception\ProcessException;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\DeprecatedInterface;
use Throwable;

/**
 * @brief   Blog settings handler.
 *
 * This class provides blog settings management. This class instance exists as
 * Blog $settings property. You should create a new settings instance when
 * updating another blog settings.
 *
 * @since   2.28, container services have been added to constructor
 */
class BlogSettings implements BlogSettingsInterface
{
    /**
     * Settings table name.
     */
    protected string $table;

    /**
     * Associative namespaces array.
     *
     * @var     array<string, BlogWorkspaceInterface>   $workspaces
     */
    protected $workspaces = [];

    /**
     * Constructor.
     *
     * @param   BlogWorkspaceInterface  $workspace      The blog workspace handler
     * @param   ConnectionInterface     $con            The database connection instance
     * @param   DeprecatedInterface     $deprecated     The deprecated handler
     * @param   null|string             $blog_id        The blog ID
     */
    public function __construct(
        protected BlogWorkspaceInterface $workspace,
        protected ConnectionInterface $con,
        protected DeprecatedInterface $deprecated,
        protected ?string $blog_id = null
    ) {
        $this->table = $this->con->prefix() . $this->workspace::NS_TABLE_NAME;

        if ($blog_id) {
            $this->loadSettings();
        }
    }

    public function createFromBlog(?string $blog_id): BlogSettingsInterface
    {
        return new self($this->workspace, $this->con, $this->deprecated, $blog_id);
    }

    /**
     * Retrieves all workspaces.
     *
     * and their settings, from database, with one query.
     */
    private function loadSettings(): void
    {
        $sql = new SelectStatement();
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
            ->where('blog_id = ' . $sql->quote((string) $this->blog_id))
            ->or('blog_id IS NULL')
            ->order([
                'setting_ns ASC',
                'setting_id DESC',
            ]);

        try {
            $rs = $sql->select();
        } catch (Throwable) {
            throw new ProcessException(__('Unable to retrieve namespaces:') . ' ' . $this->con->error());
        }

        /* Prevent empty tables (install phase, for instance) */
        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            return;
        }

        do {
            $ns = trim((string) $rs->f('setting_ns'));
            if (!$rs->isStart()) {
                // we have to go up 1 step, since workspaces construction performs a fetch()
                // at very first time
                $rs->movePrev();
            }
            $this->workspaces[$ns] = $this->workspace->createFromBlog($this->blog_id, $ns, $rs);
        } while (!$rs->isStart());
    }

    public function addWorkspace(string $workspace): BlogWorkspaceInterface
    {
        if (!$this->exists($workspace)) {
            $this->workspaces[$workspace] = $this->workspace->createFromBlog($this->blog_id, $workspace);
        }

        return $this->workspaces[$workspace];
    }

    public function renWorkspace(string $old_workspace, string $new_workspace): bool
    {
        if (!$this->exists($old_workspace) || $this->exists($new_workspace)) {
            return false;
        }

        if (!preg_match($this->workspace::NS_NAME_SCHEMA, $new_workspace)) {
            throw new BadRequestException(sprintf(__('Invalid setting namespace: %s'), $new_workspace));
        }

        // Rename the namespace in the database
        $sql = new UpdateStatement();
        $sql
            ->ref($this->table)
            ->set('setting_ns = ' . $sql->quote($new_workspace))
            ->where('setting_ns = ' . $sql->quote($old_workspace));
        $sql->update();

        // Reload the renamed namespace in the namespace array
        $this->workspaces[$new_workspace] = $this->workspace->createFromBlog($this->blog_id, $new_workspace);

        // Remove the old namespace from the namespace array
        unset($this->workspaces[$old_workspace]);

        return true;
    }

    public function delWorkspace(string $workspace): bool
    {
        if (!$this->exists($workspace)) {
            return false;
        }

        // Remove the workspace from the workspace array
        unset($this->workspaces[$workspace]);

        // Delete all settings from the workspace in the database
        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('setting_ns = ' . $sql->quote($workspace));

        $sql->delete();

        return true;
    }

    public function get(string $workspace): BlogWorkspaceInterface
    {
        return $this->addWorkspace($workspace);
    }

    public function __get(string $workspace): BlogWorkspaceInterface
    {
        return $this->addWorkspace($workspace);
    }

    public function exists(string $workspace): bool
    {
        return array_key_exists($workspace, $this->workspaces);
    }

    /**
     * Dumps workspaces.
     *
     * @return     array<string, BlogWorkspaceInterface>
     */
    public function dumpWorkspaces(): array
    {
        return $this->workspaces;
    }

    public function addNamespace(string $namespace): BlogWorkspaceInterface
    {
        $this->deprecated->set(self::class . '->addWorkspace()', '2.28');

        return $this->addWorkspace($namespace);
    }

    public function renNamespace(string $old_namespace, string $new_namespace): bool
    {
        $this->deprecated->set(self::class . '->renWorkspace()', '2.28');

        return $this->renWorkspace($old_namespace, $new_namespace);
    }

    public function delNamespace(string $namespace): bool
    {
        $this->deprecated->set(self::class . '->delWorkspace()', '2.28');

        return $this->delWorkspace($namespace);
    }

    /**
     * Dumps namespaces.
     *
     * @return  array<string, BlogWorkspaceInterface>
     */
    public function dumpNamespaces(): array
    {
        $this->deprecated->set(self::class . '->dumpWorkspaces()', '2.28');

        return $this->workspaces;
    }
}
