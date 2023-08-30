<?php
/**
 * Connection handler.
 *
 * Handle database connection.
 *
 * Using old style AbstractHanlder to keep compatibility.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\ConnectionInterface;

use Dotclear\Database\AbstractHandler;

abstract class Connection extends AbstractHandler implements ConnectionInterface
{

}