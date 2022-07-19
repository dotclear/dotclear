<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class defaultWidgets
{
    public static function search($w)
    {
        if (dcCore::app()->blog->settings->system->no_search) {
            return;
        }

        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $value = isset($GLOBALS['_search']) ? html::escapeHTML($GLOBALS['_search']) : '';

        return $w->renderDiv(
            $w->content_only,
            $w->class,
            'id="search"',
            ($w->title ? $w->renderTitle('<label for="q">' . html::escapeHTML($w->title) . '</label>') : '') .
            '<form action="' . dcCore::app()->blog->url . '" method="get" role="search">' .
            '<p><input type="text" size="10" maxlength="255" id="q" name="q" value="' . $value . '" ' .
            ($w->placeholder ? 'placeholder="' . html::escapeHTML($w->placeholder) . '"' : '') .
            ' aria-label="' . __('Search') . '"/> ' .
            '<input type="submit" class="submit" value="ok" title="' . __('Search') . '" /></p>' .
            '</form>'
        );
    }

    public static function navigation($w)
    {
        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<nav role="navigation"><ul>';

        if (!dcCore::app()->url->isHome(dcCore::app()->url->type)) {
            // Not on home page (standard or static), add home link
            $res .= '<li class="topnav-home">' .
            '<a href="' . dcCore::app()->blog->url . '">' . __('Home') . '</a></li>';
            if (dcCore::app()->blog->settings->system->static_home) {
                // Static mode: add recent posts link
                $res .= '<li class="topnav-posts">' .
                '<a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('posts') . '">' . __('Recent posts') . '</a></li>';
            }
        } else {
            // On home page (standard or static)
            if (dcCore::app()->blog->settings->system->static_home) {
                // Static mode: add recent posts link
                $res .= '<li class="topnav-posts">' .
                '<a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('posts') . '">' . __('Recent posts') . '</a></li>';
            }
        }

        $res .= '<li class="topnav-arch">' .
        '<a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('archive') . '">' .
        __('Archives') . '</a></li>' .
            '</ul></nav>';

        return $w->renderDiv($w->content_only, $w->class, 'id="topnav"', $res);
    }

    public static function categories($w)
    {
        global $_ctx;

        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $rs = dcCore::app()->blog->getCategories(['post_type' => 'post', 'without_empty' => !$w->with_empty]);
        if ($rs->isEmpty()) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '');

        $ref_level = $level = $rs->level - 1;
        while ($rs->fetch()) {
            $class = '';
            if ((dcCore::app()->url->type == 'category' && $_ctx->categories instanceof record && $_ctx->categories->cat_id == $rs->cat_id)
                || (dcCore::app()->url->type == 'post' && $_ctx->posts instanceof record && $_ctx->posts->cat_id == $rs->cat_id)) {
                $class = ' class="category-current"';
            }

            if ($rs->level > $level) {
                $res .= str_repeat('<ul><li' . $class . '>', $rs->level - $level);
            } elseif ($rs->level < $level) {
                $res .= str_repeat('</li></ul>', -($rs->level - $level));
            }

            if ($rs->level <= $level) {
                $res .= '</li><li' . $class . '>';
            }

            $res .= '<a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('category', $rs->cat_url) . '">' .
            html::escapeHTML($rs->cat_title) . '</a>' .
                ($w->postcount ? ' <span>(' . ($w->subcatscount ? $rs->nb_total : $rs->nb_post) . ')</span>' : '');

            $level = $rs->level;
        }

        if ($ref_level - $level < 0) {
            $res .= str_repeat('</li></ul>', -($ref_level - $level));
        }

        return $w->renderDiv($w->content_only, 'categories ' . $w->class, '', $res);
    }

    public static function bestof($w)
    {
        global $_ctx;

        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $params = [
            'post_selected' => true,
            'no_content'    => true,
            'order'         => 'post_dt ' . strtoupper($w->orderby),
        ];

        $rs = dcCore::app()->blog->getPosts($params);

        if ($rs->isEmpty()) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<ul>';

        while ($rs->fetch()) {
            $class = '';
            if (dcCore::app()->url->type == 'post' && $_ctx->posts instanceof record && $_ctx->posts->post_id == $rs->post_id) {
                $class = ' class="post-current"';
            }
            $res .= ' <li' . $class . '><a href="' . $rs->getURL() . '">' . html::escapeHTML($rs->post_title) . '</a></li> ';
        }

        $res .= '</ul>';

        return $w->renderDiv($w->content_only, 'selected ' . $w->class, '', $res);
    }

    public static function langs($w)
    {
        global $_ctx;

        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $rs = dcCore::app()->blog->getLangs();

        if ($rs->count() <= 1) {
            return;
        }

        $langs = l10n::getISOcodes();
        $res   = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<ul>';

        while ($rs->fetch()) {
            $l = ($_ctx->cur_lang == $rs->post_lang) ? '<strong>%s</strong>' : '%s';

            $lang_name = $langs[$rs->post_lang] ?? $rs->post_lang;

            $res .= ' <li>' .
            sprintf(
                $l,
                '<a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('lang', $rs->post_lang) . '" ' .
                'class="lang-' . $rs->post_lang . '">' .
                $lang_name . '</a>'
            ) .
                ' </li>';
        }

        $res .= '</ul>';

        return $w->renderDiv($w->content_only, 'langs ' . $w->class, '', $res);
    }

    public static function subscribe($w)
    {
        global  $_ctx;

        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $type = ($w->type == 'atom' || $w->type == 'rss2') ? $w->type : 'rss2';
        $mime = $type     == 'rss2' ? 'application/rss+xml' : 'application/atom+xml';
        if ($_ctx->exists('cur_lang')) {
            $type = $_ctx->cur_lang . '/' . $type;
        }

        $p_title = __('This blog\'s entries %s feed');
        $c_title = __('This blog\'s comments %s feed');

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<ul>';

        $res .= '<li><a type="' . $mime . '" ' .
        'href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('feed', $type) . '" ' .
        'title="' . sprintf($p_title, ($type == 'atom' ? 'Atom' : 'RSS')) . '" class="feed">' .
        __('Entries feed') . '</a></li>';

        if (dcCore::app()->blog->settings->system->allow_comments || dcCore::app()->blog->settings->system->allow_trackbacks) {
            $res .= '<li><a type="' . $mime . '" ' .
            'href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('feed', $type . '/comments') . '" ' .
            'title="' . sprintf($c_title, ($type == 'atom' ? 'Atom' : 'RSS')) . '" class="feed">' .
            __('Comments feed') . '</a></li>';
        }

        $res .= '</ul>';

        return $w->renderDiv($w->content_only, 'syndicate ' . $w->class, '', $res);
    }

    public static function feed($w)
    {
        if (!$w->url) {
            return;
        }

        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $limit = abs((int) $w->limit);

        try {
            $feed = feedReader::quickParse($w->url, DC_TPL_CACHE);
            if ($feed == false || count($feed->items) == 0) {
                return;
            }
        } catch (Exception $e) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<ul>';

        $i = 0;
        foreach ($feed->items as $item) {
            $title = isset($item->title) && strlen(trim((string) $item->title)) ? $item->title : '';
            $link  = isset($item->link)  && strlen(trim((string) $item->link)) ? $item->link : '';

            if (!$link && !$title) {
                continue;
            }

            if (!$title) {
                $title = substr($link, 0, 25) . '...';
            }

            $li = $link ? '<a href="' . html::escapeHTML($item->link) . '">' . $title . '</a>' : $title;
            $res .= ' <li>' . $li . '</li> ';
            $i++;
            if ($i >= $limit) {
                break;
            }
        }

        $res .= '</ul>';

        return $w->renderDiv($w->content_only, 'feed ' . $w->class, '', $res);
    }

    public static function text($w)
    {
        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') . $w->text;

        return $w->renderDiv($w->content_only, 'text ' . $w->class, '', $res);
    }

    public static function lastposts($w)
    {
        global $_ctx;

        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $params['limit']      = abs((int) $w->limit);
        $params['order']      = 'post_dt desc';
        $params['no_content'] = true;

        if ($w->category) {
            if ($w->category == 'null') {
                $params['sql'] = ' AND P.cat_id IS NULL ';
            } elseif (is_numeric($w->category)) {
                $params['cat_id'] = (int) $w->category;
            } else {
                $params['cat_url'] = $w->category;
            }
        }

        if ($w->tag) {
            $params['meta_id'] = $w->tag;
            $rs                = dcCore::app()->meta->getPostsByMeta($params);
        } else {
            $rs = dcCore::app()->blog->getPosts($params);
        }

        if ($rs->isEmpty()) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<ul>';

        while ($rs->fetch()) {
            $class = '';
            if (dcCore::app()->url->type == 'post' && $_ctx->posts instanceof record && $_ctx->posts->post_id == $rs->post_id) {
                $class = ' class="post-current"';
            }
            $res .= '<li' . $class . '><a href="' . $rs->getURL() . '">' .
            html::escapeHTML($rs->post_title) . '</a></li>';
        }

        $res .= '</ul>';

        return $w->renderDiv($w->content_only, 'lastposts ' . $w->class, '', $res);
    }

    public static function lastcomments($w)
    {
        if ($w->offline) {
            return;
        }

        if (!$w->checkHomeOnly(dcCore::app()->url->type)) {
            return;
        }

        $params['limit'] = abs((int) $w->limit);
        $params['order'] = 'comment_dt desc';
        $rs              = dcCore::app()->blog->getComments($params);

        if ($rs->isEmpty()) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') . '<ul>';

        while ($rs->fetch()) {
            $res .= '<li class="' .
            ((bool) $rs->comment_trackback ? 'last-tb' : 'last-comment') .
            '"><a href="' . $rs->getPostURL() . '#c' . $rs->comment_id . '">' .
            html::escapeHTML($rs->post_title) . ' - ' .
            html::escapeHTML($rs->comment_author) .
                '</a></li>';
        }

        $res .= '</ul>';

        return $w->renderDiv($w->content_only, 'lastcomments ' . $w->class, '', $res);
    }
}
