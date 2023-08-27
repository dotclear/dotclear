<?php
/**
 * Blogs handler.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use dcAuth;
use dcBlog;
use dcCore;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Exception;

class Blogs
{
    private dcAuth $auth;
    private AbstractHandler $con;

    /**
     * Constructor grabs all we need.
     */
    public function __construct()
    {
        $this->auth = dcCore::app()->auth;
        $this->con  = dcCore::app()->con;
    }

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
    public function getBlogPermissions(string $id, bool $with_super = true): array
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                'U.user_id as user_id',
                'user_super',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
                'permissions',
            ])
            ->from($sql->as($this->con->prefix() . dcAuth::USER_TABLE_NAME, 'U'))
            ->join((new JoinStatement())
                ->from($sql->as($this->con->prefix() . dcAuth::PERMISSIONS_TABLE_NAME, 'P'))
                ->on('U.user_id = P.user_id')
                ->statement())
            ->where('blog_id = ' . $sql->quote($id));

        if ($with_super) {
            $sql->union(
                (new SelectStatement())
                ->columns([
                    'U.user_id as user_id',
                    'user_super',
                    'user_name',
                    'user_firstname',
                    'user_displayname',
                    'user_email',
                    'NULL AS permissions',
                ])
                ->from($sql->as($this->con->prefix() . dcAuth::USER_TABLE_NAME, 'U'))
                ->where('user_super = 1')
                ->statement()
            );
        }

        $rs = $sql->select();

        $res = [];

        while ($rs->fetch()) {
            $res[$rs->user_id] = [
                'name'        => $rs->user_name,
                'firstname'   => $rs->user_firstname,
                'displayname' => $rs->user_displayname,
                'email'       => $rs->user_email,
                'super'       => (bool) $rs->user_super,
                'p'           => $this->auth->parsePermissions($rs->permissions),
            ];
        }

        return $res;
    }

    /**
     * Gets the blog.
     *
     * Since 2.28 this method only returns MetaRecord
     *
     * @param      string  $id     The blog identifier
     *
     * @return     MetaRecord 	The blog.
     */
    public function getBlog(string $id): MetaRecord
    {
        return $this->getBlogs(['blog_id' => $id]);
    }

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
    public function getBlogs($params = [], bool $count_only = false): MetaRecord
    {
        $join  = ''; // %1$s
        $where = ''; // %2$s

        if ($count_only) {
            $strReq = 'SELECT count(B.blog_id) ' .
            'FROM ' . $this->con->prefix() . dcBlog::BLOG_TABLE_NAME . ' B ' .
                '%1$s ' .
                'WHERE NULL IS NULL ' .
                '%2$s ';
        } else {
            $strReq = 'SELECT B.blog_id, blog_uid, blog_url, blog_name, blog_desc, blog_creadt, ' .
                'blog_upddt, blog_status ';
            if (!empty($params['columns'])) {
                $strReq .= ',';
                if (is_array($params['columns'])) {
                    $strReq .= implode(',', $params['columns']);
                } else {
                    $strReq .= $params['columns'];
                }
                $strReq .= ' ';
            }
            $strReq .= 'FROM ' . $this->con->prefix() . dcBlog::BLOG_TABLE_NAME . ' B ' .
                '%1$s ' .
                'WHERE NULL IS NULL ' .
                '%2$s ';

            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . $this->con->escape($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY B.blog_id ASC ';
            }

            if (!empty($params['limit'])) {
                $strReq .= $this->con->limit($params['limit']);
            }
        }

        if ($this->auth->userID() && !$this->auth->isSuperAdmin()) {
            $join  = 'INNER JOIN ' . $this->con->prefix() . dcAuth::PERMISSIONS_TABLE_NAME . ' PE ON B.blog_id = PE.blog_id ';
            $where = "AND PE.user_id = '" . $this->con->escape($this->auth->userID()) . "' " .
                "AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%') " .
                'AND blog_status IN (' . (string) dcBlog::BLOG_ONLINE . ',' . (string) dcBlog::BLOG_OFFLINE . ') ';
        } elseif (!$this->auth->userID()) {
            $where = 'AND blog_status IN (' . (string) dcBlog::BLOG_ONLINE . ',' . (string) dcBlog::BLOG_OFFLINE . ') ';
        }

        if (isset($params['blog_status']) && $params['blog_status'] !== '' && $this->auth->isSuperAdmin()) {
            $where .= 'AND blog_status = ' . (int) $params['blog_status'] . ' ';
        }

        if (isset($params['blog_id']) && $params['blog_id'] !== '') {
            if (!is_array($params['blog_id'])) {
                $params['blog_id'] = [$params['blog_id']];
            }
            $where .= 'AND B.blog_id ' . $this->con->in($params['blog_id']);
        }

        if (!empty($params['q'])) {
            $params['q'] = strtolower(str_replace('*', '%', $params['q']));
            $where .= 'AND (' .
            "LOWER(B.blog_id) LIKE '" . $this->con->escape($params['q']) . "' " .
            "OR LOWER(B.blog_name) LIKE '" . $this->con->escape($params['q']) . "' " .
            "OR LOWER(B.blog_url) LIKE '" . $this->con->escape($params['q']) . "' " .
                ') ';
        }

        $strReq = sprintf($strReq, $join, $where);

        return new MetaRecord($this->con->select($strReq));
    }

    /**
     * Adds a new blog.
     *
     * @param 	Cursor 	$cur 	The blog Cursor
     *
     * @throws 	Exception
     */
    public function addBlog(Cursor $cur): void
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $this->fillBlogCursor($cur);

        $cur->blog_creadt = date('Y-m-d H:i:s');
        $cur->blog_upddt  = date('Y-m-d H:i:s');
        $cur->blog_uid    = md5(uniqid());

        $cur->insert();
    }

    /**
     * Updates a given blog.
     *
     * @param 	string 	$id     The blog identifier
     * @param 	Cursor 	$cur 	The Cursor
     */
    public function updBlog(string $id, Cursor $cur): void
    {
        $this->fillBlogCursor($cur);

        $cur->blog_upddt = date('Y-m-d H:i:s');

        $cur->update("WHERE blog_id = '" . $this->con->escape($id) . "'");
    }

    /**
     * Fills the blog Cursor.
     *
     * @param 	Cursor 	$cur 	The Cursor
     *
     * @throws 	Exception
     */
    private function fillBlogCursor(Cursor $cur): void
    {
        if (($cur->blog_id !== null
            && !preg_match('/^[A-Za-z0-9._-]{2,}$/', (string) $cur->blog_id)) || (!$cur->blog_id)) {
            throw new Exception(__('Blog ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if (($cur->blog_name !== null && $cur->blog_name == '') || (!$cur->blog_name)) {
            throw new Exception(__('No blog name'));
        }

        if (($cur->blog_url !== null && $cur->blog_url == '') || (!$cur->blog_url)) {
            throw new Exception(__('No blog URL'));
        }
    }

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
    public function delBlog(string $id): void
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $sql = DeleteStatement();
        $sql
            ->from($this->con->prefix() . dcBlog::BLOG_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id))
            ->delete();
    }

    /**
     * Determines if blog exists.
     *
     * @param 	string 	$id 	The blog identifier
     *
     * @return 	bool 	True if blog exists, False otherwise.
     */
    public function blogExists(string $id): bool
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->column('blog_id')
            ->from($this->con->prefix() . dcBlog::BLOG_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id))
            ->select();

        return !$rs->isEmpty();
    }

    /**
     * Counts the number of blog posts.
     *
     * @param 	string 			$id 	The blog identifier
     * @param 	null|string 	$type 	The post type
     *
     * @return 	int 	Number of blog posts.
     */
    public function countBlogPosts(string $id, ?string $type = null): int
    {
        $sql = new SelectStatement();
        $sql
            ->column($sql->count('post_id'))
            ->from($this->con->prefix() . dcBlog::POST_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id));

        if ($type) {
            $sql->and('post_type = ' . $sql->quote($type));
        }

        return (int) $sql->select()->f(0);
    }
}
