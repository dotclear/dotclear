<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use ArrayObject;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Exception\BadRequestException;
use Dotclear\Exception\UnauthorizedException;

/**
 * @brief   Users handler interface.
 *
 * @since   2.28
 */
interface UsersInterface
{
    /**
     * Gets the user by its ID.
     *
     * @param      string  $id     The identifier
     *
     * @return     MetaRecord  The user.
     */
    public function getUser(string $id): MetaRecord;

    /**
     * Returns a users list.
     *
     * <b>$params</b> is an array with the following
     * optionnal parameters:
     *
     * - <var>q</var>: search string (on user_id, user_name, user_firstname)
     * - <var>user_id</var>: user ID
     * - <var>order</var>: ORDER BY clause (default: user_id ASC)
     * - <var>limit</var>: LIMIT clause (should be an array ![limit,offset])
     *
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $params      The parameters
     * @param      bool                                             $count_only  Count only results
     *
     * @return     MetaRecord  The users.
     */
    public function getUsers(array|ArrayObject $params = [], bool $count_only = false): MetaRecord;

    /**
     * Adds a new user.
     *
     * Takes a Cursor as input and returns the new user ID.
     *
     * @param      Cursor     $cur    The user Cursor
     *
     * @throws     BadRequestException|UnauthorizedException
     */
    public function addUser(Cursor $cur): string;

    /**
     * Updates an existing user. Returns the user ID.
     *
     * @param      string     $id     The user identifier
     * @param      Cursor     $cur    The Cursor
     *
     * @throws     UnauthorizedException
     */
    public function updUser(string $id, Cursor $cur): string;

    /**
     * Deletes a user.
     *
     * @param      string     $id     The user identifier
     *
     * @throws     UnauthorizedException
     */
    public function delUser(string $id): void;

    /**
     * Determines if user exists.
     *
     * @param      string  $id     The identifier
     *
     * @return      bool  True if user exists, False otherwise.
     */
    public function userExists(string $id): bool;

    /**
     * Returns all user permissions.
     *
     * Returns an array which looks like:
     * - [blog_id]
     * - [name] => Blog name
     * - [url] => Blog URL
     * - [p]
     * - [permission] => true
     * - ...
     *
     * @param      string  $id     The user identifier
     *
     * @return     array<string,array<string,string|array<string,bool>>>   The user permissions.
     */
    public function getUserPermissions(string $id): array;

    /**
     * Sets user permissions.
     *
     * The <var>$perms</var> array looks like:
     * - [blog_id] => '|perm1|perm2|'
     * - ...
     *
     * @param      string     $id     The user identifier
     * @param      array<string,array<string,bool>>      $perms  The permissions
     *
     * @throws     UnauthorizedException
     */
    public function setUserPermissions(string $id, array $perms): void;

    /**
     * Sets the user blog permissions.
     *
     * @param      string     $id            The user identifier
     * @param      string     $blog_id       The blog identifier
     * @param      array<string,bool>      $perms         The permissions
     * @param      bool       $delete_first  Delete permissions first
     *
     * @throws     UnauthorizedException
     */
    public function setUserBlogPermissions(string $id, string $blog_id, array $perms, bool $delete_first = true): void;

    /**
     * Sets the user default blog.
     *
     * This blog will be selected when user log in.
     *
     * @param      string  $id       The user identifier
     * @param      string  $blog_id  The blog identifier
     */
    public function setUserDefaultBlog(string $id, string $blog_id): void;

    /**
     * Removes users default blogs.
     *
     * @param      array<int,int|string>  $ids    The blogs to remove
     */
    public function removeUsersDefaultBlogs(array $ids): void;

    /**
     * Returns user default settings.
     *
     * Returns an associative array with setting names in keys.
     *
     * @return     array<string,int|bool|array<string,string>|string>
     */
    public function userDefaults(): array;

    /**
     * Build user's common name.
     *
     * Returns user's common name given to his
     * <var>user_id</var>, <var>user_name</var>, <var>user_firstname</var> and
     * <var>user_displayname</var>.
     *
     * @param   string  $user_id           The user identifier
     * @param   string  $user_name          The user name
     * @param   string  $user_firstname     The user firstname
     * @param   string  $user_displayname   The user displayname
     */
    public function getUserCN(string $user_id, ?string $user_name, ?string $user_firstname, ?string $user_displayname): string;
}
