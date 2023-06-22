<?php
/**
 * @package Dotclear
 * @subpackage Install
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\App;
use Dotclear\Install\Install;
use Dotclear\Install\Wizard;

// Start tick
define('DC_START_TIME', microtime(true));

// 1. Load Autoloader file
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'Autoloader.php']);

// 2. Add root folder for namespaced and autoloaded classes and do some init
Autoloader::me()->addNamespace('Dotclear', implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src']));

// 3. Instanciate (partially) the Application (singleton)
App::init();

// 4. Process installation
App::process(defined('DC_RC_PATH') && is_file(DC_RC_PATH) ? Install::class : Wizard::class);
