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

use Dotclear\App;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\SchemaInterface;

/**
 * @brief   Database connection handler.
 */
abstract class Connection implements ConnectionInterface
{
    public function schema(): SchemaInterface
    {
        return App::db()->schema($this->driver());
    }
}
