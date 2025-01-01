<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * URL Handler for admin urls
 */
class Url
{
    /**
     * @var    ArrayObject<string, array<string, mixed>>     List of registered admin URLs
     */
    protected ArrayObject $urls;

    /**
     * @var    string  Default backend index page
     */
    public const INDEX = 'index.php';

    /**
     * @var    string  Default pugrade index page
     */
    public const UPGRADE = 'upgrade.php';

    /**
     * Constructs a new instance.
     *
     * @throws  Exception   If not in admin context
     */
    public function __construct()
    {
        if (!App::task()->checkContext('BACKEND')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        $this->urls = new ArrayObject();

        // set required URLs
        $this->register('admin.auth', 'Auth');
        $this->register('admin.logout', 'Logout');
    }

    /**
     * Register a new URL handler.
     *
     * If URL handler already exists it will be overwritten.
     *
     * @param   string                  $name       The url name
     * @param   string                  $class      Class name (without namespace) or url value
     * @param   array<string, mixed>    $params     Query string params (optional)
     */
    public function register(string $name, string $class, array $params = []): void
    {
        // by class name
        if (!str_contains($class, '.php')) {
            $params = ['process' => $class, ...$params];
            $class  = static::INDEX;
        }
        $this->urls[$name] = [
            'url' => $class,
            'qs'  => $params,
        ];
    }

    /**
     * Register a new URL as a copy of an existing one.
     *
     * If new URL handler already exists it will be overwritten.
     *
     * @throws  Exception   If unknown URL handler
     *
     * @param   string                  $name   The URL name
     * @param   string                  $orig   URL handler to copy information from
     * @param   array<string, mixed>    $params Extra parameters to add
     * @param   string                  $newurl New URL if different from the original
     */
    public function registercopy(string $name, string $orig, array $params = [], string $newurl = ''): void
    {
        if (!isset($this->urls[$orig])) {
            throw new Exception('Unknown URL handler for ' . $orig);
        }

        $url       = $this->urls[$orig];
        $url['qs'] = array_merge($url['qs'], $params);
        if ($newurl !== '') {
            $url['url'] = $newurl;
        }
        $this->urls[$name] = $url;
    }

    /**
     * Retrieve an URL given its name, and optional parameters
     *
     * @param   string                  $name           The URL name
     * @param   array<string, mixed>    $params         The query string parameters (associative array)
     * @param   string                  $separator      The separator (used between query string parameters)
     * @param   bool                    $parametric     Set to true if url will be used as (s)printf() format
     *
     * @throws  Exception  If unknown URL
     *
     * @return  string  The forged URL
     */
    public function get(string $name, array $params = [], string $separator = '&amp;', bool $parametric = false): string
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }

        $url = $this->urls[$name];
        $qs  = array_merge($url['qs'], $params);
        $url = $url['url'];
        if ($qs !== []) {
            $url .= (str_contains((string) $url, '?') ? $separator : '?') . http_build_query($qs, '', $separator);
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
     * @param   string                  $name       The name
     * @param   array<string, mixed>    $params     The parameters
     * @param   string                  $suffix     The suffix
     */
    public function redirect(string $name, array $params = [], string $suffix = ''): void
    {
        Http::redirect($this->get($name, $params, '&') . $suffix);
    }

    /**
     * Get the URL base.
     *
     * Retrieves a PHP page given its name, and optional parameters
     * acts like get, but without the query string, should be used within forms actions
     *
     * @param   string  $name   The name
     *
     * @throws  Exception   If unknown URL
     *
     * @return  string  The URL base.
     */
    public function getBase(string $name): string
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }

