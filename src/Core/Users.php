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
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Exception\BadRequestException;
use Dotclear\Exception\UnauthorizedException;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\UsersInterface;
use Dotclear\Schema\Extension\User;

/**
 * @brief   Users handler.
 *
 * @since   2.28, users features have been grouped in this class
 */
class Users implements UsersInterface
{
    /**
     * Constructor.
     *
     * @param   BehaviorInterface       $behavior   The behavior instance
     * @param   BlogInterface           $blog       The blog instance
     * @param   ConnectionInterface     $con        The database connection instance
     */
    public function __construct(
        protected BehaviorInterface $behavior,
        protected BlogInterface $blog,
        protected ConnectionInterface $con,
    ) {
    }

    public function getUser(string $id): MetaRecord
    {
        $params['user_id'] = $id;

        return $this->getUsers($params);
    }

    public function getUsers(array|ArrayObject $params = [], bool $count_only = false): MetaRecord
    {
        $sql = new SelectStatement();

        if ($count_only) {
            $sql
                ->column($sql->count('U.user_id'))
                ->from($sql->as($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME, 'U'))
                ->where('NULL IS NULL');
        } else {
            $sql
                ->columns([
                    'U.user_id',
                    'user_super',
                    'user_status',
                    'user_pwd',
                    'user_change_pwd',
                    'user_name',
                    'user_firstname',
                    'user_displayname',
                    'user_email',
                    'user_url',
                    'user_desc',
                    'user_lang',
                    'user_tz',
                    'user_post_status',
                    'user_options',
                    $sql->count('P.post_id', 'nb_post'),
                ])
                ->from($sql->as($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME, 'U'));

            if (!empty($params['columns'])) {
                $sql->columns($params['columns']);
            }
            $sql
                ->join(
                    (new JoinStatement())
                        ->left()
                        ->from($sql->as($this->con->prefix() . $this->blog::POST_TABLE_NAME, 'P'))
                        ->on('U.user_id = P.user_id')
                        ->statement()
                )
                ->where('NULL IS NULL');
        }

        if (!empty($params['q'])) {
            $q = $sql->escape(str_replace('*', '%', strtolower((string) $params['q'])));
            $sql->and($sql->orGroup([
                $sql->like('LOWER(U.user_id)', $q),
                $sql->like('LOWER(user_name)', $q),
                $sql->like('LOWER(user_firstname)', $q),
            ]));
        }

        if (!empty($params['user_id'])) {
            $sql->and('U.user_id = ' . $sql->quote($params['user_id']));
        }

        if (!$count_only) {
            $sql->group([
                'U.user_id',
                'user_super',
                'user_status',
                'user_pwd',
                'user_change_pwd',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
                'user_url',
                'user_desc',
                'user_lang',
                'user_tz',
                'user_post_status',
                'user_options',
            ]);

            if (!empty($params['order'])) {
                if (preg_match('`^([^. ]+) (?:asc|desc)`i', (string) $params['order'], $matches)) {
                    if (in_array($matches[1], ['user_id', 'user_name', 'user_firstname', 'user_displayname'])) {
                        $table_prefix = 'U.';
                    } else {
                        $table_prefix = ''; // order = nb_post (asc|desc)
                    }
                    $sql->order($table_prefix . $sql->escape($params['order']));
                } else {
                    $sql->order($sql->escape($params['order']));
                }
            } else {
                $sql->order('U.user_id ASC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        $rs = $sql->select() ?? MetaRecord::newFromArray([]);
        $rs->extend(User::class);

        return $rs;
    }

    public function addUser(Cursor $cur): string
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        if ($cur->user_id == '') {
            throw new BadRequestException(__('No user ID given'));
        }

        if ($cur->user_pwd == '') {
            throw new BadRequestException(__('No password given'));
        }

        $this->fillUserCursor($cur);

        if ($cur->user_creadt === null) {
            $cur->user_creadt = date('Y-m-d H:i:s');
        }

        $cur->insert();

        # --BEHAVIOR-- coreAfterAddUser -- Cursor
        $this->behavior->callBehavior('coreAfterAddUser', $cur);

        return $cur->user_id;
    }

    public function updUser(string $id, Cursor $cur): string
    {
        $this->fillUserCursor($cur);

        if (($cur->user_id !== null || $id != $this->blog->auth()->userID()) && !$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        $sql = new UpdateStatement();
        $sql->where('user_id = ' . $sql->quote($id));

        $sql->update($cur);

        # --BEHAVIOR-- coreAfterUpdUser -- Cursor
        $this->behavior->callBehavior('coreAfterUpdUser', $cur);

        if ($cur->user_id !== null) {
            $id = $cur->user_id;
        }

        # Updating all user's blogs
        $sql = new SelectStatement();
        $sql
            ->distinct()
            ->column('blog_id')
            ->from($this->con->prefix() . $this->blog::POST_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $rs = $sql->select();

        if ($rs) {
            $old_blog = $this->blog->id();
            while ($rs->fetch()) {
                $this->blog->loadFromBlog($rs->blog_id);
                $this->blog->triggerBlog();
            }
            $this->blog->loadFromBlog(empty($old_blog) ? '' : $old_blog);
        }

        return $id;
    }

    public function delUser(string $id): void
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        if ($id == $this->blog->auth()->userID()) {
            return;
        }

        $rs = $this->getUser($id);

        if ((int) $rs->nb_post > 0) {
            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $sql->delete();

        # --BEHAVIOR-- coreAfterDelUser -- string
        $this->behavior->callBehavior('coreAfterDelUser', $id);
    }

    public function userExists(string $id): bool
    {
        $sql = new SelectStatement();
        $sql
            ->column('user_id')
            ->from($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $rs = $sql->select();

        return !$rs || !$rs->isEmpty();
    }

    public function getUserPermissions(string $id): array
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                'B.blog_id',
                'blog_name',
                'blog_url',
                'permissions',
            ])
            ->from($sql->as($this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME, 'P'))
            ->join(
                (new JoinStatement())
                ->inner()
                ->from($sql->as($this->con->prefix() . $this->blog::BLOG_TABLE_NAME, 'B'))
                ->on('P.blog_id = B.blog_id')
                ->statement()
            )
            ->where('user_id = ' . $sql->quote($id));

        $rs = $sql->select();

        $res = [];

        if ($rs) {
            while ($rs->fetch()) {
                $res[(string) $rs->blog_id] = [
                    'name' => $rs->blog_name,
                    'url'  => $rs->blog_url,
                    'p'    => $this->blog->auth()->parsePermissions($rs->permissions),
                ];
            }
        }

        return $res;
    }

    public function setUserPermissions(string $id, array $perms): void
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $sql->delete();

        foreach ($perms as $blog_id => $p) {
            $this->setUserBlogPermissions($id, $blog_id, $p, false);
        }
    }

    public function setUserBlogPermissions(string $id, string $blog_id, array $perms, bool $delete_first = true): void
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        $no_perm = empty($perms);

        $perms = '|' . implode('|', array_keys($perms)) . '|';

        $cur = $this->blog->auth()->openPermCursor();

        $cur->user_id     = $id;
        $cur->blog_id     = $blog_id;
        $cur->permissions = $perms;

        if ($delete_first || $no_perm) {
            $sql = new DeleteStatement();
            $sql
                ->from($this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME)
                ->where('blog_id = ' . $sql->quote($blog_id))
                ->and('user_id = ' . $sql->quote($id));

            $sql->delete();
        }

        if (!$no_perm) {
            $cur->insert();
        }
    }

