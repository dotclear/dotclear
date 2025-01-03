<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Dotclear\App;

class BlogPref
{
    /**
     * JS Popup helper for static home linked to an entry
     *
     * @param      string  $plugin_id  Plugin id (or admin URL)
     */
    public static function adminPopupPosts(string $plugin_id = ''): string
    {
        if ($plugin_id === '' || $plugin_id !== 'admin.blog_pref') {
            return '';
        }

        return
        Page::jsJson('admin.blog_pref', [
            'base_url' => App::blog()->url(),
        ]) .
        Page::jsLoad('js/_blog_pref_popup_posts.js');
    }
}
