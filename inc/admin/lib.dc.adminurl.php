<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

/**
@brief URL Handler for admin urls

 */
class dcAdminURL
{
    /** @var dcCore dcCore instance */
    protected $core;
    protected $urls;

    /**
    Inits dcAdminURL object

    @param    core        <b>dcCore</b>        Dotclear core reference
     */
    public function __construct($core)
    {
        $this->core = $core;
        $this->urls = new ArrayObject();
    }

    /**
     * Registers a new url
     * @param  string $name   the url name
     * @param  string $url    url value
     * @param  array  $params query string params (optional)
     */
    public function register($name, $url, $params = array())
    {
        $this->urls[$name] = array('url' => $url, 'qs' => $params);
    }

    /**
     * Registers a new url as a copy of an existing one
     * @param  string $name   url name
     * @param  streing $orig   url to copy information from
     * @param  array  $params extra parameters to add
     * @param  string $newurl new url if different from the original
     */
    public function registercopy($name, $orig, $params = array(), $newurl = '')
    {
        if (!isset($this->urls[$orig])) {
            throw new exception('Unknown URL handler for ' . $orig);
        }
        $url       = $this->urls[$orig];
        $url['qs'] = array_merge($url['qs'], $params);
        if ($newurl != '') {
            $url['url'] = $newurl;
        }
        $this->urls[$name] = $url;
    }

    /**
     * retrieves a URL given its name, and optional parameters
     *
     * @param  string $name      URL Name
     * @param  array  $params    query string parameters, given as an associative array
     * @param  boolean $urlencode set to true if url may not be encoded
     * @param  string $separator separator to use between QS parameters
     * @param  boolean $parametric set to true if url will be used as (s)printf() format.
     * @return string            the forged url
     */
    public function get($name, $params = array(), $separator = '&amp;', $parametric = false)
    {
        if (!isset($this->urls[$name])) {
            throw new exception('Unknown URL handler for ' . $name);
        }
        $url = $this->urls[$name];
        $p   = array_merge($url['qs'], $params);
        $u   = $url['url'];
        if (!empty($p)) {
            $u .= '?' . http_build_query($p, '', $separator);
        }
        if ($parametric) {
            // Dirty hack to get back %[n$]s instead of %25[{0..9}%24]s in URLs used with (s)printf(), as http_build_query urlencode() its result.
            $u = preg_replace('/\%25(([0-9])+?\%24)*?s/', '%$2s', $u);
        }
        return $u;
    }

    /**
     * retrieves a URL given its name, and optional parameters
     *
     * @param  string $name      URL Name
     * @param  array  $params    query string parameters, given as an associative array
     * @param  boolean $urlencode set to true if url may not be encoded
     * @param  string $suffix suffix to be added to the QS parameters
     * @return string            the forged url
     */
    public function redirect($name, $params = array(), $suffix = "")
    {
        if (!isset($this->urls[$name])) {
            throw new exception('Unknown URL handler for ' . $name);
        }
        http::redirect($this->get($name, $params, '&') . $suffix);
    }

    /**
     * retrieves a php page given its name, and optional parameters
     * acts like get, but without the query string, should be used within forms actions
     *
     * @param  string $name      URL Name
     * @return string            the forged url
     */
    public function getBase($name)
    {
        if (!isset($this->urls[$name])) {
            throw new exception('Unknown URL handler for ' . $name);
        }
        return $this->urls[$name]['url'];
    }

    /**
     * forges form hidden fields to pass to a generated <form>. Should be used in combination with
     * form action retrieved from getBase()
     *
     * @param  string $name      URL Name
     * @param  array  $params    query string parameters, given as an associative array
     * @return string            the forged form data
     */
    public function getHiddenFormFields($name, $params = array())
    {
        if (!isset($this->urls[$name])) {
            throw new exception('Unknown URL handler for ' . $name);
        }
        $url = $this->urls[$name];
        $p   = array_merge($url['qs'], $params);
        $str = '';
        foreach ((array) $p as $k => $v) {
            $str .= form::hidden(array($k), $v);
        }
        return $str;
    }

    /**
     * retrieves a URL (decoded — useful for echoing) given its name, and optional parameters
     *
     * @deprecated     should be used carefully, parameters are no more escaped
     *
     * @param  string $name      URL Name
     * @param  array  $params    query string parameters, given as an associative array
     * @param  string $separator separator to use between QS parameters
     * @return string            the forged decoded url
     */
    public function decode($name, $params = array(), $separator = '&')
    {
        return urldecode($this->get($name, $params, false, $separator));
    }

    /**
     * Returns $urls property content.
     *
     * @return  ArrayObject
     */
    public function dumpUrls()
    {
        return $this->urls;
    }
}
