<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Session as DatabaseSession;
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Exception;

/**
 * @brief   Session handler.
 *
 * Transitionnal class to set Dotclear default session handler table.
 *
 * @since   2.28, container services have been added to constructor
 */
class Session extends DatabaseSession
{
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

        register_shutdown_function(function () {
            try {
                if (session_id()) {
                    // Explicitly close session before DB connection
                    session_write_close();
                }
                $this->con->close();
            } catch (Exception $e) {    // @phpstan-ignore-line
                // Ignore exceptions
            }
        });
    }
}
