<?php
/**
 * Version handler.
 *
 * Handle id,version pairs through database.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\AbstractHandler;

class CoreFactory implements CoreFactoryInterface
{
    public function __construct(protected Core $core) {}

    public function con(): AbstractHandler
    {
        return AbstractHandler::init(DC_DBDRIVER, DC_DBHOST, DC_DBNAME, DC_DBUSER, DC_DBPASSWORD, DC_DBPERSIST, DC_DBPREFIX);
    }

    public function version(): Version
    {
        return new Version($this->core->get('con'));
    }
}