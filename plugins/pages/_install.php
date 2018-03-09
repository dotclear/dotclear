<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$version = $core->plugins->moduleInfo('pages', 'version');
if (version_compare($core->getVersion('pages'), $version, '>=')) {
    return;
}

$core->blog->settings->addNamespace('pages');

if ($core->getVersion('pages') == null) {

    // Create a first pending page, only on a new installation of this plugin
    $params = array(
        'post_type'  => 'page',
        'no_content' => true
    );
    $counter = $core->blog->getPosts($params, true);

    if ($counter->f(0) == 0 && $core->blog->settings->pages->firstpage == null) {

        $core->blog->settings->pages->put('firstpage', true, 'boolean');

        $cur                     = $core->con->openCursor($core->prefix . 'post');
        $cur->user_id            = $core->auth->userID();
        $cur->post_type          = 'page';
        $cur->post_format        = 'xhtml';
        $cur->post_lang          = $core->blog->settings->system->lang;
        $cur->post_title         = __('My first page');
        $cur->post_content       = '<p>' . __('This is your first page. When you\'re ready to blog, log in to edit or delete it.') . '</p>';
        $cur->post_content_xhtml = $cur->post_content;
        $cur->post_excerpt       = '';
        $cur->post_excerpt_xhtml = $cur->post_excerpt;
        $cur->post_status        = -2; // Pending status
        $cur->post_open_comment  = 0;
        $cur->post_open_tb       = 0;
        $post_id                 = $core->blog->addPost($cur);
    }
}

$core->setVersion('pages', $version);
return true;
