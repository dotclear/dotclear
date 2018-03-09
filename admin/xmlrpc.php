<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/prepend.php';

if (isset($_SERVER['PATH_INFO'])) {
    $blog_id = trim($_SERVER['PATH_INFO']);
    $blog_id = preg_replace('#^/#', '', $blog_id);
} elseif (!empty($_GET['b'])) {
    $blog_id = $_GET['b'];
}

if (empty($blog_id)) {
    header('Content-Type: text/plain');
    http::head(412);
    echo 'No blog ID given';
    exit;
}

# Avoid plugins warnings, set a default blog
$core->setBlog($blog_id);

# Loading plugins
$core->plugins->loadModules(DC_PLUGINS_ROOT);

# Start XML-RPC server
$server = new dcXmlRpc($core, $blog_id);
$server->serve();
