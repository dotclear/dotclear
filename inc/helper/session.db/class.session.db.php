<?php
/**
 * @class sessionDB
 * @brief Database Session Handler
 *
 * This class allows you to handle session data in database.
 *
 * @package Clearbricks
 * @subpackage Session
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class sessionDB
{
    /**
     * dbLayer handler
     *
     * @var dbLayer
     */
    private $con;

    /**
     * Table name
     *
     * @var string
     */
    private $table;

    /**
     * Cookie name
     *
     * @var string
     */
    private $cookie_name;

    /**
     * Cookie path
     *
     * @var string|null
     */
    private $cookie_path;

    /**
     * Cookie domain
     *
     * @var string|null
     */
    private $cookie_domain;

    /**
     * Secure cookie
     *
     * @var bool
     */
    private $cookie_secure;

    /**
     * TTL (must be a negative duration as '-120 minutes')
     *
     * @var        string
     */
    private $ttl = '-120 minutes';

    /**
     * Transient session
     *
     * No DB optimize on session destruction if true
     *
     * @var        bool
     */
    private $transient = false;

    /**
     * Constructor
     *
     * This method creates an instance of sessionDB class.
     *
     * @param dbLayer    $con               dbLayer inherited database instance
     * @param string     $table             Table name
     * @param string     $cookie_name       Session cookie name
     * @param string     $cookie_path       Session cookie path
     * @param string     $cookie_domain     Session cookie domaine
     * @param bool       $cookie_secure     Session cookie is available only through SSL if true
     * @param string     $ttl               TTL (default -120 minutes)
     * @param bool       $transient         Transient session : no db optimize on session destruction if true
     */
    public function __construct(
        dbLayer $con,
        string $table,
        string $cookie_name,
        ?string $cookie_path = null,
        ?string $cookie_domain = null,
        bool $cookie_secure = false,
        ?string $ttl = null,
        bool $transient = false
    ) {
        $this->con           = &$con;
        $this->table         = $table;
        $this->cookie_name   = $cookie_name;
        $this->cookie_path   = is_null($cookie_path) ? '/' : $cookie_path;
        $this->cookie_domain = $cookie_domain;
        $this->cookie_secure = $cookie_secure;
        if (!is_null($ttl)) {
            $this->ttl = $ttl;
        }
        $this->transient = $transient;

        if (function_exists('ini_set')) {
            @ini_set('session.use_cookies', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('url_rewriter.tags', '');
            @ini_set('session.use_trans_sid', '0');
            @ini_set('session.cookie_path', $this->cookie_path);
            @ini_set('session.cookie_domain', $this->cookie_domain);
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

    /**
     * Session Start
     */
    public function start(): void
    {
        session_set_save_handler(
            [$this, '_open'],
            [$this, '_close'],
            [$this, '_read'],
            [$this, '_write'],
            [$this, '_destroy'],
            [$this, '_gc']
        );

        if (isset($_SESSION) && session_name() !== $this->cookie_name) {
            $this->destroy();
        }

        if (!isset($_COOKIE[$this->cookie_name])) {
            session_id(sha1(uniqid((string) rand(), true)));
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
     * @param bool     $transient     Session transient flag
     */
    public function setTransientSession(bool $transient = false): void
    {
        $this->transient = $transient;
    }

    /**
     * Session Cookie
     *
     * This method returns an array of all session cookie parameters.
     *
     * @param mixed         $value        Cookie value
     * @param int           $expire       Cookie expiration timestamp
     */
    public function getCookieParameters($value = null, int $expire = 0)
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

    /**
     * Session handler callback called on session open
     *
     * @param      string  $path   The save path
     * @param      string  $name   The session name
     *
     * @return     bool
     */
    public function _open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Session handler callback called on session close
     *
     * @return     bool  ( description_of_the_return_value )
     */
    public function _close(): bool
    {
        $this->_gc();

        return true;
    }

    /**
     * Session handler callback called on session read
     *
     * @param      string  $ses_id  The session identifier
     *
     * @return     string
     */
    public function _read(string $ses_id): string
    {
        $strReq = 'SELECT ses_value FROM ' . $this->table . ' ' .
        'WHERE ses_id = \'' . $this->checkID($ses_id) . '\' ';

        $rs = $this->con->select($strReq);

        if ($rs->isEmpty()) {
            return '';
        }

        return $rs->f('ses_value');
    }

    /**
     * Session handler callback called on session write
     *
     * @param      string  $ses_id  The session identifier
     * @param      string  $data    The data
     *
     * @return     bool
     */
    public function _write(string $ses_id, string $data): bool
    {
        $strReq = 'SELECT ses_id ' .
        'FROM ' . $this->table . ' ' .
        "WHERE ses_id = '" . $this->checkID($ses_id) . "' ";

        $rs = $this->con->select($strReq);

        $cur            = $this->con->openCursor($this->table);
        $cur->ses_time  = (string) time();
        $cur->ses_value = (string) $data;

        if (!$rs->isEmpty()) {
            $cur->update("WHERE ses_id = '" . $this->checkID($ses_id) . "' ");
        } else {
            $cur->ses_id    = $this->checkID($ses_id);
            $cur->ses_start = (string) time();

            $cur->insert();
        }

        return true;
    }

    /**
     * Session handler callback called on session destroy
     *
     * @param      string  $ses_id  The session identifier
     *
     * @return     bool
     */
    public function _destroy(string $ses_id): bool
    {
        $strReq = 'DELETE FROM ' . $this->table . ' ' .
        'WHERE ses_id = \'' . $this->checkID($ses_id) . '\' ';

        $this->con->execute($strReq);

        if (!$this->transient) {
            $this->_optimize();
        }

        return true;
    }

    /**
     * Session handler callback called on session garbage collect
     *
     * @return     bool
     */
    public function _gc(): bool
    {
        $ses_life = strtotime($this->ttl);

        $strReq = 'DELETE FROM ' . $this->table . ' ' .
            'WHERE ses_time < ' . $ses_life . ' ';

        $this->con->execute($strReq);

        if ($this->con->changes() > 0) {
            $this->_optimize();
        }

        return true;
    }

    /**
     * Optimize the session table
     */
    private function _optimize(): void
    {
        $this->con->vacuum($this->table);
    }

    /**
     * Check a session id
     *
     * @param      string  $id     The identifier
     *
     * @return     string
     */
    private function checkID(string $id)
    {
        return preg_match('/^([0-9a-f]{40})$/i', $id) ? $id : '';
    }
}
