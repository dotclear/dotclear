<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

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
     * Constructor.
     *
     * Retrieves user prefs and puts them in $workspaces
     * array. Local (user) prefs have a highest priority than global prefs.
     *
     * @param   string          $user_id   The user identifier
     * @param   null|string     $workspace The workspace to load
     *
     * @throws  \Exception
     */
    public function __construct(string $user_id, ?string $workspace = null);

    /**
     * Create a new workspace.
     *
     * If the workspace already exists, return it without modification.
     *
     * @param   string  $workspace  Workspace name
     *
     * @return  UserWorkspaceInterface
     */
    public function addWorkspace(string $workspace): UserWorkspaceInterface;

    /**
     * Rename a workspace.
     *
     * @param   string  $old_workspace  The old workspace name
     * @param   string  $new_workspace  The new workspace name
     *
     * @throws  \Exception
     *
     * @return  bool
     */
    public function renWorkspace(string $old_workspace, string $new_workspace): bool;

    /**
     * Delete a whole workspace with all preferences pertaining to it.
     *
     * @param   string  $workspace  Workspace name
     *
     * @return  bool
     */
    public function delWorkspace(string $workspace): bool;

    /**
     * Returns full workspace with all prefs pertaining to it.
     *
     * @param   string  $workspace  Workspace name
     *
     * @return  UserWorkspaceInterface
     */
    public function get(string $workspace): UserWorkspaceInterface;

    /**
     * Magic __get method.
     *
     * @copydoc ::get
     *
     * @param   string  $workspace  Workspace name
     *
     * @return  UserWorkspaceInterface
     */
    public function __get(string $workspace): UserWorkspaceInterface;

    /**
     * Check if a workspace exists.
     *
     * @param   string  $workspace  Workspace name
     *
     * @return  boolean
     */
    public function exists(string $workspace): bool;

    /**
     * Dumps workspaces.
     *
     * @return  array<string, array<string, mixed>>
     */
    public function dumpWorkspaces(): array;
}
