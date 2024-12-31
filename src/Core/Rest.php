<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use Dotclear\Helper\RestServer;
use Dotclear\Interface\ConfigInterface;
use Throwable;

/**
 * @brief   Rest server handler.
 *
 * This class extends Dotclear\Helper\RestServer to handle dcCore instance in each rest method call (XML response only).
 * Instance of this class is provided by App::rest().
 *
 * Rest class uses RestServer (class that RestInterface interface) constants.
 *
 * @since   2.28, container services have been added to constructor
 */
class Rest extends RestServer
{
    /**
     * Constructor.
     *
     * @param   ConfigInterface     $config    The application configuration
     */
    public function __construct(
        protected ConfigInterface $config
    ) {
        parent::__construct();
    }

    /**
     * @todo    Remove old dcCore from RestServer::serve returned parent parameters
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

    public function enableRestServer(bool $serve = true): void
    {
        if ($this->config->coreUpgrade() !== '') {
            try {
                if ($serve && file_exists($this->config->coreUpgrade())) {
                    // Remove watchdog file
                    unlink($this->config->coreUpgrade());
                } elseif (!$serve && !file_exists($this->config->coreUpgrade())) {
                    // Create watchdog file
                    touch($this->config->coreUpgrade());
                }
            } catch (Throwable) {
            }
        }
    }

    public function serveRestRequests(): bool
    {
        return !file_exists($this->config->coreUpgrade()) && $this->config->allowRestServices();
    }
}