        return $this->urls[$name]['url'];
    }

    /**
     * Get the URL params (query string).
     *
     * @param   string  $name   The name
     *
     * @throws  Exception   If unknown URL
     *
     * @return  array<string, mixed>  The URL params.
     */
    public function getParams(string $name): array
    {
        if (!isset($this->urls[$name])) {
            throw new Exception('Unknown URL handler for ' . $name);
        }

        return $this->urls[$name]['qs'];
    }

    /**
     * Get the hidden form fields.
     *
     * Forges form hidden fields to pass to a generated <form>. Should be used in combination with
     * form action retrieved from getBase()
     *
     * @param   string                  $name    The name
     * @param   array<string, mixed>    $params  The parameters
     *
     * @throws  Exception   If unknown URL
     *
     * @return  string  The hidden form fields.
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
            $str .= (new Hidden([(string) $field], (string) $value))->render();
        }

        return $str;
    }

    /**
     * Get the hidden form fields as an array of formHidden object.
     *
     * Forges form hidden fields to pass to a generated <form>. Should be used in combination with
     * form action retrieved from getBase()
     *
     * @param   string                  $name    The name
     * @param   array<string, mixed>    $params  The parameters
     *
     * @throws  Exception   If unknown URL
     *
     * @return  array<int, Hidden>   The hidden form fields.
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
            $stack[] = new Hidden([(string) $field], (string) $value);
        }

        return $stack;
    }

    /**
     * Retrieve an URL (decoded — useful for echoing) given its name, and optional parameters
     *
     * @deprecated  should be used carefully, parameters are no more escaped
     *
     * @param   string                  $name       The URL Name
     * @param   array<string, mixed>    $params     Query string parameters, given as an associative array
     * @param   string                  $separator  Separator to use between QS parameters
     *
     * @return  string  The forged decoded url
     */
    public function decode(string $name, array $params = [], string $separator = '&'): string
    {
        return urldecode($this->get($name, $params, $separator));
    }

    /**
     * Return a copy of self::$urls property content.
     *
     * @return  ArrayObject<string, mixed>
     */
    public function dumpUrls(): ArrayObject
    {
        return clone $this->urls;
    }

    /**
     * Set default backend URLs handlers.
     */
    public function setDefaultUrls(): void
    {
        if (!App::task()->checkContext('BACKEND')) {
            return;
        }

        $this->register('admin.posts', 'Posts');
        $this->register('admin.popup_posts', 'PostsPopup'); //use admin.posts.popup
        $this->register('admin.posts.popup', 'PostsPopup');
        $this->register('admin.post', 'Post');
        $this->register('admin.post.media', 'PostMedia');
        $this->register('admin.blog.theme', 'BlogTheme');
        $this->register('admin.blog.pref', 'BlogPref');
        $this->register('admin.blog.del', 'BlogDel');
        $this->register('admin.blog', 'Blog');
        $this->register('admin.blogs', 'Blogs');
        $this->register('admin.categories', 'Categories');
        $this->register('admin.category', 'Category');
        $this->register('admin.comments', 'Comments');
        $this->register('admin.comment', 'Comment');
        $this->register('admin.help', 'Help');
        $this->register('admin.help.charte', 'HelpCharte');
        $this->register('admin.home', 'Home');
        $this->register('admin.langs', 'Langs');
        $this->register('admin.link.popup', 'LinkPopup');
        $this->register('admin.media', 'Media');
        $this->register('admin.media.item', 'MediaItem');
        $this->register('admin.plugins', 'Plugins');
        $this->register('admin.plugin', 'Plugin');
        $this->register('admin.search', 'Search');
        $this->register('admin.user.preferences', 'UserPreferences');
        $this->register('admin.user', 'User');
        $this->register('admin.user.actions', 'UsersActions');
        $this->register('admin.users', 'Users');
        $this->register('admin.csp.report', 'CspReport');
        $this->register('admin.rest', 'Rest');

        // we don't care of admin process for FileServer
        $this->register('load.plugin.file', static::INDEX, ['pf' => 'dummy.css']);
        $this->register('load.var.file', static::INDEX, ['vf' => 'dummy.json']);

        // from upgrade
        $this->register('upgrade.home', static::UPGRADE);
        $this->register('upgrade.upgrade', static::UPGRADE, ['process' => 'Upgrade']);

        // Deprecated since 2.32, use upgrade.home or upgrade.upgrade instead
        $this->register('admin.update', static::UPGRADE, ['process' => 'Upgrade']);
    }
}
