<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Exception\BadRequestException;

/**
 * @brief   Blog settings handler interface.
 *
 * This class provides blog settings management. This class instance exists as
 * Blog $settings property. You should create a new settings instance when
 * updating another blog settings.
 *
 * @since   2.28
 */
interface BlogSettingsInterface
{
    /**
     * Create a new instance a BLogSettings.
     *
     * Retrieves blog settings and puts them in $workspaces array.
     * Local (blog) settings have a highest priority than global settings.
     *
     * If instance is created without blog ID, only globals settings are
     * manageabled.
     *
     * @param   null|string     $blog_id    The blog ID
     */
    public function createFromBlog(?string $blog_id): BlogSettingsInterface;

    /**
     * Create a new workspace.
     *
     * If the workspace already exists, return it without modification.
     *
     * @param   string  $workspace  Namespace name
     */
    public function addWorkspace(string $workspace): BlogWorkspaceInterface;

    /**
     * Rename a namespace.
     *
     * @param   string  $old_workspace  The old ns
     * @param   string  $new_workspace  The new ns
     *
     * @throws  BadRequestException
     *
     * @return  bool    return true if no error, else false
     */
    public function renWorkspace(string $old_workspace, string $new_workspace): bool;

    /**
     * Delete a whole workspace with all settings pertaining to it.
     *
     * @param   string  $workspace  workspace name
     */
    public function delWorkspace(string $workspace): bool;

    /**
     * Returns full workspace with all settings pertaining to it.
     *
     * @param   string  $workspace  workspace name
     */
    public function get(string $workspace): BlogWorkspaceInterface;

    /**
     * Magic __get method.
     *
     * @param   string  $workspace  workspace name
     */
    public function __get(string $workspace): BlogWorkspaceInterface;

    /**
     * Check if a workspace exists.
     *
     * @param   string  $workspace  Namespace name
     */
    public function exists(string $workspace): bool;

    /**
     * Dumps workspaces.
     *
     * @return  array<string, BlogWorkspaceInterface>
     */
    public function dumpWorkspaces(): array;

    /**
     * Alias of addWorkspace.
     *
     * @deprecated  since 2.28, use self::addWorkspace()  instead
     */
    public function addNamespace(string $namespace): BlogWorkspaceInterface;

    /**
     * Alias of renWorkspace.
     *
     * @deprecated  since 2.28, use self::renWorkspace()  instead
     */
    public function renNamespace(string $old_namespace, string $new_namespace): bool;

    /**
     * Alias of delWorkspace.
     *
     * @deprecated  since 2.28, use self::delWorkspace()  instead
     */
    public function delNamespace(string $namespace): bool;

    /**
     * Alias of dumpWorkspaces.
     *
     * @deprecated  since 2.28, use self::dumpWorkspaces()  instead
     *
     * @return  array<string, BlogWorkspaceInterface>
     */
    public function dumpNamespaces(): array;
}
