<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\ConnectionInterface;

/**
 * @class Session
 *
 * Database Session Handler
 *
 * This class allows you to handle session data in database.
 */
class Session implements SessionInterface
{
    /**
     * TTL (must be a negative duration as '-120 minutes')
     */
    private string $ttl = '-120 minutes';

    /**
     * Session handler
     */
    protected SessionHandler $handler;

    /**
     * Constructor
     *
     * This method creates an instance of Session class.
     *
     * @param ConnectionInterface   $con               AbstractHandler inherited database instance
     * @param string                $table             Table name
     * @param string                $cookie_name       Session cookie name
     * @param string                $cookie_path       Session cookie path
     * @param string                $cookie_domain     Session cookie domaine
     * @param bool                  $cookie_secure     Session cookie is available only through SSL if true
     * @param string                $ttl               TTL (default -120 minutes)
     */
    public function __construct(
        private ConnectionInterface $con,
        private string $table,
        private string $cookie_name,
        private ?string $cookie_path = null,
        private ?string $cookie_domain = null,
        private bool $cookie_secure = false,
        ?string $ttl = null
    ) {
        $this->cookie_path = is_null($cookie_path) ? '/' : $cookie_path;
        if (!is_null($ttl)) {
            $this->ttl = $ttl;
        }

        if (function_exists('ini_set')) {
            @ini_set('session.use_cookies', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('url_rewriter.tags', '');
            @ini_set('session.use_trans_sid', '0');
            @ini_set('session.cookie_path', (string) $this->cookie_path);
            @ini_set('session.cookie_domain', (string) $this->cookie_domain);
            @ini_set('session.cookie_secure', (string) $this->cookie_secure);
        }
    }

    /**
     * Destructor
     *
     * This method calls session_write_close PHP function.
     */
    public function __destruct()
    {
        if (isset($_SESSION)) {
            session_write_close();
        }
    }

    public function createFromCookieName(string $cookie_name): SessionInterface
    {
        return new self(
            $this->con,
            $this->table,
            $cookie_name,
            $this->cookie_path,
            $this->cookie_domain,
            $this->cookie_secure,
            $this->ttl
        );
    }

    /**
     * Session Start
     */
    public function start(): void
    {
        $this->handler = (new SessionHandler($this->con, $this->table, $this->ttl));
        session_set_save_handler($this->handler);

        if (isset($_SESSION) && session_name() !== $this->cookie_name) {
            $this->destroy();
        }

        if (!isset($_COOKIE[$this->cookie_name])) {
            session_id(sha1(uniqid((string) random_int(0, mt_getrandmax()), true)));
        }

        session_name($this->cookie_name);
        session_start();
    }

    /**
     * Session Destroy
     *
     * This method destroies all session data and removes cookie.
     */
    public function destroy(): void
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
        call_user_func_array('setcookie', $this->getCookieParameters(false, -600));
    }

    /**
     * Session Transient
     *
     * This method set the transient flag of the session
     *
     * @deprecated since 2.32, no more used
     *
     * @param bool     $transient     Session transient flag
     */
    public function setTransientSession(bool $transient = false): void
    {
    }

    /**
     * Session Cookie
     *
     * This method returns an array of all session cookie parameters.
     *
     * @param mixed         $value        Cookie value
     * @param int           $expire       Cookie expiration timestamp
     */
    public function getCookieParameters($value = null, int $expire = 0): array
    {
        return [
            (string) session_name(),
            (string) $value,
            $expire,
            (string) $this->cookie_path,
            (string) $this->cookie_domain,
            (bool) $this->cookie_secure,
        ];
    }
}
