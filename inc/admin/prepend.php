<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\App;

define('DC_CONTEXT_ADMIN', true);
define('DC_ADMIN_CONTEXT', true); // For dyslexic devs ;-)

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'prepend.php']);

App::context(dcAdmin::class);
