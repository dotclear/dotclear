<?php
/**
 * Authentication hanlder interface.
 *
 * Tracks core or modules id,version pairs.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use dcPrefs;
use Dotclear\Database\Cursor;
use Dotclear\Helper\Crypt;

interface AuthInterface
{
    // Constants

    /** @var    string  User table name */
    public const USER_TABLE_NAME = 'user';

    /** @var    string  User permissions table name */
    public const PERMISSIONS_TABLE_NAME = 'permissions';

    /** @var    string  User blog permission code for : All */
    public const PERMISSION_ADMIN = 'admin';
    /** @var    string  User blog permission code for : All entries/comments */
    public const PERMISSION_CONTENT_ADMIN = 'contentadmin';
    /** @var    string  User blog permission code for : Own entries/comments */
    public const PERMISSION_USAGE = 'usage';
    /** @var    string  User blog permission code for : Publication of entries/comments */
    public const PERMISSION_PUBLISH = 'publish';
    /** @var    string  User blog permission code for : Deletion of entries/comments */
    public const PERMISSION_DELETE = 'delete';
    /** @var    string  User blog permission code for : Categories */
    public const PERMISSION_CATEGORIES = 'categories';
    /** @var    string  User blog permission code for : All media */
    public const PERMISSION_MEDIA_ADMIN = 'media_admin';
    /** @var    string  User blog permission code for : Own media */
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
     *
     * @return  bool
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
     *
     * @return  bool
     */
    public function checkPassword(string $pwd): bool;

    /**
     * This method checks if user session cookie exists.
     *
     * @return  bool
     */
    public function sessionExists(): bool;

    /**
     * This method checks user session validity.
     *
     * @param   string  $uid    Browser UID
     *
     * @return  bool
     */
    public function checkSession(?string $uid = null): bool;

    /**
     * Checks if user must change his password in order to login.
     *
     * @return  bool
     */
    public function mustChangePassword(): bool;

    /**
     * Checks if user is super admin.
     *
     * @return  bool
     */
    public function isSuperAdmin(): bool;

    /**
     * Checks if user has permissions given in <var>$permissions</var> for blog
     * <var>$blog_id</var>.
     *
     * @param   string  $permissions    Permissions list (comma separated)
     * @param   string  $blog_id        Blog ID
     *
     * @return  bool
     */
    public function check(?string $permissions, ?string $blog_id): bool;

    /**
     * Returns true if user is allowed to change its password.
     *
     * @return  bool
     */
    public function allowPassChange(): bool;

    //@}

    /// @name Sudo
    //@{

    /**
     * Calls <var>$fn</var> function with super admin rights.
     *
     * @param   callable|array  $fn     Callback function
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
     *
     * @return  dcPrefs
     */
    public function prefs(): dcPrefs;

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
     * @return  false|array
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
    public function getInfo($information);

    /**
     * Returns a specific user option
     *
     * @param   string  $option     Option name
     *
     * @return  mixed
     */
    public function getOption($option);

    /**
     * Returns all user options in an associative array.
     *
     * @return  array
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
     * @return  array
     */
    public function parsePermissions($level): array;

    /**
     * Makes permissions string from an array.
     *
     * @param   array   $list   The list
     *
     * @return  string
     */
    public function makePermissions($list): string;

    /**
     * Returns <var>perm_types</var> property content.
     *
     * @return  array
     */
    public function getPermissionsTypes(): array;

    /**
     * Adds a new permission type.
     *
     * @param   string  $name   Permission name
     * @param   string  $title  Permission title
     */
    public function setPermissionType(string $name, string $title);

    //@}

    /// @name Password recovery
    //@{

    /**
     * Add a recover key to a specific user.
     *
     * User is identified by its email and password.
     *
     * @param   string  $user_id        User ID
     * @param   string  $user_email     User Email
     *
     * @return  string
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
     * @param   string  $recover_key    Recovery key
     *
     * @return  array
     */
    public function recoverUserPassword(string $recover_key): array;

    //@}
}
