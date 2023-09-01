<?php
/**
 * Session handler.
 *
 * Transitionnal class to set Dotclear default session handler table.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Session as databaseSession;

class Session extends databaseSession
{
    public const SESSION_TABLE_NAME = 'session';
}
