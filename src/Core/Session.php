<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Session as DatabaseSession;
use Throwable;

/**
 * @brief   Session handler.
 *
 * Transitionnal class to set Dotclear default session handler table.
 *
 * @since   2.28, container services have been added to constructor
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Session extends DatabaseSession
{
    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
        parent::__construct(
            con: $this->core->db()->con(),
            table : $this->core->db()->con()->prefix() . Session::SESSION_TABLE_NAME,
            cookie_name: $this->core->config()->sessionName(),
            cookie_secure: $this->core->config()->adminSsl(),
            ttl: $this->core->config()->sessionTtl()
        );

        register_shutdown_function(function (): void {
            try {
                if (session_id()) {
                    // Explicitly close session before DB connection
                    session_write_close();
                }
                $this->core->db()->con()->close();
            } catch (Throwable) {
                // Ignore exceptions
            }
        });
    }
}
