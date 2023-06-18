<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * URL Handler for admin urls
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Network\Http;

class dcAdminURL
{
    /**
     * List of registered admin URLs
     *
     * @var ArrayObject
     */
    protected $urls;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->urls = new ArrayObject();
    }

    /**
     * Registers a new url
     *
     * @param  string $name   the url name
     * @param  string $class  class name (without namespace) or url value
     * @param  array  $params query string params (optional)
     */
    public function register(string $name, string $class, array $params = [])
    {
        // by class name
        if (strpos($class, '.php') === false) {
            $params = array_merge(['process' => $class], $params);
            $class = DC_ADMIN_URL;
        }
        $this->urls[$name] = [
            'url' =>  $class,
            'qs'  => $params,
        ];
    }

    /**
     * Registers a new url as a copy of an existing one
     *
     * @param  string $name   url name
     * @param  string $orig   url to copy information from
     * @param  array  $params extra parameters to add
     * @param  string $newurl new url if different from the original
     */
    public function registercopy(string $name, string $orig, array $params = [], string $newurl = '')
    {
        if (!isset($this->urls[$orig])) {
            throw new Exception('Unknown URL handler for ' . $orig);
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
     * @param      string     $name        The URL name
     * @param      array      $params      The query string parameters (associative array)
     * @param      string     $separator   The separator (used between query string parameters)
     * @param      bool       $parametric  Set to true if url will be used as (s)printf() format
     *
     * @throws     Exception  If unknown URL
     *
     * @return     string     The forged URL
     */
    public function get(string $name, array $params = [], string $separator = '&amp;', bool $parametric = false): string
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }
        $url = $this->urls[$name];
        $qs  = array_merge($url['qs'], $params);
        $url = $url['url'];
        if (!empty($qs)) {
            $url .= '?' . http_build_query($qs, '', $separator);
        }
        if ($parametric) {
            // Dirty hack to get back %[n$]s instead of %25[{0..9}%24]s in URLs used with (s)printf(), as http_build_query urlencode() its result.
            $url = preg_replace('/\%25((\d)+?\%24)*?s/', '%$2s', (string) $url);
        }

        return $url;
    }

    /**
     * Redirect to an URL given its name, and optional parameters
     *
     * @param      string     $name    The name
     * @param      array      $params  The parameters
     * @param      string     $suffix  The suffix
     *
     * @throws     Exception  If unknown URL
     */
    public function redirect(string $name, array $params = [], string $suffix = '')
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }
        Http::redirect($this->get($name, $params, '&') . $suffix);
    }

    /**
     * Gets the URL base.
     *
     * Retrieves a PHP page given its name, and optional parameters
     * acts like get, but without the query string, should be used within forms actions
     *
     * @param      string     $name   The name
     *
     * @throws     Exception  If unknown URL
     *
     * @return     string     The URL base.
     */
    public function getBase(string $name): string
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }

        return $this->urls[$name]['url'];
    }

    /**
     * Gets the hidden form fields.
     *
     * Forges form hidden fields to pass to a generated <form>. Should be used in combination with
     * form action retrieved from getBase()
     *
     * @param      string     $name    The name
     * @param      array      $params  The parameters
     *
     * @throws     Exception  If unknown URL
     *
     * @return     string     The hidden form fields.
     */
    public function getHiddenFormFields(string $name, array $params = []): string
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }
        $url = $this->urls[$name];
        $qs  = array_merge($url['qs'], $params);
        $str = '';
        foreach ($qs as $field => $value) {
            $str .= (new Hidden([$field], $value))->render();
        }

        return $str;
    }

    /**
     * Gets the hidden form fields as an array of formHidden object.
     *
     * Forges form hidden fields to pass to a generated <form>. Should be used in combination with
     * form action retrieved from getBase()
     *
     * @param      string     $name    The name
     * @param      array      $params  The parameters
     *
     * @throws     Exception  If unknown URL
     *
     * @return     array      The hidden form fields.
     */
    public function hiddenFormFields(string $name, array $params = []): array
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }
        $url   = $this->urls[$name];
        $qs    = array_merge($url['qs'], $params);
        $stack = [];
        foreach ($qs as $field => $value) {
            $stack[] = new Hidden([$field], $value);
        }

        return $stack;
    }

    /**
     * Retrieves a URL (decoded — useful for echoing) given its name, and optional parameters
     *
     * @deprecated     should be used carefully, parameters are no more escaped
     *
     * @param  string $name      URL Name
     * @param  array  $params    query string parameters, given as an associative array
     * @param  string $separator separator to use between QS parameters
     *
     * @return string            the forged decoded url
     */
    public function decode(string $name, array $params = [], string $separator = '&'): string
    {
        return urldecode($this->get($name, $params, $separator));
    }

    /**
     * Returns $urls property content.
     *
     * @return  ArrayObject
     */
    public function dumpUrls(): ArrayObject
    {
        return $this->urls;
    }
}
