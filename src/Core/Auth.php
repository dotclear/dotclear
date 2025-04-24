<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Crypt;
use Dotclear\Helper\Network\Http;
use Dotclear\Exception\ProcessException;
use Dotclear\Exception\BadRequestException;
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\UserPreferencesInterface;
use Dotclear\Interface\Core\UsersInterface;
use Dotclear\Schema\Extension\User;
use Throwable;

/**
 * @brief   Authentication handler.
 *
 * Auth is a class used to handle everything related to user authentication
 * and credentials. Object is provided by App::auth() method.
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
    protected string $user_id;

    /**
     * Array with user information.
     *
     * @var     array<string, mixed>   $user_info
     */
    protected array $user_info = [];

    /**
     * Array with user options.
     *
     * @var     array<string, mixed>   $user_options
     */
    protected array $user_options = [];

    /**
     * User must change his password after login.
     */
    protected bool $user_change_pwd;

    /**
     * User is super admin.
     */
    protected bool $user_admin;

    /**
     * User can change its password.
     */
    protected bool $allow_pass_change = true;

    /**
     * List of blogs on which the user has permissions.
     *
     * @since   2.28, as $user_blogs, before as $blogs
     *
     * @var     array<string, mixed>   $user_blogs
     */
    protected array $user_blogs = [];

    /**
     * Count of user blogs.
     *
     * @todo    Set Auth::$blog_count as a protected property
     *
     * @deprecated  since 2.??, use App::auth()->getBlogCount() instead
     */
    public ?int $blog_count = null;

    /**
     * Permission types.
     *
     * @var     array<string, string>   $perm_types
     */
    protected array $perm_types;

    /**
     * Class constructor.
     *
     * @param   BlogInterface               $blog           The blog instance
     * @param   BlogsInterface              $blogs          The blogs handler
     * @param   ConnectionInterface         $con            The database connection instance
     * @param   SessionInterface            $session        The session handler
     * @param   UserPreferencesInterface    $user_prefs     The user preferences instance
     * @param   UsersInterface              $users          The users handler
     */
    public function __construct(
        protected BlogInterface $blog,
        protected BlogsInterface $blogs,
        protected ConfigInterface $config,
        protected ConnectionInterface $con,
        protected SessionInterface $session,
        public UserpreferencesInterface $user_prefs,
        protected UsersInterface $users
    ) {
        $this->user_table = $this->con->prefix() . self::USER_TABLE_NAME;
        $this->perm_table = $this->con->prefix() . self::PERMISSIONS_TABLE_NAME;

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

        $this->blog->setAuth($this);
    }

    public function openUserCursor(): Cursor
    {
        return $this->con->openCursor($this->user_table);
    }

    public function openPermCursor(): Cursor
    {
        return $this->con->openCursor($this->perm_table);
    }

    /// @name Credentials and user permissions
    //@{

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
            if (password_verify($pwd, $rs->user_pwd)) {
                // User password ok
                if (password_needs_rehash($rs->user_pwd, PASSWORD_DEFAULT)) {
                    $rs->user_pwd = $this->crypt($pwd);
                    $rehash       = true;
                }
            } else {
                // Check if pwd still stored in old fashion way
                $ret = password_get_info($rs->user_pwd);
                if (isset($ret['algo']) && $ret['algo'] == 0) {
                    // hash not done with password_hash() function, check by old fashion way
                    if (Crypt::hmac($this->config->masterKey(), $pwd, $this->config->cryptAlgo()) == $rs->user_pwd) {
                        // Password Ok, need to store it in new fashion way
                        $rs->user_pwd = $this->crypt($pwd);
                        $rehash       = true;
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
                $cur->user_pwd = (string) $rs->user_pwd;

                $sql = new UpdateStatement();
                $sql->where('user_id = ' . $sql->quote($rs->user_id));

                $sql->update($cur);
            }
        } elseif (is_string($user_key) && $user_key !== '') {
            // Avoid time attacks by measuring server response time during comparison
            if (!hash_equals(Http::browserUID($this->config->masterKey() . $rs->user_id . $this->cryptLegacy($rs->user_id)), $user_key)) {
                return false;
            }
        }

        $this->user_id         = $rs->user_id;
        $this->user_change_pwd = (bool) $rs->user_change_pwd;
        $this->user_admin      = (bool) $rs->user_super;

        $this->user_info['user_status']       = $rs->user_status;
        $this->user_info['user_pwd']          = $rs->user_pwd;
        $this->user_info['user_name']         = $rs->user_name;
        $this->user_info['user_firstname']    = $rs->user_firstname;
        $this->user_info['user_displayname']  = $rs->user_displayname;
        $this->user_info['user_email']        = $rs->user_email;
        $this->user_info['user_url']          = $rs->user_url;
        $this->user_info['user_default_blog'] = $rs->user_default_blog;
        $this->user_info['user_lang']         = $rs->user_lang;
        $this->user_info['user_tz']           = $rs->user_tz;
        $this->user_info['user_post_status']  = $rs->user_post_status;
        $this->user_info['user_creadt']       = $rs->user_creadt;
        $this->user_info['user_upddt']        = $rs->user_upddt;

        $this->user_info['user_cn'] = $this->users->getUserCN(
            $rs->user_id,
            $rs->user_name,
            $rs->user_firstname,
            $rs->user_displayname
        );

        $this->user_options = array_merge($this->users->userDefaults(), $rs->options());

        $this->user_prefs = $this->user_prefs->createFromUser($this->userID());

        $this->user_blogs = [];

        # Get permissions on blogs
        return !($check_blog && $this->findUserBlog() === false);
    }

    public function crypt(string $pwd): string
    {
        return password_hash($pwd, PASSWORD_DEFAULT);
    }

    public function cryptLegacy(string $pwd): string
    {
        return Crypt::hmac($this->config->masterKey(), $pwd, $this->config->cryptAlgo());
    }

    public function checkPassword(string $pwd): bool
    {
        if (!empty($this->user_info['user_pwd'])) {
            return password_verify($pwd, (string) $this->user_info['user_pwd']);
        }

        return false;
    }

    public function sessionExists(): bool
    {
        return isset($_COOKIE[$this->config->sessionName()]);
    }

    public function checkSession(?string $uid = null): bool
    {
        $welcome = true;
        $this->session->start();

        if (!isset($_SESSION['sess_user_id'])) {
            // If session does not exist, logout.
            $welcome = false;
        } else {
            // Check here for user and IP address
            $this->checkUser($_SESSION['sess_user_id']);
            $uid = $uid ?: Http::browserUID($this->config->masterKey());

            if (!$this->userID() || ($uid !== $_SESSION['sess_browser_uid'])) {
                $welcome = false;
            }
        }

        if (!$welcome) {
            $this->session->destroy();
        }

        return $welcome;
    }

    public function mustChangePassword(): bool
    {
        return $this->user_change_pwd;
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_admin ?? false;
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
        $permissions = array_map('trim', explode(',', (string) $permissions));
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

    //@}

    /// @name Sudo
    //@{

    public function sudo($fn, ...$args)
    {
        if (!is_callable($fn)) {    // @phpstan-ignore-line
            throw new ProcessException(print_r($fn, true) . ' function doest not exist');
        }

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

    //@}

    /// @name User information and options
    //@{

    public function prefs(): UserPreferencesInterface
    {
        return $this->user_prefs;
    }

    /**
     * Gets the permissions.
     *
     * @param      null|string  $blog_id  The blog identifier
     *
     * @return  false|array<string, bool>
     */
    public function getPermissions(?string $blog_id)
    {
        if (isset($this->user_blogs[$blog_id])) {
            return $this->user_blogs[$blog_id];
        }

        if ($this->isSuperAdmin()) {
            // Super admin
            $sql = new SelectStatement();
            $sql
                ->column('blog_id')
                ->from($this->con->prefix() . $this->blog::BLOG_TABLE_NAME)
                ->where('blog_id = ' . $sql->quote((string) $blog_id));

            $rs = $sql->select();

            $this->user_blogs[$blog_id] = !$rs instanceof MetaRecord || $rs->isEmpty() ? false : [self::PERMISSION_ADMIN => true];

            return $this->user_blogs[$blog_id];
        }

        $sql = new SelectStatement();
        $sql
            ->column('permissions')
            ->from($this->perm_table)
            ->where('user_id = ' . $sql->quote($this->userID()))
            ->and('blog_id = ' . $sql->quote((string) $blog_id))
            ->and($sql->isNotNull('permissions'));

        $rs                         = $sql->select();
        $this->user_blogs[$blog_id] = !$rs instanceof MetaRecord || $rs->isEmpty() ? false : $this->parsePermissions($rs->permissions);

        return $this->user_blogs[$blog_id];
    }

    public function getBlogCount(): int
    {
        if ($this->blog_count === null) {
            $this->blog_count = (int) $this->blogs->getBlogs([], true)->f(0);
        }

        return $this->blog_count;
    }

    public function findUserBlog(?string $blog_id = null, bool $all_status = true)
    {
        if ($blog_id && $this->getPermissions($blog_id) !== false) {
            if ($all_status || $this->isSuperAdmin()) {
                return $blog_id;
            }
            $rs = $this->blogs->getBlog($blog_id);
            if ($rs->count() && !App::status()->blog()->isRestricted((int) $rs->blog_status)) {
                return $blog_id;
            }
        }

        $sql = new SelectStatement();

        if ($this->isSuperAdmin()) {
            $sql
                ->column('blog_id')
                ->from($this->con->prefix() . $this->blog::BLOG_TABLE_NAME)
                ->order('blog_id ASC')
                ->limit(1);
        } else {
            $sql
                ->column('P.blog_id')
                ->from([
                    $this->perm_table . ' P',
                    $this->con->prefix() . $this->blog::BLOG_TABLE_NAME . ' B',
                ])
                ->where('user_id = ' . $sql->quote($this->userID()))
                ->and('P.blog_id = B.blog_id')
                // from 2.33 each Utility or Process must check user permissions (with Auth::check(), Page::check(), ...)
                ->and($sql->isNotNull('permissions'))
                ->and('blog_status >= ' . App::status()->blog()::OFFLINE)
                ->order('P.blog_id ASC')
                ->limit(1);
        }

        $rs = $sql->select();
        if ($rs instanceof MetaRecord && !$rs->isEmpty()) {
            return $rs->blog_id;
        }

        return false;
    }

    public function userID(): string
    {
        return $this->user_id ?? '';
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
    //@}

    /// @name Permissions
    //@{

    /**
     * Parse user permissions
     *
     * @param      null|string  $level  The level
     *
     * @return     array<string, bool>
     */
    public function parsePermissions($level): array
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

    //@}

    /// @name Password recovery
    //@{

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
            'user_email' => $rs->user_email,
            'user_id'    => $rs->user_id,
            'new_pass'   => $new_pass,
        ];
    }

    //@}
}
