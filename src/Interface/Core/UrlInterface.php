<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Exception;

/**
 * @brief   URL Handler for public urls interface.
 *
 * @since   2.28
 */
interface UrlInterface
{
    /**
     * Gets the current type.
     */
    public function getType(): string;

    /**
     * Sets the current type.
     *
     * @param      string  $type   The type
     */
    public function setType(string $type): void;

    /**
     * Gets the current mode.
     */
    public function getMode(): string;

    /**
     * Sets the current mode.
     *
     * @param      string  $mode   The mode
     */
    public function setMode(string $mode): void;

    /**
     * Gets the home type (static or default) set for the blog.
     */
    public function getHomeType(): string;

    /**
     * Determines whether the specified type is blog home page.
     *
     * @param      string  $type   The type
     */
    public function isHome(string $type): bool;

    /**
     * Gets the URL for a specified type.
     *
     * @param      string  $type   The type
     * @param      string  $value  The value
     */
    public function getURLFor(string $type, string $value = ''): string;

    /**
     * Register an URL handler
     *
     * @param      string       $type            The type
     * @param      string       $url             The url
     * @param      string       $representation  The representation
     * @param      callable     $handler         The handler
     */
    public function register(string $type, string $url, string $representation, $handler): void;

    /**
     * Register the default URL handler
     *
     * @param      callable  $handler  The handler
     */
    public function registerDefault($handler): void;

    /**
     * Register an error handler (prepend at the begining of the error handler stack)
     *
     * @param      callable  $handler  The handler
     */
    public function registerError($handler): void;

    /**
     * Unregister an URL handler
     *
     * @param      string  $type   The type
     */
    public function unregister(string $type): void;

    /**
     * Gets the registered URL handlers.
     *
     * @return     array<string, array<string, mixed>>  The types.
     */
    public function getTypes(): array;

    /**
     * Gets the base URI of an URL handler.
     *
     * @param      string  $type   The type
     */
    public function getBase(string $type): string;

    /**
     * Throws a 404 (page not found) exception
     *
     * @throws     Exception
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
     * Gets the arguments from an URI
     *
     * @param      string  $part   The part
     * @param      mixed   $type   The type
     * @param      mixed   $args   The arguments
     */
    public function getArgs(string $part, &$type, &$args): void;

    /**
     * Call an registered URL handler callback
     *
     * @param      string     $type   The type
     * @param      string     $args   The arguments
     *
     * @throws     Exception
     */
    public function callHandler(string $type, ?string $args = null): void;

    /**
     * Call the default handler callback
     *
     * @param      string  $args   The arguments
     *
     * @throws     Exception
     */
    public function callDefaultHandler(?string $args = null): void;

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
     * Rick Roll script-kiddies trying to connect using a wordpress URL :-)
     *
     * https://example.com/wp-admin and https://example.com/wp-login
     *
     * @param      null|string  $args   The arguments
     */
    public static function wpfaker(?string $args): never;
}
