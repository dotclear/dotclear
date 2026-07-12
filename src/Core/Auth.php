<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Crypt;
use Dotclear\Helper\Network\Http;
use Dotclear\Exception\BadRequestException;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\UserPreferencesInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Dotclear\Schema\Extension\User;
use Throwable;

/**
 * @brief   Authentication handler.
 *
 * Auth is a class used to handle everything related to user authentication
 * and credentials. Object is provided by App::auth() method.
 *
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Auth implements AuthInterface
{
    /**
     * User table name.
     */
    protected string $user_table;

    /**
     * Perm table name.
     */
    protected string $perm_table;

    /**
     * Current user ID.
     */
    protected string $user_id = '';

    /**
     * Array with user information.
     *
     * @var     array<string, mixed>   $user_info
     */
    protected array $user_info = [];

    /**
     * Array with user options.
     *
     * @var array{
     *      edit_size: int,
     *      post_format: string,
     *      editor: array<string, string>,
     *      enable_wysiwyg: bool,
     *      toolbar_bottom: bool,
     *      ...<string, mixed>
     * }    $user_options
     */
    protected array $user_options;

    /**
     * User must change his password after login.
     */
    protected bool $user_change_pwd;

    /**
     * User is super admin.
     */
    protected bool $user_admin = false;

    /**
     * User can change its password.
     */
    protected bool $allow_pass_change = true;

    /**
     * List of blogs on which the user has permissions.
     *
     * @since   2.28, as $user_blogs, before as $blogs
     *
     * @var     array<string, false|array<string, bool>>   $user_blogs
     */
    protected array $user_blogs = [];

    /**
     * Count of user blogs.
     */
    protected ?int $blog_count = null;

    /**
     * Permission types.
     *
     * @var     array<string, string>   $perm_types
     */
    protected array $perm_types;

    /**
     * User preferences.
     *
     * @derprecated  since 2.38, use App::auth()->prefs()
     */
    public UserpreferencesInterface $user_prefs;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
        $this->user_prefs = $this->core->userPreferences();
        $this->user_table = $this->core->db()->con()->prefix() . self::USER_TABLE_NAME;
        $this->perm_table = $this->core->db()->con()->prefix() . self::PERMISSIONS_TABLE_NAME;

        $this->perm_types = [
            self::PERMISSION_ADMIN         => __('administrator'),
            self::PERMISSION_CONTENT_ADMIN => __('manage all entries and comments'),
            self::PERMISSION_USAGE         => __('manage their own entries and comments'),
            self::PERMISSION_PUBLISH       => __('publish entries and comments'),
            self::PERMISSION_DELETE        => __('delete entries and comments'),
            self::PERMISSION_CATEGORIES    => __('manage categories'),
            self::PERMISSION_MEDIA_ADMIN   => __('manage all media items'),
            self::PERMISSION_MEDIA         => __('manage their own media items'),
        ];
    }

    public function openUserCursor(): Cursor
    {
        return $this->core->db()->con()->openCursor($this->user_table);
    }

    public function openPermCursor(): Cursor
    {
        return $this->core->db()->con()->openCursor($this->perm_table);
    }

    /// @name Credentials and user permissions
    ///@{

    public function checkUser(string $user_id, ?string $pwd = null, ?string $user_key = null, bool $check_blog = true): bool
    {
        # Check user and password
        $sql = new SelectStatement();
        $sql
            ->columns([
                'user_id',
                'user_super',
                'user_status',
                'user_pwd',
                'user_change_pwd',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
                'user_url',
                'user_default_blog',
                'user_options',
                'user_lang',
                'user_tz',
                'user_post_status',
                'user_creadt',
                'user_upddt',
            ])
            ->from($this->user_table)
            ->where('user_id = ' . $sql->quote($user_id));

        try {
            $rs = $sql->select();
        } catch (Throwable) {
            return false;
        }

        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            // Avoid time attacks by measuring server response time during user existence check
            sleep(random_int(2, 5));

            return false;
        }

        $rs->extend(User::class);

        if (is_string($pwd) && $pwd !== '') {
            $rehash = false;
            if (password_verify($pwd, $rs->strField('user_pwd'))) {
                // User password ok
                if (password_needs_rehash($rs->strField('user_pwd'), PASSWORD_DEFAULT)) {
                    $rs->set('user_pwd', $this->crypt($pwd));
                    $rehash = true;
                }
            } else {
                // Check if pwd still stored in old fashion way
                $ret = password_get_info($rs->strField('user_pwd'));
                if (isset($ret['algo']) && $ret['algo'] == 0) {
                    // hash not done with password_hash() function, check by old fashion way
                    if (Crypt::hmac($this->core->config()->masterKey(), $pwd, $this->core->config()->cryptAlgo()) === $rs->strField('user_pwd')) {
                        // Password Ok, need to store it in new fashion way
                        $rs->set('user_pwd', $this->crypt($pwd));
                        $rehash = true;
                    } else {
                        // Password KO
                        sleep(random_int(2, 5));

                        return false;
                    }
                } else {
                    // Password KO
                    sleep(random_int(2, 5));

                    return false;
                }
            }

            if ($rehash) {
                // Store new hash in DB
                $cur           = $this->openUserCursor();
                $cur->user_pwd = $rs->strField('user_pwd');

                $sql = new UpdateStatement();
                $sql->where('user_id = ' . $sql->quote($rs->strField('user_id')));

                $sql->update($cur);
            }
        } elseif (is_string($user_key) && $user_key !== '') {
            // Avoid time attacks by measuring server response time during comparison
            if (!hash_equals(Http::browserUID($this->core->config()->masterKey() . $rs->strField('user_id') . $this->cryptLegacy($rs->strField('user_id'))), $user_key)) {
                return false;
            }
        }

        $this->user_id         = $rs->strField('user_id');
        $this->user_change_pwd = $rs->boolField('user_change_pwd');
        $this->user_admin      = $rs->boolField('user_super');

        $this->user_info['user_status']       = $rs->intField('user_status', true);
        $this->user_info['user_pwd']          = $rs->strField('user_pwd', true);
        $this->user_info['user_name']         = $rs->strField('user_name', true);
        $this->user_info['user_firstname']    = $rs->strField('user_firstname', true);
        $this->user_info['user_displayname']  = $rs->strField('user_displayname', true);
        $this->user_info['user_email']        = $rs->strField('user_email', true);
        $this->user_info['user_url']          = $rs->strField('user_url', true);
        $this->user_info['user_default_blog'] = $rs->strField('user_default_blog', true);
        $this->user_info['user_lang']         = $rs->strField('user_lang', true);
        $this->user_info['user_tz']           = $rs->strField('user_tz', true);
        $this->user_info['user_post_status']  = $rs->intField('user_post_status', true);
        $this->user_info['user_creadt']       = $rs->strField('user_creadt', true);
        $this->user_info['user_upddt']        = $rs->strField('user_upddt', true);

        $this->user_info['user_cn'] = $this->core->users()->getUserCN(
            $rs->strField('user_id'),
            $rs->strField('user_name', true),
            $rs->strField('user_firstname', true),
            $rs->strField('user_displayname', true)
        );

        /**
         * @var array{
         *      edit_size: int,
         *      post_format: string,
         *      editor: array<string, string>,
         *      enable_wysiwyg: bool,
         *      toolbar_bottom: bool,
         *      ...
         * }    $options
         */
        $options            = $rs->options();
        $this->user_options = array_merge($this->core->users()->userDefaults(), $options);

        $this->user_prefs = $this->user_prefs->createFromUser($this->userID());

        /*
         * Migrate user_options in user preferences if necessary
         */
        if (!$this->user_prefs->get('interface')->prefExists('edit_size')) {
            $this->user_prefs->get('interface')->put(
                'edit_size',
                $this->user_options['edit_size'],
                UserWorkspaceInterface::WS_INT,
                'Number of rows in textarea'
            );
        }

        if (!$this->user_prefs->get('interface')->prefExists('post_format')) {
            $this->user_prefs->get('interface')->put(
                'post_format',
                $this->user_options['post_format'],
                UserWorkspaceInterface::WS_STRING,
                'Post default format'
            );
        }

        if (!$this->user_prefs->get('interface')->prefExists('editor')) {
            $this->user_prefs->get('interface')->put(
                'editor',
                $this->user_options['editor'],
                UserWorkspaceInterface::WS_ARRAY,
                'Editors by format'
            );
        }

        if (!$this->user_prefs->get('interface')->prefExists('enable_wysiwyg')) {
            $this->user_prefs->get('interface')->put(
                'enable_wysiwyg',
                $this->user_options['enable_wysiwyg'],
                UserWorkspaceInterface::WS_BOOL,
                'Enable WYSIWYG mode'
            );
        }

        if (!$this->user_prefs->get('interface')->prefExists('toolbar_bottom')) {
            $this->user_prefs->get('interface')->put(
                'toolbar_bottom',
                $this->user_options['toolbar_bottom'],
                UserWorkspaceInterface::WS_BOOL,
                'Display editor toolbar at bottom of textarea (if possible)'
            );
        }

        $this->user_blogs = [];

        # Get permissions on blogs
        return !$check_blog || $this->findUserBlog() !== false;
    }

    public function crypt(string $pwd): string
    {
        return password_hash($pwd, PASSWORD_DEFAULT);
    }

    public function cryptLegacy(string $pwd): string
    {
        return Crypt::hmac($this->core->config()->masterKey(), $pwd, $this->core->config()->cryptAlgo());
    }

    public function checkPassword(string $pwd): bool
    {
        if (!empty($this->user_info['user_pwd']) && is_string($this->user_info['user_pwd'])) {
            return password_verify($pwd, $this->user_info['user_pwd']);
        }

        return false;
    }

    public function sessionExists(): bool
    {
        return isset($_COOKIE[$this->core->config()->sessionName()]);
    }

    public function checkSession(?string $uid = null): bool
    {
        $welcome = true;

        $sess_user_id = is_string($sess_user_id = $this->core->session()->get('sess_user_id')) ? $sess_user_id : '';
        if ($sess_user_id === '') {
            // If session does not exist, logout.
            $welcome = false;
        } else {
            // Check here for user and IP address
            $this->checkUser($sess_user_id);
            $uid = $uid ?: Http::browserUID($this->core->config()->masterKey());

            if (!$this->userID() || ($uid !== $this->core->session()->get('sess_browser_uid'))) {
                $welcome = false;
            }
        }

        return $welcome;
    }

    public function mustChangePassword(): bool
    {
        return $this->user_change_pwd;
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_admin;
    }

    public function check(?string $permissions, ?string $blog_id): bool
    {
        if ($this->isSuperAdmin()) {
            // Super admin, everything is allowed
            return true;
        }

        $user_permissions = $this->getPermissions($blog_id);

        if (!$user_permissions) {
            // No permission for this user on given blog
            return false;
        }

        if (isset($user_permissions[self::PERMISSION_ADMIN])) {
            // User has admin permission on given blog
            return true;
        }

        // Check every requested permission
        $permissions = array_map(trim(...), explode(',', (string) $permissions));
        foreach ($permissions as $permission) {
            if (isset($user_permissions[$permission])) {
                // One of the requested permission is granted for this user on given blog
                return true;
            }
        }

        return false;
    }

    public function allowPassChange(): bool
    {
        return $this->allow_pass_change;
    }

    ///@}

    /// @name Sudo
    ///@{

    public function sudo(callable $fn, ...$args)
    {
        if ($this->isSuperAdmin()) {
            $res = $fn(...$args);
        } else {
            // Pretends to be a super admin
            $this->user_admin = true;

            try {
                $res = $fn(...$args);
            } catch (Throwable $e) {
                throw $e;
            } finally {
                // Back to normal user behavior
                $this->user_admin = false;
            }
        }

        return $res;
    }

    ///@}

    /// @name User information and options
    ///@{

    public function prefs(): UserPreferencesInterface
    {
        return $this->user_prefs;
    }

    public function getPermissions(?string $blog_id): false|array
    {
        if (is_null($blog_id)) {
            return false;
        }

        if (isset($this->user_blogs[$blog_id])) {
            return $this->user_blogs[$blog_id];
        }

        if ($this->isSuperAdmin()) {
            // Super admin
            $sql = new SelectStatement();
            $sql
                ->column('blog_id')
                ->from($this->core->db()->con()->prefix() . $this->core->blog()::BLOG_TABLE_NAME)
                ->where('blog_id = ' . $sql->quote($blog_id));

            $rs = $sql->select();

            $this->user_blogs[$blog_id] = !$rs instanceof MetaRecord || $rs->isEmpty() ? false : [self::PERMISSION_ADMIN => true];

            return $this->user_blogs[$blog_id];
        }

        $sql = new SelectStatement();
        $sql
            ->column('permissions')
            ->from($this->perm_table)
            ->where('user_id = ' . $sql->quote($this->userID()))
            ->and('blog_id = ' . $sql->quote($blog_id))
            ->and($sql->isNotNull('permissions'));

        $rs = $sql->select();

        $this->user_blogs[$blog_id] = !$rs instanceof MetaRecord || $rs->isEmpty()
            ? false
            : $this->parsePermissions($rs->strField('permissions'));

        return $this->user_blogs[$blog_id];
    }

    public function getBlogCount(): int
    {
        if ($this->blog_count === null) {
            $this->blog_count = $this->core->blogs()->getBlogs([], true)->cardinal();
        }

        return $this->blog_count;
    }

    public function findUserBlog(?string $blog_id = null, bool $all_status = true): false|string
    {
        if ($blog_id && $this->getPermissions($blog_id) !== false) {
            if ($all_status || $this->isSuperAdmin()) {
                return $blog_id;
            }

            $rs = $this->core->blogs()->getBlog($blog_id);
            if ($rs->count() && !$this->core->status()->blog()->isRestricted($rs->intField('blog_status'))) {
                return $blog_id;
            }
        }

        $sql = new SelectStatement();

        if ($this->isSuperAdmin()) {
            $sql
                ->column('blog_id')
                ->from($this->core->db()->con()->prefix() . $this->core->blog()::BLOG_TABLE_NAME)
                ->order('blog_id ASC')
                ->limit(1);
        } else {
            $sql
                ->column('P.blog_id')
                ->from([
                    $this->perm_table . ' P',
                    $this->core->db()->con()->prefix() . $this->core->blog()::BLOG_TABLE_NAME . ' B',
                ])
                ->where('user_id = ' . $sql->quote($this->userID()))
                ->and('P.blog_id = B.blog_id')
                // from 2.33 each Utility or Process must check user permissions (with Auth::check(), Page::check(), ...)
                ->and($sql->isNotNull('permissions'))
                ->and('blog_status >= ' . $this->core->status()->blog()::OFFLINE)
                ->order('P.blog_id ASC')
                ->limit(1);
        }

        $rs = $sql->select();
        if ($rs instanceof MetaRecord && !$rs->isEmpty()) {
            // Return 1st blog in list
            return $rs->strField('blog_id');
        }

        return false;
    }

    public function userID(): string
    {
        return $this->user_id;
    }

    public function getInfo(string $information)
    {
        return $this->user_info[$information] ?? null;
    }

    public function getOption(string $option)
    {
        return $this->user_options[$option] ?? null;
    }

    public function getOptions(): array
    {
        return $this->user_options;
    }

    ///@}

    /// @name Permissions
    ///@{

    public function parsePermissions(?string $level): array
    {
        $level = (string) preg_replace('/^\|/', '', (string) $level);
        $level = (string) preg_replace('/\|$/', '', (string) $level);

        $res = [];
        foreach (explode('|', $level) as $v) {
            $res[$v] = true;
        }

        return $res;
    }

    public function makePermissions(array $list): string
    {
        return implode(',', $list);
    }

    public function getPermissionsTypes(): array
    {
        return $this->perm_types;
    }

    public function setPermissionType(string $name, string $title): void
    {
        $this->perm_types[$name] = $title;
    }

    ///@}

    /// @name Password recovery
    ///@{

    public function setRecoverKey(string $user_id, string $user_email): string
    {
        $sql = new SelectStatement();
        $sql
            ->column('user_id')
            ->from($this->user_table)
            ->where('user_id = ' . $sql->quote($user_id))
            ->and('user_email = ' . $sql->quote($user_email));

        $rs = $sql->select();

        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            throw new BadRequestException(__('That user does not exist in the database.'));
        }

        $key = md5(uniqid('', true));

        $cur                   = $this->openUserCursor();
        $cur->user_recover_key = $key;

        $sql = new UpdateStatement();
        $sql->where('user_id = ' . $sql->quote($user_id));

        $sql->update($cur);

        return $key;
    }

    public function recoverUserPassword(string $recover_key): array
    {
        $sql = new SelectStatement();
        $sql
            ->columns(['user_id', 'user_email'])
            ->from($this->user_table)
            ->where('user_recover_key = ' . $sql->quote($recover_key));

        $rs = $sql->select();

        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            throw new BadRequestException(__('That key does not exist in the database.'));
        }

        $new_pass = Crypt::createPassword();

        $cur                   = $this->openUserCursor();
        $cur->user_pwd         = $this->crypt($new_pass);
        $cur->user_recover_key = null;
        $cur->user_change_pwd  = 1; // User will have to change this temporary password at next login

        $sql = new UpdateStatement();
        $sql->where('user_recover_key = ' . $sql->quote($recover_key));

        $sql->update($cur);

        return [
            'user_email' => $rs->strField('user_email'),
            'user_id'    => $rs->strField('user_id'),
            'new_pass'   => $new_pass,
        ];
    }

    ///@}
}
