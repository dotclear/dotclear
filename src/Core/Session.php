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

/**
 * @brief   Session handler.
 *
 * Transitionnal class to set Dotclear default session handler table.
 *
 * @since   2.28, container services have been added to constructor
 */
class Session extends DatabaseSession
{
    public function __construct(ConfigInterface $config, ConnectionInterface $con)
    {
        parent::__construct(
            con: $con,
            table : $con->prefix() . Session::SESSION_TABLE_NAME,
            cookie_name: $config->sessionName(),
            cookie_secure: $config->adminSsl(),
            ttl: $config->sessionTtl()
        );
    }
}
