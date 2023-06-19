<?php
/**
 * @deprecated since 2.27 Use dcCommentsActions
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @deprecated It is only used for plugins compatibility
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminCommentsActions
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        $args = [];
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        if (isset($_REQUEST['redir'])) {
            $url_parts = explode('?', $_REQUEST['redir']);
            $uri       = $url_parts[0];
            if (isset($url_parts[1])) {
                parse_str($url_parts[1], $args);
            }
            $args['redir'] = $_REQUEST['redir'];
        } else {
            $uri  = dcCore::app()->adminurl->get('admin.comments');
            $args = [];
        }

        $comments_actions_page = new dcCommentsActions($uri, $args);
        $comments_actions_page->setEnableRedirSelection(false);
        $comments_actions_page->process();
    }
}

adminCommentsActions::init();
