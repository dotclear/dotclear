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
if (!defined('DC_RC_PATH')) {
    return;
}

class tplBlogroll
{
    /**
     * tpl:Blogroll [attributes] : Displays the blogroll (tpl value)
     *
     * attributes:
     *
     *      - category    string      Category title pattern (default to \<h3>%s\</h3>)
     *      - block       string      Block pattern (default to \<ul>%s\</ul>)
     *      - item        string      Item pattern (default to \<li%2$s>%1$s\</li>)
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
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
            "echo tplBlogroll::getList('" . $category . "','" . $block . "','" . $item . "'," . $only_cat . '); ' .
            '?>';
    }

    /**
     * tpl:BlogrollXbelLink [attributes] : Displays XBEL URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function blogrollXbelLink($attr)
    {
        return '<?php echo ' . sprintf(dcCore::app()->tpl->getFilters($attr), 'dcCore::app()->blog->url.dcCore::app()->url->getURLFor("xbel")') . '; ?>';
    }

    /**
     * Gets the full list (HTML).
     *
     * @param      string       $cat_title  The cat title pattern
     * @param      string       $block      The block pattern
     * @param      string       $item       The item pattern
     * @param      string       $category   The link category if given
     *
     * @return     string       The list.
     */
    public static function getList(string $cat_title = '<h3>%s</h3>', string $block = '<ul>%s</ul>', string $item = '<li>%s</li>', ?string $category = null): string
    {
        $blogroll = new dcBlogroll(dcCore::app()->blog);

        try {
            $links = $blogroll->getLinks();
        } catch (Exception $e) {
            return '';
        }

        $res = '';

        $hierarchy = $blogroll->getLinksHierarchy($links);

        if ($category) {
            if (!isset($hierarchy[$category])) {
                return '';
            }
            $hierarchy = [$category => $hierarchy[$category]];
        }

        foreach ($hierarchy as $k => $v) {
            if ($k != '') {
                $res .= sprintf($cat_title, html::escapeHTML($k)) . "\n";
            }

            $res .= self::getLinksList($v, $block, $item);
        }

        return $res;
    }

    /**
     * Gets the links list (HTML).
     *
     * @param      array   $links  The links
     * @param      string  $block  The block pattern
     * @param      string  $item   The item pattern
     *
     * @return     string  The links list.
     */
    private static function getLinksList(array $links, string $block = '<ul>%s</ul>', string $item = '<li%2$s>%1$s</li>'): string
    {
        $list = '';

        // Find current link item if any

        $current      = -1;
        $current_size = 0;
        $self_uri     = http::getSelfURI();
        foreach ($links as $link_id => $link) {
            if (!preg_match('$^([a-z][a-z0-9.+-]+://)$', $link['link_href'])) {
                $url = http::concatURL($self_uri, $link['link_href']);
                if (strlen($url) > $current_size && preg_match('/^' . preg_quote($url, '/') . '/', $self_uri)) {
                    $current      = $link_id;
                    $current_size = strlen($url);
                }
            }
        }

        foreach ($links as $link_id => $link) {
            $title = $link['link_title'];
            $href  = $link['link_href'];
            $desc  = $link['link_desc'];
            $lang  = $link['link_lang'];
            $xfn   = $link['link_xfn'];

            $link = '<a href="' . html::escapeHTML($href) . '"' .
            ((!$lang) ? '' : ' hreflang="' . html::escapeHTML($lang) . '"') .
            ((!$desc) ? '' : ' title="' . html::escapeHTML($desc) . '"') .
            ((!$xfn) ? '' : ' rel="' . html::escapeHTML($xfn) . '"') .
            '>' .
            html::escapeHTML($title) .
                '</a>';

            $current_class = $current == $link_id ? ' class="active"' : '';

            $list .= sprintf($item, $link, $current_class) . "\n";
        }

        return sprintf($block, $list) . "\n";
    }

    /**
     * Widget public rendering helper
     *
     * @param      dcWidget  $widget  The widget
     *
     * @return     string
     */
    public static function linksWidget(dcWidget $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(dcCore::app()->url->type)) {
            return '';
        }

        $links = self::getList($widget->renderSubtitle('', false), '<ul>%s</ul>', '<li%2$s>%1$s</li>', $widget->category);

        if (empty($links)) {
            return '';
        }

        return $widget->renderDiv(
            $widget->content_only,
            'links ' . $widget->class,
            '',
            ($widget->title ? $widget->renderTitle(html::escapeHTML($widget->title)) : '') .
            $links
        );
    }
}
