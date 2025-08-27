<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factories;

/**
 * @brief   Database connection handlers container.
 * 
 * Handle Dotclear default database schema
 * from one of the following drivers:
 * * mysqli
 * * mysqlimb4
 * * pgsql
 * * sqlite
 *
 * @since   2.36
 */
class ContainerSchema extends Container
{
    public const CONTAINER_ID = 'database_schema';

    public function __construct()
    {
        parent::__construct(Factories::getFactory(static::CONTAINER_ID));
    }

    public function getDefaultServices(): array
    {
        return [    // @phpstan-ignore-line
        	'mysqli' 	=> \Dotclear\Schema\Database\Mysqli\Schema::class,
        	'mysqlimb4' => \Dotclear\Schema\Database\Mysqlimb4\Schema::class,
        	'pgsql' 	=> \Dotclear\Schema\Database\Pgsql\Schema::class,
        	'sqlite' 	=> \Dotclear\Schema\Database\Sqlite\Schema::class,
    	];
    }
}