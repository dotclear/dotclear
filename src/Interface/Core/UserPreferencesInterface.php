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
 * @brief   User prefs handler interface.
 *
 * UserPreferences provides user preferences management. This class instance exists as
 * Auth $prefs property. You should create a new prefs instance when
 * updating another user prefs.
 *
 * @since   2.28
 */
interface UserPreferencesInterface
{
    /**
     * Create a new instance a UserPreferences.
     *
     * Retrieves user prefs and puts them in $workspaces array.
     * Local (user) prefs have a highest priority than global prefs.
     *
     * @param   string          $user_id        The user identifier
     * @param   null|string     $user_workspace The workspace to load
     */
    public function createFromUser(string $user_id, ?string $user_workspace = null): UserPreferencesInterface;

    /**
     * Create a new workspace.
     *
     * If the workspace already exists, return it without modification.
     *
     * @param   string  $workspace  Workspace name
     */
    public function addWorkspace(string $workspace): UserWorkspaceInterface;

    /**
     * Rename a workspace.
     *
     * @param   string  $old_workspace  The old workspace name
     * @param   string  $new_workspace  The new workspace name
     *
     * @throws  BadRequestException
     */
    public function renWorkspace(string $old_workspace, string $new_workspace): bool;

    /**
     * Delete a whole workspace with all preferences pertaining to it.
     *
     * @param   string  $workspace  Workspace name
     */
    public function delWorkspace(string $workspace): bool;

    /**
     * Returns full workspace with all prefs pertaining to it.
     *
     * @param   string  $workspace  Workspace name
     */
    public function get(string $workspace): UserWorkspaceInterface;

    /**
     * Magic __get method.
     *
     * @copydoc ::get
     *
     * @param   string  $workspace  Workspace name
     */
    public function __get(string $workspace): UserWorkspaceInterface;

    /**
     * Check if a workspace exists.
     *
     * @param   string  $workspace  Workspace name
     */
    public function exists(string $workspace): bool;

    /**
     * Dumps workspaces.
     *
     * @return  array<string, UserWorkspaceInterface>
     */
    public function dumpWorkspaces(): array;
}
