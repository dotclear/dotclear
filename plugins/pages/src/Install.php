<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module install process.
 * @ingroup pages
 */
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

        if (App::version()->getVersion(My::id()) === '') {
            // Create a first pending page, only on a new installation of this plugin
            $params = [
                'post_type'  => 'page',
                'no_content' => true,
            ];
            $counter = App::blog()->getPosts($params, true);

            if ($counter->f(0) == 0 && My::settings()->firstpage == null) {
                My::settings()->put('firstpage', true, 'boolean');

                $cur                     = App::blog()->openPostCursor();
                $cur->user_id            = App::auth()->userID();
                $cur->post_type          = 'page';
                $cur->post_format        = 'xhtml';
                $cur->post_lang          = App::blog()->settings()->system->lang;
                $cur->post_title         = __('My first page');
                $cur->post_content       = '<p>' . __('This is your first page. When you\'re ready to blog, log in to edit or delete it.') . '</p>';
                $cur->post_content_xhtml = $cur->post_content;
                $cur->post_excerpt       = '';
                $cur->post_excerpt_xhtml = $cur->post_excerpt;
                $cur->post_status        = App::status()->post()::PENDING;
                $cur->post_open_comment  = 0;
                $cur->post_open_tb       = 0;
                App::blog()->addPost($cur);
            }
        }

        return true;
    }
}
