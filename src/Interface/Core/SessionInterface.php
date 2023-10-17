<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
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
     * Destructor
     *
     * This method calls session_write_close PHP function.
     */
    public function __destruct();

    /**
     * Session Start
     */
    public function start(): void;

    /**
     * Session Destroy
     *
     * This method destroies all session data and removes cookie.
     */
    public function destroy(): void;

    /**
     * Create a new session instance with a given cookie name.
     *
     * This does not overwrite current session instance.
     *
     * @param   string  $cookie_name    The cookie name
     *
     * @return  SessionInterface
     */
    public function createFromCookieName(string $cookie_name): SessionInterface;

    /**
     * Session Transient
     *
     * This method set the transient flag of the session
     *
     * @param   bool    $transient  Session transient flag
     */
    public function setTransientSession(bool $transient = false): void;

    /**
     * Session Cookie
     *
     * This method returns an array of all session cookie parameters,
     * like :
     * * (string) session name
     * * (string) session value,
     * * (int) session expire,
     * * (string) cookie path,
     * * (string) cookie domain,
     * * (bool) cookie secure,
     *
     * @param   mixed   $value      Cookie value
     * @param   int     $expire     Cookie expiration timestamp
     *
     * @return  array<int,int|string|bool>
     */
    public function getCookieParameters($value = null, int $expire = 0): array;

    /**
     * Session handler callback called on session open
     *
     * @param   string  $path   The save path
     * @param   string  $name   The session name
     *
     * @return  bool
     */
    public function _open(string $path, string $name): bool;

    /**
     * Session handler callback called on session close
     *
     * @return  bool
     */
    public function _close(): bool;

    /**
     * Session handler callback called on session read
     *
     * @param   string  $ses_id     The session identifier
     *
     * @return  string
     */
    public function _read(string $ses_id): string;

    /**
     * Session handler callback called on session write
     *
     * @param   string  $ses_id     The session identifier
     * @param   string  $data       The data
     *
     * @return  bool
     */
    public function _write(string $ses_id, string $data): bool;

    /**
     * Session handler callback called on session destroy
     *
     * @param   string  $ses_id     The session identifier
     *
     * @return  bool
     */
    public function _destroy(string $ses_id): bool;

    /**
     * Session handler callback called on session garbage collect
     *
     * @return  bool
     */
    public function _gc(): bool;
}
