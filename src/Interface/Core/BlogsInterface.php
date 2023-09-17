<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use ArrayObject;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Exception;

/**
 * @brief   Blogs handler interface.
 *
 * @since   2.28
 */
interface BlogsInterface
{
    /**
     * Gets all blog status.
     *
     * This SHOULD use blog status from Blog constante
     *
     * @return     array<int,string>    An array of available blog status codes and names.
     */
    public function getAllBlogStatus(): array;

    /**
     * Returns a blog status name given to a code.
     *
     * This is intended to be human-readable
     * and will be translated, so never use it for tests.
     * If status code does not exist, returns <i>offline</i>.
     *
     * @param      int      $s      Status code
     *
     * @return     string   The blog status name.
     */
    public function getBlogStatus(int $s): string;

    /**
     * Returns all blog permissions (users).
     *
     * Retrun permissions as an array which looks like:
     * - [user_id]
     * - [name] => User name
     * - [firstname] => User firstname
     * - [displayname] => User displayname
     * - [super] => (true|false) super admin
     * - [p]
     * - [permission] => true
     * - ...
     *
     * @param 	string  $id 			The blog identifier
     * @param 	bool 	$with_super 	Includes super admins in result
     *
     * @return 	array 	The blog permissions.
     */
    public function getBlogPermissions(string $id, bool $with_super = true): array;

    /**
     * Gets the blog.
     *
     * Since 2.28 this method only returns MetaRecord
     *
     * @param      string  $id     The blog identifier
     *
     * @return     MetaRecord 	The blog.
     */
    public function getBlog(string $id): MetaRecord;

    /**
     * Returns a MetaRecord of blogs.
     *
     * <b>$params</b> is an array with the following optionnal parameters:
     * - <var>blog_id</var>: Blog ID
     * - <var>q</var>: Search string on blog_id, blog_name and blog_url
     * - <var>limit</var>: limit results
     *
     * @param 	array|ArrayObject 	$params 		The parameters
     * @param 	bool 				$count_only 	Count only results
     *
     * @return 	MetaRecord 	The blogs.
     */
    public function getBlogs(array|ArrayObject $params = [], bool $count_only = false): MetaRecord;

    /**
     * Adds a new blog.
     *
     * @param 	Cursor 	$cur 	The blog Cursor
     *
     * @throws 	Exception
     */
    public function addBlog(Cursor $cur): void;

    /**
     * Updates a given blog.
     *
     * @param 	string 	$id     The blog identifier
     * @param 	Cursor 	$cur 	The Cursor
     */
    public function updBlog(string $id, Cursor $cur): void;

    /**
     * Removes a given blog.
     *
     * @warning This will remove everything related to the blog (posts,
     * categories, comments, links...)
     *
     * @param 	string 	$id 	The blog identifier
     *
     * @throws 	Exception
     */
    public function delBlog(string $id): void;

    /**
     * Determines if blog exists.
     *
     * @param 	string 	$id 	The blog identifier
     *
     * @return 	bool 	True if blog exists, False otherwise.
     */
    public function blogExists(string $id): bool;

    /**
     * Counts the number of blog posts.
     *
     * @param 	string 			$id 	The blog identifier
     * @param 	null|string 	$type 	The post type
     *
     * @return 	int 	Number of blog posts.
     */
    public function countBlogPosts(string $id, ?string $type = null): int;
}
