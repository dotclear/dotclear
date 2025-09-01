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

use Dotclear\Database\SessionHandler;
use Dotclear\Exception\SessionException;
use Dotclear\Interface\Core\SessionInterface;
use Throwable;

/**
 * @brief   Session handler.
 *
 * Transitionnal class to set Dotclear default session handler table.
 *
 * @since   2.28, container services have been added to constructor
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Session implements SessionInterface
{
    /**
     * The cookie name.
     */
    protected string $cookie_name = '';

    /**
     * The cookie path.
     */
    protected string $cookie_path = '/';

    /**
     * The cookie domain.
     */
    protected string $cookie_domain = '';

    /**
     * The cookie secure.
     */
    protected bool $cookie_secure = false;

    /**
     * The cookie TTL (must be a negative duration as '-120 minutes')
     */
    protected string $ttl = '-120 minutes';

    /**
     * Session handler
     */
    protected SessionHandler $handler;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
        register_shutdown_function(function (): void {
            try {
                if (session_id()) {
                    // Explicitly close session before DB connection
                    session_write_close();
                }
                $this->core->db()->con()->close();
            } catch (Throwable) {
                // Ignore exceptions
            }
        });
    }

    public function configure(string $cookie_name, ?string $cookie_path = null, ?string $cookie_domain = null, bool $cookie_secure = false, ?string $ttl = null): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new SessionException('Session is already configured');
        }

        $this->cookie_name    = $cookie_name;
        $this->cookie_path    = $cookie_path ?? '/';
        $this->cookie_domain  = $cookie_domain ?? '';
        $this->cookie_secure  = $cookie_secure;
        $this->ttl            = $ttl ?? '-120 minutes';

        if (function_exists('ini_set')) {
            @ini_set('session.use_cookies', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('url_rewriter.tags', '');
            @ini_set('session.use_trans_sid', '0');
            @ini_set('session.cookie_path', $this->cookie_path);
            @ini_set('session.cookie_domain', (string) $this->cookie_domain);
            @ini_set('session.cookie_secure', (string) $this->cookie_secure);
        }
    }

    public function __destruct()
    {
        if (isset($_SESSION)) {
            session_write_close();
        }
    }

    public function start(): void
    {
        // We can't set session stuff (handler, id, name) if session already exists
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if ($this->cookie_name === '') {
            throw new SessionException('Session is not configured');
        }

        $this->handler = (new SessionHandler($this->core->db()->con(), $this->core->db()->con()->prefix() . Session::SESSION_TABLE_NAME, $this->ttl));
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

    public function destroy(): void
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
        call_user_func_array('setcookie', $this->getCookieParameters(false, -600));
    }

    public function getCookieParameters($value = null, int $expire = 0): array
    {
        return [
            (string) session_name(),
            (string) $value,
            $expire,
            (string) $this->cookie_path,
            (string) $this->cookie_domain,
            $this->cookie_secure,
        ];
    }
}
