<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use ArrayObject;
use Exception;
use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\blogroll\Status\Link;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief   The module frontend tempalte.
 * @ingroup blogroll
 */
class FrontendTemplate
{
    /**
     * tpl:Blogroll [attributes] : Displays the blogroll (tpl value).
     *
     * attributes:
     *
     *      - category    string      Category title pattern (default to \<h3>%s\</h3>)
     *      - block       string      Block pattern (default to \<ul>%s\</ul>)
     *      - item        string      Item pattern (default to \<li%2$s>%1$s\</li>)
     *
     * @param   ArrayObject<string, mixed>     $attr   The attributes
     */
    public static function blogroll(ArrayObject $attr): string
    {
        $category = '<h3>%s</h3>';
        $block    = '<ul>%s</ul>';
        $item     = '<li%2$s>%1$s</li>';

        if (isset($attr['category']) && is_string($attr['category'])) {
            $category = addslashes($attr['category']);
        }

        if (isset($attr['block']) && is_string($attr['block'])) {
            $block = addslashes($attr['block']);
        }

        if (isset($attr['item']) && is_string($attr['item'])) {
            $item = addslashes($attr['item']);
        }

        $only_cat = 'null';
        if (!empty($attr['only_category']) && is_string($attr['only_category'])) {
            $only_cat = "'" . addslashes($attr['only_category']) . "'";
        }

        return
            '<?= ' . self::class . "::getList('" . $category . "','" . $block . "','" . $item . "'," . $only_cat . ');' . ' ?>';
    }

    /**
     * tpl:BlogrollXbelLink [attributes] : Displays XBEL URL (tpl value).
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param   ArrayObject<string, mixed>     $attr   The attributes
     */
    public static function blogrollXbelLink(ArrayObject $attr): string
    {
        return '<?= ' . sprintf(App::frontend()->template()->getFilters($attr), 'App::blog()->url().App::url()->getURLFor("xbel")') . ' ?>';
    }

    /**
     * Gets the full list (HTML).
     *
     * @param   string  $cat_title  The cat title pattern
     * @param   string  $block      The block pattern
     * @param   string  $item       The item pattern
     * @param   string  $category   The link category if given
     *
     * @return  string  The list.
     */
    public static function getList(string $cat_title = '<h3>%s</h3>', string $block = '<ul>%s</ul>', string $item = '<li>%s</li>', ?string $category = null): string
    {
        $blogroll = new Blogroll(App::blog());

        try {
            $rs = $blogroll->getLinks([
                'link_status' => Link::ONLINE,
            ]);
        } catch (Exception) {
            return '';
        }

        $res = '';

        $hierarchy = $blogroll->getLinksHierarchy($rs);

        if ($category) {
            if (!isset($hierarchy[$category])) {
                return '';
            }
            $hierarchy = [$category => $hierarchy[$category]];
        }

        foreach ($hierarchy as $category => $v) {
            if ($category !== '') {
                $res .= sprintf($cat_title, Html::escapeHTML($category)) . "\n";
            }

            $res .= self::getLinksList($v, $block, $item);
        }

        return $res;
    }

    /**
     * Gets the links list (HTML).
     *
     * @param   array<array-key, array<array-key, mixed>>   $links  The links
     * @param   string                                      $block  The block pattern
     * @param   string                                      $item   The item pattern
     *
     * @return  string  The links list.
     */
    private static function getLinksList(array $links, string $block = '<ul>%s</ul>', string $item = '<li%2$s>%1$s</li>'): string
    {
        $list = '';

        // Find current link item if any

        $current      = -1;
        $current_size = 0;
        $self_uri     = Http::getSelfURI();
        foreach ($links as $link_id => $link) {
            if (is_string($link['link_href']) && !preg_match('$^([a-z][a-z0-9.+-]+://)$', $link['link_href'])) {
                $url = Http::concatURL($self_uri, $link['link_href']);
                if (strlen($url) > $current_size && preg_match('/^' . preg_quote($url, '/') . '/', $self_uri)) {
                    $current      = $link_id;
                    $current_size = strlen($url);
                }
            }
        }

        foreach ($links as $link_id => $link) {
            $title = is_string($title = $link['link_title']) ? $title : '';
            $href  = is_string($href = $link['link_href']) ? $href : '';

            if ($title !== '' && $href !== '') {
                $desc = is_string($desc = $link['link_desc']) ? $desc : '';
                $lang = is_string($lang = $link['link_lang']) ? $lang : '';
                $xfn  = is_string($xfn = $link['link_xfn']) ? $xfn : '';

                $link = '<a href="' . Html::escapeHTML($href) . '"' .
                    (($lang !== '') ? ' hreflang="' . Html::escapeHTML($lang) . '"' : '') .
                    (($desc !== '') ? ' title="' . Html::escapeHTML($desc) . '"' : '') .
                    (($xfn !== '') ? ' rel="' . Html::escapeHTML($xfn) . '"' : '') .
                    '>' . Html::escapeHTML($title) . '</a>';

                $current_class = $current === $link_id ? ' class="active"' : '';

                $list .= sprintf($item, $link, $current_class) . "\n";
            }
        }

        return $list !== '' ? sprintf($block, $list) . "\n" : '';
    }

    /**
     * Widget public rendering helper.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function linksWidget(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $category = is_string($category = $widget->get('category')) ? $category : null;
        $links    = self::getList($widget->renderSubtitle('', false), '<ul>%s</ul>', '<li%2$s>%1$s</li>', $category);

        if ($links === '') {
            return '';
        }

        return $widget->renderDiv(
            (bool) $widget->content_only,
            'links ' . $widget->class,
            '',
            ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '') .
            $links
        );
    }
}
