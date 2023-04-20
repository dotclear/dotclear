<?php
/**
 * Unit tests bootstrap
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

// Composer Autoloader

require_once __DIR__ . '/../../vendor/autoload.php';

// Dotclear Autoloader

require_once __DIR__ . '/../../src/Autoloader.php';

$autoloader = new Autoloader('', '', true);
$autoloader->addNamespace('Dotclear', implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src']));

// Clearbricks Autoloader (deprecated)

define('CLEARBRICKS_PATH', __DIR__ . '/../../inc/helper');  // Used in old tests/unit/inc/*

$__autoload = [
    'dbStruct'        => CLEARBRICKS_PATH . '/dbschema/class.dbstruct.php',
    'dbSchema'        => CLEARBRICKS_PATH . '/dbschema/class.dbschema.php',
    'mysqliSchema'    => CLEARBRICKS_PATH . '/dbschema/class.mysqli.dbschema.php',
    'mysqlimb4Schema' => CLEARBRICKS_PATH . '/dbschema/class.mysqlimb4.dbschema.php',
    'pgsqlSchema'     => CLEARBRICKS_PATH . '/dbschema/class.pgsql.dbschema.php',
    'sqliteSchema'    => CLEARBRICKS_PATH . '/dbschema/class.sqlite.dbschema.php',

    'dbLayer'             => CLEARBRICKS_PATH . '/dblayer/dblayer.php',
    'mysqliConnection'    => CLEARBRICKS_PATH . '/dblayer/class.mysqli.php',
    'mysqlimb4Connection' => CLEARBRICKS_PATH . '/dblayer/class.mysqlimb4.php',
    'pgsqlConnection'     => CLEARBRICKS_PATH . '/dblayer/class.pgsql.php',
    'sqliteConnection'    => CLEARBRICKS_PATH . '/dblayer/class.sqlite.php',

    'form'             => implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'Helper', 'Html', 'Form', 'Legacy.php']),
    'formSelectOption' => implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'Helper', 'Html', 'Form', 'Legacy.php']),
];
spl_autoload_register(function ($name) use ($__autoload) {if (isset($__autoload[$name])) { require_once $__autoload[$name]; }});

// Ensure L10n functions exist
require_once __DIR__ . '/../../src/Helper/L10n.php';

// Ensure some core classes exist
require_once __DIR__ . '/../../inc/core/class.dc.record.php';
