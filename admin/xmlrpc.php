<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/prepend.php';

class adminXMLRPCpage
{
    public static function init()
    {
        if (isset($_SERVER['PATH_INFO'])) {
            $blog_id = trim((string) $_SERVER['PATH_INFO']);
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

        // Avoid plugins warnings, set a default blog
        dcCore::app()->setBlog($blog_id);

        // Loading plugins
        dcCore::app()->plugins->loadModules(DC_PLUGINS_ROOT);

        // Start XML-RPC server
        (new dcXmlRpc($blog_id))->serve();
    }
}

adminXMLRPCpage::init();
