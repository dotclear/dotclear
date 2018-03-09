<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

require dirname(__FILE__) . '/_widgets.php';

# Blogroll template functions
$core->tpl->addValue('Blogroll', array('tplBlogroll', 'blogroll'));
$core->tpl->addValue('BlogrollXbelLink', array('tplBlogroll', 'blogrollXbelLink'));

$core->url->register('xbel', 'xbel', '^xbel(?:/?)$', array('urlBlogroll', 'xbel'));

class tplBlogroll
{
    public static function blogroll($attr)
    {
        $category = '<h3>%s</h3>';
        $block    = '<ul>%s</ul>';
        $item     = '<li%2$s>%1$s</li>';

        if (isset($attr['category'])) {
            $category = addslashes($attr['category']);
        }

        if (isset($attr['block'])) {
            $block = addslashes($attr['block']);
        }

        if (isset($attr['item'])) {
            $item = addslashes($attr['item']);
        }

        $only_cat = 'null';
        if (!empty($attr['only_category'])) {
            $only_cat = "'" . addslashes($attr['only_category']) . "'";
        }

        return
            '<?php ' .
            "echo tplBlogroll::getList('" . $category . "','" . $block . "','" . $item . "'," . $only_cat . "); " .
            '?>';
    }

    public static function blogrollXbelLink($attr)
    {
        $f = $GLOBALS['core']->tpl->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("xbel")') . '; ?>';
    }

    public static function getList($cat_title = '<h3>%s</h3>', $block = '<ul>%s</ul>', $item = '<li>%s</li>', $category = null)
    {
        $blogroll = new dcBlogroll($GLOBALS['core']->blog);

        try {
            $links = $blogroll->getLinks();
        } catch (Exception $e) {
            return false;
        }

        $res = '';

        $hierarchy = $blogroll->getLinksHierarchy($links);

        if ($category) {
            if (!isset($hierarchy[$category])) {
                return '';
            }
            $hierarchy = array($hierarchy[$category]);
        }

        foreach ($hierarchy as $k => $v) {
            if ($k != '') {
                $res .= sprintf($cat_title, html::escapeHTML($k)) . "\n";
            }

            $res .= self::getLinksList($v, $block, $item);
        }

        return $res;
    }

    private static function getLinksList($links, $block = '<ul>%s</ul>', $item = '<li%2$s>%1$s</li>')
    {
        $list = '';

        # Find current link item if any
        $current      = -1;
        $current_size = 0;
        $self_uri     = http::getSelfURI();

        foreach ($links as $k => $v) {
            if (!preg_match('$^([a-z][a-z0-9.+-]+://)$', $v['link_href'])) {
                $url = http::concatURL($self_uri, $v['link_href']);
                if (strlen($url) > $current_size && preg_match('/^' . preg_quote($url, '/') . '/', $self_uri)) {
                    $current      = $k;
                    $current_size = strlen($url);
                }
            }
        }

        foreach ($links as $k => $v) {
            $title = $v['link_title'];
            $href  = $v['link_href'];
            $desc  = $v['link_desc'];
            $lang  = $v['link_lang'];
            $xfn   = $v['link_xfn'];

            $link =
            '<a href="' . html::escapeHTML($href) . '"' .
            ((!$lang) ? '' : ' hreflang="' . html::escapeHTML($lang) . '"') .
            ((!$desc) ? '' : ' title="' . html::escapeHTML($desc) . '"') .
            ((!$xfn) ? '' : ' rel="' . html::escapeHTML($xfn) . '"') .
            '>' .
            html::escapeHTML($title) .
                '</a>';

            $current_class = $current == $k ? ' class="active"' : '';

            $list .= sprintf($item, $link, $current_class) . "\n";
        }

        return sprintf($block, $list) . "\n";
    }

    # Widget function
    public static function linksWidget($w)
    {
        global $core;

        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && $core->url->type != 'default') ||
            ($w->homeonly == 2 && $core->url->type == 'default')) {
            return;
        }

        $links = self::getList($w->renderSubtitle('', false), '<ul>%s</ul>', '<li%2$s>%1$s</li>', $w->category);

        if (empty($links)) {
            return;
        }

        return $w->renderDiv($w->content_only, 'links ' . $w->class, '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            $links);
    }
}

class urlBlogroll extends dcUrlHandlers
{
    public static function xbel($args)
    {
        $blogroll = new dcBlogroll($GLOBALS['core']->blog);

        try {
            $links = $blogroll->getLinks();
        } catch (Exception $e) {
            self::p404();
            return;
        }

        if ($args) {
            self::p404();
            return;
        }

        http::cache($GLOBALS['mod_files'], $GLOBALS['mod_ts']);

        header('Content-Type: text/xml; charset=UTF-8');

        echo
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<!DOCTYPE xbel PUBLIC "+//IDN python.org//DTD XML Bookmark Exchange ' .
        'Language 1.0//EN//XML"' . "\n" .
        '"http://www.python.org/topics/xml/dtds/xbel-1.0.dtd">' . "\n" .
        '<xbel version="1.0">' . "\n" .
        '<title>' . html::escapeHTML($GLOBALS['core']->blog->name) . " blogroll</title>\n";

        $i = 1;
        foreach ($blogroll->getLinksHierarchy($links) as $cat_title => $links) {
            if ($cat_title != '') {
                echo
                '<folder>' . "\n" .
                "<title>" . html::escapeHTML($cat_title) . "</title>\n";
            }

            foreach ($links as $k => $v) {
                $lang = $v['link_lang'] ? ' xml:lang="' . $v['link_lang'] . '"' : '';

                echo
                '<bookmark href="' . $v['link_href'] . '"' . $lang . '>' . "\n" .
                '<title>' . html::escapeHTML($v['link_title']) . "</title>\n";

                if ($v['link_desc']) {
                    echo '<desc>' . html::escapeHTML($v['link_desc']) . "</desc>\n";
                }

                if ($v['link_xfn']) {
                    echo
                        "<info>\n" .
                        '<metadata owner="http://gmpg.org/xfn/">' . $v['link_xfn'] . "</metadata>\n" .
                        "</info>\n";
                }

                echo
                    "</bookmark>\n";
            }

            if ($cat_title != '') {
                echo "</folder>\n";
            }

            $i++;
        }

        echo
            '</xbel>';
    }
}
