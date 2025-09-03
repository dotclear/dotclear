<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Session Handler interface.
 *
 * @since   2.28
 */
interface SessionInterface
{
    /**
     * The Session database table name.
     *
     * @var     string  SESSION_TABLE_NAME
     */
    public const SESSION_TABLE_NAME = 'session';

    /**
     * Configure session cookie.
     *
     * This MUST be done before session starts.
     *
     * @throws  \Dotclear\Exception\SessionException     if session is already configured
     *
     * @param   string          $cookie_name    Cookie name
     * @param   null|string     $cookie_path    Cookie path
     * @param   null|string     $cookie_domain  Cookie domain
     * @param   bool            $cookie_secure  Cookie secure
     * @param   null|string     $ttl            The ttl
     */
    public function configure(string $cookie_name, ?string $cookie_path = null, ?string $cookie_domain = null, bool $cookie_secure = false, ?string $ttl = null): void;

    /**
     * Destructor.
     *
     * This method calls session_write_close PHP function.
     */
    public function __destruct();

    /**
     * Session Start.
     *
     * @throws  \Dotclear\Exception\SessionException     if session is not configured
     */
    public function start(): void;

    /**
     * Session Destroy.
     *
     * This method destroies all session data and removes cookie.
     */
    public function destroy(): void;

    /**
     * Session Cookie.
     *
     * This method returns an array of all session cookie parameters:
     * <ul>
     * <li>(string) session name,</li>
     * <li>(string) session value,</li>
     * <li>(int) session expire,</li>
     * <li>(string) cookie path,</li>
     * <li>(string) cookie domain,</li>
     * <li>(bool) cookie secure,</li>
     * </ul>
     *
     * @param   mixed   $value      Cookie value
     * @param   int     $expire     Cookie expiration timestamp
     *
     * @return  list{0:string,1:string,2:int,3:string,4:string,5:bool}
     */
    public function getCookieParameters($value = null, int $expire = 0): array;

    /**
     * Check if sessions are active and a session exists.
     *
     * This does not mean the active session is from this instance.
     *
     * @return  bool    True if there is an actve session
     */
    public function exists(): bool;

    /**
     * Set a value to session.
     *
     * To set a session value to null is equal to unset it.
     *
     * @param   string  $key    The key
     * @param   mixed   $value  The value
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get a value from session.
     *
     * @param   string  $key    The key
     *
     * @return  mixed   The value or null if not set
     */
    public function get(string $key): mixed;

    /**
     * Unset values from session.
     *
     * @param   string  $keys   The keys
     */
    public function unset(...$keys): void;
}
