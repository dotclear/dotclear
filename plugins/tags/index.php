<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

if (!empty($_REQUEST['m'])) {
    switch ($_REQUEST['m']) {
        case 'tags':
        case 'tag_posts':
            require dirname(__FILE__) . '/' . $_REQUEST['m'] . '.php';
            break;
    }
}
