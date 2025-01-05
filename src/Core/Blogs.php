<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use Dotclear\App;
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
use Dotclear\Interface\Core\DeprecatedInterface;

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
     * @param   BlogInterface           $blog           The blog instance
     * @param   ConnectionInterface     $con            The database connection instance
     * @param   DeprecatedInterface     $deprecated     The database connection instance
     */
    public function __construct(
        protected BlogInterface $blog,
        protected ConnectionInterface $con,
        protected DeprecatedInterface $deprecated
    ) {
    }

    /**
     * @deprecated  since 2.33, use App::status()->blog()->statuses() instead
     */
    public function getAllBlogStatus(): array
    {
        $this->deprecated->set('App::status()->blog()->statuses()', '2.33');

        return App::status()->blog()->statuses();
    }

    /**
     * @deprecated  since 2.33, use App::status()->blog()->name($s) instead
     */
    public function getBlogStatus(int $s): string
    {
        $this->deprecated->set('App::status()->blog()->status($s)', '2.33');

        return App::status()->blog()->name($s);
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

        if ($rs instanceof MetaRecord) {
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
        $sql = new SelectStatement();

        if ($count_only) {
            $sql
                ->column($sql->count('B.blog_id'))
                ->from($sql->as($this->con->prefix() . $this->blog::BLOG_TABLE_NAME, 'B'))
                ->where('NULL IS NULL')
            ;
        } else {
            $sql
                ->columns([
                    'B.blog_id',
                    'blog_uid',
                    'blog_url',
                    'blog_name',
                    'blog_desc',
                    'blog_creadt',
                    'blog_upddt',
                    'blog_status',
                ])
                ->from($sql->as($this->con->prefix() . $this->blog::BLOG_TABLE_NAME, 'B'))
                ->where('NULL IS NULL')
            ;

            if (!empty($params['columns'])) {
                $sql->columns($params['columns']);
            }

            $sql->order(empty($params['order']) ? 'B.blog_id ASC' : $sql->escape($params['order']));

            if (!empty($params['limit'])) {
                $sql->limit($params['limit']);
            }
        }

        if ($this->blog->auth()->userID() && !$this->blog->auth()->isSuperAdmin()) {
            $sql
                ->join(
                    (new JoinStatement())
                        ->inner()
                        ->from($sql->as($this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME, 'PE'))
                        ->on('B.blog_id = PE.blog_id')
                        ->statement()
                )
                ->and('PE.user_id = ' . $sql->quote($this->blog->auth()->userID()))
                ->and($sql->orGroup([
                    $sql->like('permissions', '%|' . $this->blog->auth()::PERMISSION_USAGE . '|%'),
                    $sql->like('permissions', '%|' . $this->blog->auth()::PERMISSION_ADMIN . '|%'),
                    $sql->like('permissions', '%|' . $this->blog->auth()::PERMISSION_CONTENT_ADMIN . '|%'),
                ]))
                ->and('blog_status >= ' . App::status()->blog()->limit())
            ;
        } elseif (!$this->blog->auth()->userID()) {
            $sql->and('blog_status >= ' . App::status()->blog()->limit());
        }

        if (isset($params['blog_status']) && $params['blog_status'] !== '' && $this->blog->auth()->isSuperAdmin()) {
            $sql->and('blog_status = ' . (int) $params['blog_status']);
        }

        if (isset($params['blog_id']) && $params['blog_id'] !== '') {
            $sql->and('B.blog_id' . $sql->in($params['blog_id']));
        }

        if (!empty($params['q'])) {
            $params['q'] = strtolower((string) str_replace('*', '%', $params['q']));    // @phpstan-ignore-line
            $sql->and($sql->orGroup([
                $sql->like('LOWER(B.blog_id)', $sql->escape($params['q'])),
                $sql->like('LOWER(B.blog_name)', $sql->escape($params['q'])),
                $sql->like('LOWER(B.blog_url)', $sql->escape($params['q'])),
            ]));
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
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

        $cur->update("WHERE blog_id = '" . $this->con->escapeStr($id) . "'");
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

        return $rs instanceof MetaRecord && !$rs->isEmpty();
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
