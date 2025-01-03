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

        if (isset($attr['category'])) {
            $category = addslashes((string) $attr['category']);
        }

        if (isset($attr['block'])) {
            $block = addslashes((string) $attr['block']);
        }

        if (isset($attr['item'])) {
            $item = addslashes((string) $attr['item']);
        }

        $only_cat = 'null';
        if (!empty($attr['only_category'])) {
            $only_cat = "'" . addslashes((string) $attr['only_category']) . "'";
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
            $links = $blogroll->getLinks();
        } catch (Exception) {
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
                $res .= sprintf($cat_title, Html::escapeHTML($k)) . "\n";
            }

            $res .= self::getLinksList($v, $block, $item);
        }

        return $res;
    }

    /**
     * Gets the links list (HTML).
     *
     * @param   array<string, mixed>    $links  The links
     * @param   string                  $block  The block pattern
     * @param   string                  $item   The item pattern
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
            if (!preg_match('$^([a-z][a-z0-9.+-]+://)$', (string) $link['link_href'])) {
                $url = Http::concatURL($self_uri, (string) $link['link_href']);
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

            $link = '<a href="' . Html::escapeHTML($href) . '"' .
            (($lang) ? ' hreflang="' . Html::escapeHTML($lang) . '"' : '') .
            (($desc) ? ' title="' . Html::escapeHTML($desc) . '"' : '') .
            (($xfn) ? ' rel="' . Html::escapeHTML($xfn) . '"' : '') .
            '>' .
            Html::escapeHTML($title) .
                '</a>';

            $current_class = $current === $link_id ? ' class="active"' : '';

            $list .= sprintf($item, $link, $current_class) . "\n";
        }

        return sprintf($block, $list) . "\n";
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

        $links = self::getList($widget->renderSubtitle('', false), '<ul>%s</ul>', '<li%2$s>%1$s</li>', $widget->get('category'));

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
