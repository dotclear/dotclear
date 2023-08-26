<?php
/**
 * @brief Dotclear REST server extension
 *
 * This class extends Dotclear\Helper\RestServer to handle dcCore instance in each rest method call (XML response only).
 * Instance of this class is provided by dcCore::app()->rest.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\RestServer;

class dcRestServer extends RestServer
{
    /**
     * Main server
     *
     * This method creates the main server.
     *
     * @param      string  $encoding  The encoding
     * @param      int     $format    The format
     * @param      mixed   $param     The parameter
     *
     * @return     bool
     */
    public function serve(string $encoding = 'UTF-8', int $format = parent::XML_RESPONSE, $param = null): bool
    {
        if (isset($_REQUEST['json'])) {
            // No need to use dcCore::app() with JSON response
            return parent::serve($encoding, parent::JSON_RESPONSE);
        }

        // Use dcCore::app() as supplemental parameter to ensure retro-compatibility
        return parent::serve($encoding, parent::XML_RESPONSE, dcCore::app());
    }

    /**
     * Serve or not the REST requests.
     *
     * Using a file as token
     *
     * @param      bool  $serve  The flag
     */
    public function enableRestServer(bool $serve = true): void
    {
        if (defined('DC_UPGRADE')) {
            try {
                if ($serve && file_exists(DC_UPGRADE)) {
                    // Remove watchdog file
                    unlink(DC_UPGRADE);
                } elseif (!$serve && !file_exists(DC_UPGRADE)) {
                    // Create watchdog file
                    touch(DC_UPGRADE);
                }
            } catch (Exception) {
            }
        }
    }

    /**
     * Check if we need to serve REST requests.
     *
     * @return     bool
     */
    public function serveRestRequests(): bool
    {
        return defined('DC_UPGRADE') && defined('DC_REST_SERVICES') && !file_exists(DC_UPGRADE) && DC_REST_SERVICES;
    }
}
