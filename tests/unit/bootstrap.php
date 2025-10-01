<?php

/**
 * Unit tests bootstrap
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

use Dotclear\Core\Core;

// Composer Autoloader
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'vendor', 'autoload.php']);

// Dotclear root functions and autoloader
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'Functions.php']);

// Clearbricks Autoloader (deprecated)
$__autoload = [
    'form'             => implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'Helper', 'Html', 'Form', 'Legacy.php']),
    'formSelectOption' => implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'Helper', 'Html', 'Form', 'Legacy.php']),
];
spl_autoload_register(function ($name) use ($__autoload) {if (isset($__autoload[$name])) { require_once $__autoload[$name]; }});

// Instanciate Core
$root = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..']);
new Core($root);
