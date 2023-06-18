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

// load admin context
if (App::context(dcAdmin::class)) {
    // try to load admin process
    $process = '';
    var_dump($_GET);
    var_dump($_POST);
    var_dump($_REQUEST);
    exit;
    if (!empty($_REQUEST['process']) && is_string($_REQUEST['process'])) {
        $process = $_REQUEST['process'];
    } elseif (defined('APP_PROCESS') && is_string(APP_PROCESS)) {
        $process = APP_PROCESS;
    }
    if (!empty($process)) {
        App::process('Dotclear\\Backend\\' . $process);
    }
}
