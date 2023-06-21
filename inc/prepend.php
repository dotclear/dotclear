<?php
/*
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\App;

// Start tick
define('DC_START_TIME', microtime(true));

// 1. Load Autoloader file
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Autoloader.php']);

// 2. Add root folder for namespaced and autoloaded classes and do some init
Autoloader::me()->addNamespace('Dotclear', implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src']));

// 3. Instanciate the Application (singleton)
new App();
App::init();
