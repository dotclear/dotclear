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
     * @param   string          $cookie_name    Cookie name
     * @param   null|string     $cookie_path    Cookie path
     * @param   null|string     $cookie_domain  Cookie domain
     * @param   bool            $cookie_secure  Cookie secure
     * @param   null|string     $ttl            The ttl
     *
     * @throws  Dotclear\Exception\SessionException     if session is already configured
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
     * @throws  Dotclear\Exception\SessionException     if session is not configured
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
}
