<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Session as DatabaseSession;
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Throwable;

/**
 * @brief   Session handler.
 *
 * Transitionnal class to set Dotclear default session handler table.
 *
 * @since   2.28, container services have been added to constructor
 */
class Session extends DatabaseSession
{
    /**
     * Constructor.
     *
     * @param   ConfigInterface         $config     The application configuration
     * @param   ConnectionInterface     $con        The database connection instance
     */
    public function __construct(
        protected ConfigInterface $config,
        protected ConnectionInterface $con
    ) {
        parent::__construct(
            con: $this->con,
            table : $this->con->prefix() . Session::SESSION_TABLE_NAME,
            cookie_name: $this->config->sessionName(),
            cookie_secure: $this->config->adminSsl(),
            ttl: $this->config->sessionTtl()
        );

        register_shutdown_function(function (): void {
            try {
                if (session_id()) {
                    // Explicitly close session before DB connection
                    session_write_close();
                }
                $this->con->close();
            } catch (Throwable) {
                // Ignore exceptions
            }
        });
    }
}
