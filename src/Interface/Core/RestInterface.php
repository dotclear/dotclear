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
 * @brief   Rest server handler interface.
 *
 * @since   2.28
 */
interface RestInterface
{
    /**
     * XML response format.
     *
     * @var    int  XML_RESPONSE
     */
    public const XML_RESPONSE = 0;

    /**
     * JSON response format.
     *
     * @var    int  JSON_RESPONSE
     */
    public const JSON_RESPONSE = 1;

    /**
     * Default response format.
     *
     * @var    int  DEFAULT_RESPONSE
     */
    public const DEFAULT_RESPONSE = self::XML_RESPONSE;

    /**
     * Add Function
     *
     * This adds a new function to the server. <var>$callback</var> should be a valid PHP callback.
     *
     * Callback function takes two or three arguments:
     *  - supplemental parameter (if not null)
     *  - GET values
     *  - POST values
     *
     * @param string    $name        Function name
     * @param callable  $callback    Callback function
     */
    public function addFunction(string $name, $callback): void;

    /**
     * Main server
     *
     * This method creates the main server.
     *
     * @param string    $encoding       Server charset
     * @param int       $format         Response format
     * @param mixed     $param          Supplemental parameter
     */
    public function serve(string $encoding = 'UTF-8', int $format = self::DEFAULT_RESPONSE, $param = null): bool;

    /**
     * Serve or not the REST requests.
     *
     * Using a file as token
     *
     * @param      bool  $serve  The flag
     */
    public function enableRestServer(bool $serve = true): void;

    /**
     * Check if we need to serve REST requests.
     */
    public function serveRestRequests(): bool;
}
