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
use Dotclear\Exception\ProcessException;
use Dotclear\Exception\BadRequestException;

/**
 * @brief   Authentication handler interface.
 *
 * @since   2.28
 */
interface AuthInterface
{
    /**
     * User table name.
     *
     * @var    string  USER_TABLE_NAME
     */
    public const USER_TABLE_NAME = 'user';

    /**
     * User permissions table name.
     *
     * @var    string  PERMISSIONS_TABLE_NAME
     */
    public const PERMISSIONS_TABLE_NAME = 'permissions';

    /**
     * User blog permission code for : All (super).
     *
     * @var    string  PERMISSION_SUPERADMIN
     */
    public const PERMISSION_SUPERADMIN = 'superadmin';

    /**
     * User blog permission code for : All.
     *
     * @var    string  PERMISSION_ADMIN
     */
    public const PERMISSION_ADMIN = 'admin';

    /**
     * User blog permission code for : All entries/comments.
     *
     * @var    string  PERMISSION_CONTENT_ADMIN
     */
    public const PERMISSION_CONTENT_ADMIN = 'contentadmin';

    /**
     * User blog permission code for : Own entries/comments.
     *
     * @var    string  PERMISSION_USAGE
     */
    public const PERMISSION_USAGE = 'usage';

    /**
     * User blog permission code for : Publication of entries/comments.
     *
     * @var    string  PERMISSION_PUBLISH
     */
    public const PERMISSION_PUBLISH = 'publish';

    /**
     * User blog permission code for : Deletion of entries/comments.
     *
     * @var    string  PERMISSION_DELETE
     */
    public const PERMISSION_DELETE = 'delete';

    /**
     * User blog permission code for : Categories.
     *
     * @var    string  PERMISSION_CATEGORIES
     */
    public const PERMISSION_CATEGORIES = 'categories';

    /**
     * User blog permission code for : All media.
     *
     * @var    string  PERMISSION_MEDIA_ADMIN
     */
    public const PERMISSION_MEDIA_ADMIN = 'media_admin';

    /**
     * User blog permission code for : Own media.
     *
     * @var    string  PERMISSION_MEDIA
     */
    public const PERMISSION_MEDIA = 'media';

    /**
     * Open a user database table cursor.
     *
     * @return  Cursor  The user database table cursor
     */
    public function openUserCursor(): Cursor;

    /**
     * Open a user permission database table cursor.
     *
     * @return  Cursor  The user permission database table cursor
     */
    public function openPermCursor(): Cursor;

    /// @name Credentials and user permissions
    //@{

    /**
     * Checks if user exists and can log in.
     *
     * <var>$pwd</var> argument is optionnal
     * while you may need to check user without password.
     * This method will create credentials
     * and populate all needed object properties.
     *
     * @param   string  $user_id        User ID
     * @param   string  $pwd            User password
     * @param   string  $user_key       User key check
     * @param   bool    $check_blog     Checks if user is associated to a blog or not.
     */
    public function checkUser(string $user_id, ?string $pwd = null, ?string $user_key = null, bool $check_blog = true): bool;

    /**
     * This method crypt given string (password, session_id, …).
     *
     * @param   string  $pwd    String to be crypted
     *
     * @return  string  crypted value
     */
    public function crypt(string $pwd): string;

    /**
     * This method crypt given string (password, session_id, …).
     *
     * @param   string  $pwd    String to be crypted
     *
     * @return  string  crypted value
     */
    public function cryptLegacy(string $pwd): string;

    /**
     * This method only check current user password.
     *
     * @param   string  $pwd    User password
     */
    public function checkPassword(string $pwd): bool;

    /**
     * This method checks if user session cookie exists.
     */
    public function sessionExists(): bool;

    /**
     * This method checks user session validity.
     *
     * @param   string  $uid    Browser UID
     */
    public function checkSession(?string $uid = null): bool;

    /**
     * Checks if user must change his password in order to login.
     */
    public function mustChangePassword(): bool;

    /**
     * Checks if user is super admin.
     */
    public function isSuperAdmin(): bool;

    /**
     * Checks if user has permissions given in <var>$permissions</var> for blog
     * <var>$blog_id</var>.
     *
     * @param   string  $permissions    Permissions list (comma separated)
     * @param   string  $blog_id        Blog ID
     */
    public function check(?string $permissions, ?string $blog_id): bool;

    /**
     * Returns true if user is allowed to change its password.
     */
    public function allowPassChange(): bool;

    //@}

    /// @name Sudo
    //@{

    /**
     * Calls <var>$fn</var> function with super admin rights.
     *
     * @throws  ProcessException
     *
     * @param   callable    $fn     Callback function
     * @param   mixed       $args   Callback arguments
     *
     * @return  mixed   The function result
     */
    public function sudo($fn, ...$args);

    //@}

    /// @name User information and options
    //@{

    /**
     * Get user preferences handler.
     *
     * @throws  \Error   if no user is set
     */
    public function prefs(): UserPreferencesInterface;

    /**
     * Returns user permissions for a blog.
     *
     * As an array which looks like:
     *
     *  - [blog_id]
     *    - [permission] => true
     *    - ...
     *
     * @param   string  $blog_id    Blog ID
     *
     * @return  false|array<string, bool>
     */
    public function getPermissions(?string $blog_id);

    /**
     * Gets the blog count.
     *
     * @return  int     The blog count.
     */
    public function getBlogCount(): int;

    /**
     * Finds an user blog.
     *
     * @param   string  $blog_id        The blog identifier
     * @param   bool    $all_status     False if we not allow removed blog (not super admin only)
     *
     * @return  mixed
     */
    public function findUserBlog(?string $blog_id = null, bool $all_status = true);

    /**
     * Returns current user ID.
     *
     * @return  null|string
     */
    public function userID();

    /**
     * Returns information about a user .
     *
     * @param   string  $information    Information name
     *
     * @return  mixed
     */
    public function getInfo(string $information);

    /**
     * Returns a specific user option
     *
     * @param   string  $option     Option name
     *
     * @return  mixed
     */
    public function getOption(string $option);

    /**
     * Returns all user options in an associative array.
     *
     * @return  array<string, mixed>
     */
    public function getOptions(): array;
    //@}

    /// @name Permissions
    //@{

    /**
     * Returns an array with permissions parsed from the string <var>$level</var>
     *
     * @param   string  $level  Permissions string
     *
     * @return  array<string, bool>
     */
    public function parsePermissions($level): array;

    /**
     * Makes permissions string from an array.
     *
     * @param   array<string>   $list   The list
     */
    public function makePermissions(array $list): string;

    /**
     * Returns <var>perm_types</var> property content.
     *
     * @return  array<string, string>  The permissions types.
     */
    public function getPermissionsTypes(): array;

    /**
     * Adds a new permission type.
     *
     * @param   string  $name   Permission name
     * @param   string  $title  Permission title
     */
    public function setPermissionType(string $name, string $title): void;

    //@}

    /// @name Password recovery
    //@{

    /**
     * Add a recover key to a specific user.
     *
     * User is identified by its email and password.
     *
     * @throws  BadRequestException
     *
     * @param   string  $user_id        User ID
     * @param   string  $user_email     User Email
     */
    public function setRecoverKey(string $user_id, string $user_email): string;

    /**
     * Creates a new user password using recovery key.
     *
     * Returns an array:
     * - user_email
     * - user_id
     * - new_pass
     *
     * @throws  BadRequestException
     *
     * @param   string  $recover_key    Recovery key
     *
     * @return  array<string, string>
     */
    public function recoverUserPassword(string $recover_key): array;

    //@}
}
