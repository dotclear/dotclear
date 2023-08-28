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
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use dcBlog;
use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (Core::version()->getVersion(My::id()) === '') {
            // Create a first pending page, only on a new installation of this plugin
            $params = [
                'post_type'  => 'page',
                'no_content' => true,
            ];
            $counter = Core::blog()->getPosts($params, true);

            if ($counter->f(0) == 0 && My::settings()->firstpage == null) {
                My::settings()->put('firstpage', true, 'boolean');

                $cur                     = Core::con()->openCursor(Core::con()->prefix() . dcBlog::POST_TABLE_NAME);
                $cur->user_id            = Core::auth()->userID();
                $cur->post_type          = 'page';
                $cur->post_format        = 'xhtml';
                $cur->post_lang          = Core::blog()->settings->system->lang;
                $cur->post_title         = __('My first page');
                $cur->post_content       = '<p>' . __('This is your first page. When you\'re ready to blog, log in to edit or delete it.') . '</p>';
                $cur->post_content_xhtml = $cur->post_content;
                $cur->post_excerpt       = '';
                $cur->post_excerpt_xhtml = $cur->post_excerpt;
                $cur->post_status        = dcBlog::POST_PENDING; // Pending status
                $cur->post_open_comment  = 0;
                $cur->post_open_tb       = 0;
                $post_id                 = Core::blog()->addPost($cur);
            }
        }

        return true;
    }
}