    public function setUserDefaultBlog(string $id, string $blog_id): void
    {
        $cur = $this->blog->auth()->openUserCursor();

        $cur->user_default_blog = $blog_id;

        $sql = new UpdateStatement();
        $sql->where('user_id = ' . $sql->quote($id));

        $sql->update($cur);
    }

    public function removeUsersDefaultBlogs(array $ids): void
    {
        $cur = $this->blog->auth()->openUserCursor();

        $cur->user_default_blog = null;

        $sql = new UpdateStatement();
        $sql->where('user_default_blog' . $sql->in($ids));

        $sql->update($cur);
    }

    /**
     * Fills the user Cursor.
     *
     * @param      Cursor     $cur    The user Cursor
     *
     * @throws     BadRequestException
     */
    private function fillUserCursor(Cursor $cur): void
    {
        if ($cur->isField('user_id')
            && !preg_match('/^[A-Za-z0-9@._-]{2,}$/', (string) $cur->user_id)) {
            throw new BadRequestException(__('User ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if ($cur->user_url !== null && $cur->user_url != '') {
            if (!preg_match('|^https?://|', (string) $cur->user_url)) {
                $cur->user_url = 'http://' . $cur->user_url;
            }
        }

        if ($cur->isField('user_pwd')) {
            if (strlen($cur->user_pwd) < 6) {
                throw new BadRequestException(__('Password must contain at least 6 characters.'));
            }
            $cur->user_pwd = $this->blog->auth()->crypt($cur->user_pwd);
        }

        if ($cur->user_lang !== null && !preg_match('/^[a-z]{2}(-[a-z]{2})?$/', (string) $cur->user_lang)) {
            throw new BadRequestException(__('Invalid user language code'));
        }

        if ($cur->user_upddt === null) {
            $cur->user_upddt = date('Y-m-d H:i:s');
        }

        if ($cur->user_options !== null) {
            $cur->user_options = serialize((array) $cur->user_options);
        }
    }

    public function userDefaults(): array
    {
        return [
            'edit_size'      => 24,
            'enable_wysiwyg' => true,
            'toolbar_bottom' => false,
            'editor'         => ['xhtml' => 'dcCKEditor', 'wiki' => 'dcLegacyEditor'],
            'post_format'    => 'xhtml',
        ];
    }

    public function getUserCN(string $user_id, ?string $user_name, ?string $user_firstname, ?string $user_displayname): string
    {
        if (!empty($user_displayname)) {
            return $user_displayname;
        }

        if (!empty($user_name)) {
            if (!empty($user_firstname)) {
                return $user_firstname . ' ' . $user_name;
            }

            return $user_name;
        } elseif (!empty($user_firstname)) {
            return $user_firstname;
        }

        return $user_id;
    }
}
