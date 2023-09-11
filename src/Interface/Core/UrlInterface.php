<?php
/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Exception;

/**
 * URL Handler for public urls
 */
interface UrlInterface
{
    /**
     * Gets the home type set for the blog.
     *
     * @return     string  The home type (static or default)
     */
    public function getHomeType(): string;

    /**
     * Determines whether the specified type is blog home page.
     *
     * @param      string  $type   The type
     *
     * @return     bool    True if the specified type is home, False otherwise.
     */
    public function isHome(string $type): bool;

    /**
     * Gets the URL for a specified type.
     *
     * @param      string  $type   The type
     * @param      string  $value  The value
     *
     * @return     string
     */
    public function getURLFor(string $type, string $value = ''): string;

    /**
     * Register an URL handler
     *
     * @param      string           $type            The type
     * @param      string           $url             The url
     * @param      string           $representation  The representation
     * @param      callable|array   $handler         The handler
     */
    public function register(string $type, string $url, string $representation, $handler): void;

    /**
     * Register the default URL handler
     *
     * @param      callable|array  $handler  The handler
     */
    public function registerDefault($handler): void;

    /**
     * Register an error handler (prepend at the begining of the error handler stack)
     *
     * @param      callable|array  $handler  The handler
     */
    public function registerError($handler): void;

    /**
     * Gets the registered URL handlers.
     *
     * @return     array  The types.
     */
    public function getTypes(): array;

    /**
     * Gets the base URI of an URL handler.
     *
     * @param      string  $type   The type
     *
     * @return     mixed
     */
    public function getBase(string $type);

    /**
     * Throws a 404 (page not found) exception
     *
     * @throws     Exception
     * @return never
     */
    public static function p404(): never;

    /**
     * Gets the page number from URI arguments.
     *
     * @param      mixed     $args   The arguments
     *
     * @return     false|int  The page number or false if none found.
     */
    public static function getPageNumber(mixed &$args): bool|int;

    /**
     * Serve a page using a template file
     *
     * @param      string     $tpl_name      The template file
     * @param      string     $content_type  The content type
     * @param      bool       $http_cache    The http cache
     * @param      bool       $http_etag     The http etag
     *
     * @throws     Exception
     */
    public static function serveDocument(string $tpl_name, string $content_type = 'text/html', bool $http_cache = true, bool $http_etag = true): void;

    /**
     * Gets the appropriate page based on requested URI.
     */
    public function getDocument(): void;

    /**
     * Output 404 (not found) page
     *
     * @param      null|string  $args   The arguments
     * @param      string       $type   The type
     * @param      Exception    $e      The exception
     */
    public static function default404(?string $args, string $type, Exception $e): void;

    /**
     * Output the Home page (last posts, paginated)
     *
     * @param      null|string  $args   The arguments
     */
    public static function home(?string $args): void;

    /**
     * Output the Static home page
     *
     * @param      null|string  $args   The arguments
     */
    public static function static_home(?string $args): void;

    /**
     * Output the Search page (found posts, paginated)
     *
     * Note: This handler is not called directly by the URL handler,
     *       but if necessary by one of them, so no need to set page number here.
     */
    public static function search(): void;

    /**
     * Output the Home page (last posts, paginated) for a specified language
     *
     * @param      null|string  $args   The arguments
     */
    public static function lang(?string $args): void;

    /**
     * Output the Category page (last posts of category, paginated)
     *
     * @param      null|string  $args   The arguments
     */
    public static function category(?string $args): void;

    /**
     * Output the Archive page
     *
     * @param      null|string  $args   The arguments
     */
    public static function archive(?string $args): void;

    /**
     * Output the Post page
     *
     * @param      null|string  $args   The arguments
     */
    public static function post(?string $args): void;

    /**
     * Output the Post preview page
     *
     * @param      null|string  $args   The arguments
     */
    public static function preview(?string $args): void;

    /**
     * Output the Theme preview page
     *
     * @param      null|string  $args   The arguments
     */
    public static function try(?string $args): void;

    /**
     * Output the Feed page
     *
     * @param      null|string  $args   The arguments
     */
    public static function feed(?string $args): void;

    /**
     * Cope with incoming Trackbacks
     *
     * @param      null|string  $args   The arguments
     */
    public static function trackback(?string $args): void;

    /**
     * Cope with incoming Webmention
     *
     * @param      null|string  $args   The arguments
     */
    public static function webmention(?string $args): void;

    /**
     * Cope with XML-RPC services URLs
     *
     * Limited to pingbacks only
     *
     * @param      null|string  $args   The arguments
     */
    public static function xmlrpc(?string $args): void;

    /**
     * Rick Roll script kiddies which try the administrative page wordpress URLs on the blog :-)
     *
     * https://example.com/wp-admin and https://example.com/wp-login
     *
     * @param      null|string  $args   The arguments
     * @return never
     */
    public static function wpfaker(?string $args): never;
}
