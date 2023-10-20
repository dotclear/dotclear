<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Exception\BadRequestException;
use Dotclear\Exception\UnauthorizedException;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\ConnectionInterface;

/**
 * @brief   Blogs handler.
 *
 * @since   2.28, blogs features have been grouped in this class
 */
class Blogs implements BlogsInterface
{
    /**
     * Constructor.
     *
     * @param   BlogInterface           $blog   The blog instance
     * @param   ConnectionInterface     $con    The database connection instance
     */
    public function __construct(
        protected BlogInterface $blog,
        protected ConnectionInterface $con
    ) {
    }

    public function getAllBlogStatus(): array
    {
        return [
            $this->blog::BLOG_ONLINE  => __('online'),
            $this->blog::BLOG_OFFLINE => __('offline'),
            $this->blog::BLOG_REMOVED => __('removed'),
        ];
    }

    public function getBlogStatus(int $s): string
    {
        $r = $this->getAllBlogStatus();
        if (isset($r[$s])) {
            return $r[$s];
        }

        return $r[0];
    }

    /**
     * Gets the blog permissions.
     *
     * @param      string  $id          The identifier
     * @param      bool    $with_super  The with super
     *
     * @return     array<int|string, array<string, mixed>>   The blog permissions.
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
            ->from($sql->as($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME, 'U'))
            ->join((new JoinStatement())
                ->from($sql->as($this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME, 'P'))
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
                ->from($sql->as($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME, 'U'))
                ->where('user_super = 1')
                ->statement()
            );
        }

        $rs = $sql->select();

        $res = [];

        if ($rs) {
            while ($rs->fetch()) {
                $res[$rs->user_id] = [
                    'name'        => $rs->user_name,
                    'firstname'   => $rs->user_firstname,
                    'displayname' => $rs->user_displayname,
                    'email'       => $rs->user_email,
                    'super'       => (bool) $rs->user_super,
                    'p'           => $this->blog->auth()->parsePermissions($rs->permissions),
                ];
            }
        }

        return $res;
    }

    public function getBlog(string $id): MetaRecord
    {
        return $this->getBlogs(['blog_id' => $id]);
    }

    public function getBlogs(array|ArrayObject $params = [], bool $count_only = false): MetaRecord
    {
        $join  = ''; // %1$s
        $where = ''; // %2$s

        if ($count_only) {
            $strReq = 'SELECT count(B.blog_id) ' .
            'FROM ' . $this->con->prefix() . $this->blog::BLOG_TABLE_NAME . ' B ' .
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
            $strReq .= 'FROM ' . $this->con->prefix() . $this->blog::BLOG_TABLE_NAME . ' B ' .
                '%1$s ' .
                'WHERE NULL IS NULL ' .
                '%2$s ';

            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . $this->con->escape($params['order']) . ' ';    // @phpstan-ignore-line
            } else {
                $strReq .= 'ORDER BY B.blog_id ASC ';
            }

            if (!empty($params['limit'])) {
                $strReq .= $this->con->limit($params['limit']);
            }
        }

        if ($this->blog->auth()->userID() && !$this->blog->auth()->isSuperAdmin()) {
            $join  = 'INNER JOIN ' . $this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME . ' PE ON B.blog_id = PE.blog_id ';
            $where = "AND PE.user_id = '" . $this->con->escape($this->blog->auth()->userID()) . "' " .  // @phpstan-ignore-line
                "AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%') " .
                'AND blog_status IN (' . (string) $this->blog::BLOG_ONLINE . ',' . (string) $this->blog::BLOG_OFFLINE . ') ';
        } elseif (!$this->blog->auth()->userID()) {
            $where = 'AND blog_status IN (' . (string) $this->blog::BLOG_ONLINE . ',' . (string) $this->blog::BLOG_OFFLINE . ') ';
        }

        if (isset($params['blog_status']) && $params['blog_status'] !== '' && $this->blog->auth()->isSuperAdmin()) {
            $where .= 'AND blog_status = ' . (int) $params['blog_status'] . ' ';
        }

        if (isset($params['blog_id']) && $params['blog_id'] !== '') {
            if (!is_array($params['blog_id'])) {
                $params['blog_id'] = [$params['blog_id']];
            }
            $where .= 'AND B.blog_id ' . $this->con->in($params['blog_id']);
        }

        if (!empty($params['q'])) {
            $params['q'] = strtolower((string) str_replace('*', '%', $params['q']));
            $where .= 'AND (' . // @phpstan-ignore-line
            "LOWER(B.blog_id) LIKE '" . $this->con->escape($params['q']) . "' " .
            "OR LOWER(B.blog_name) LIKE '" . $this->con->escape($params['q']) . "' " .
            "OR LOWER(B.blog_url) LIKE '" . $this->con->escape($params['q']) . "' " .
                ') ';
        }

        $strReq = sprintf($strReq, $join, $where);

        return new MetaRecord($this->con->select($strReq));
    }

    public function addBlog(Cursor $cur): void
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        $this->fillBlogCursor($cur);

        $cur->blog_creadt = date('Y-m-d H:i:s');
        $cur->blog_upddt  = date('Y-m-d H:i:s');
        $cur->blog_uid    = md5(uniqid());

        $cur->insert();
    }

    public function updBlog(string $id, Cursor $cur): void
    {
        $this->fillBlogCursor($cur);

        $cur->blog_upddt = date('Y-m-d H:i:s');

        $cur->update("WHERE blog_id = '" . $this->con->escape($id) . "'");  // @phpstan-ignore-line
    }

    /**
     * Clean up blog cursor.
     *
     * @throws  BadRequestException
     *
     * @param   Cursor  $cur    The blog cursor
     */
    private function fillBlogCursor(Cursor $cur): void
    {
        if (($cur->blog_id !== null
            && !preg_match('/^[A-Za-z0-9._-]{2,}$/', (string) $cur->blog_id)) || (!$cur->blog_id)) {
            throw new BadRequestException(__('Blog ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if (($cur->blog_name !== null && $cur->blog_name == '') || (!$cur->blog_name)) {
            throw new BadRequestException(__('No blog name'));
        }

        if (($cur->blog_url !== null && $cur->blog_url == '') || (!$cur->blog_url)) {
            throw new BadRequestException(__('No blog URL'));
        }
    }

    public function delBlog(string $id): void
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->con->prefix() . $this->blog::BLOG_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id))
            ->delete();
    }

    public function blogExists(string $id): bool
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->column('blog_id')
            ->from($this->con->prefix() . $this->blog::BLOG_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id))
            ->select();

        return $rs && !$rs->isEmpty();
    }

    public function countBlogPosts(string $id, ?string $type = null): int
    {
        $sql = new SelectStatement();
        $sql
            ->column($sql->count('post_id'))
            ->from($this->con->prefix() . $this->blog::POST_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id));

        if ($type) {
            $sql->and('post_type = ' . $sql->quote($type));
        }

        return (int) $sql->select()?->f(0);
    }
}
