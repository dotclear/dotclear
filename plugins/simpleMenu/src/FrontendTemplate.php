<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use ArrayObject;
use dcCore;
use dcWidget;
use html;
use http;

if (!defined('DC_RC_PATH')) {
    return false;
}

class FrontendTemplate
{
    /**
     * tpl:SimpleMenu [attributes] : Display current loop index (tpl value)
     *
     * attributes:
     *
     *      - class         string    Additional unumbered list CSS class
     *      - id            string    Unumbered list ID
     *      - description   string    Description usage (span, title, both or none, see note 1)
     *
     * Notes :
     *
     *  1). The link description (used as menuitem label) may be put as title of the link element, inside a span or both
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function simpleMenu(ArrayObject $attr): string
    {
        if (!(bool) dcCore::app()->blog->settings->system->simpleMenu_active) {
            return '';
        }

        $class       = isset($attr['class']) ? trim((string) $attr['class']) : '';
        $id          = isset($attr['id']) ? trim((string) $attr['id']) : '';
        $description = isset($attr['description']) ? trim((string) $attr['description']) : '';

        if (!preg_match('#^(title|span|both|none)$#', $description)) {
            $description = '';
        }

        return '<?php echo ' . self::class . '::displayMenu(' .
        "'" . addslashes($class) . "'," .
        "'" . addslashes($id) . "'," .
        "'" . addslashes($description) . "'" .
            '); ?>';
    }

    /**
     * Widget rendering function
     *
     * @param      dcWidget  $widget  The widget
     *
     * @return     string
     */
    public static function simpleMenuWidget(dcWidget $widget): string
    {
        $descr_type = [0 => 'span', 1 => 'title', 2 => 'both', 3 => 'none'];

        if (!(bool) dcCore::app()->blog->settings->system->simpleMenu_active) {
            return '';
        }

        if ($widget->offline) {
            return '';
        }

        if (($widget->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type)) || ($widget->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))) {
            return '';
        }

        $description = 'title';
        if (isset($descr_type[$widget->description])) {
            $description = $descr_type[$widget->description];
        }
        $menu = self::displayMenu('', '', $description);
        if ($menu == '') {
            return '';
        }

        return $widget->renderDiv(
            $widget->content_only,
            'simple-menu ' . $widget->class,
            '',
            ($widget->title ? $widget->renderTitle(html::escapeHTML($widget->title)) : '') . $menu
        );
    }

    /**
     * Menu rendering helper (used by template and widget rendering callbacks)
     *
     * @param      string  $class        The class
     * @param      string  $id           The identifier
     * @param      string  $description  The description (see above)
     *
     * @return     string
     */
    public static function displayMenu(string $class = '', string $id = '', string $description = ''): string
    {
        $ret = '';

        if (!(bool) dcCore::app()->blog->settings->system->simpleMenu_active) {
            return $ret;
        }

        $menu = dcCore::app()->blog->settings->system->simpleMenu;
        if (is_array($menu)) {
            // Current relative URL
            $url     = $_SERVER['REQUEST_URI'];
            $abs_url = http::getHost() . $url;

            // Home recognition var
            $home_url       = html::stripHostURL(dcCore::app()->blog->url);
            $home_directory = dirname($home_url);
            if ($home_directory != '/') {
                $home_directory = $home_directory . '/';
            }

            // Menu items loop
            foreach ($menu as $i => $m) {
                # $href = lien de l'item de menu
                $href = $m['url'];
                $href = html::escapeHTML($href);

                # Cope with request only URL (ie ?query_part)
                $href_part = '';
                if ($href != '' && substr($href, 0, 1) == '?') {
                    $href_part = substr($href, 1);
                }

                $targetBlank = ((isset($m['targetBlank'])) && ($m['targetBlank'])) ? true : false;

                # Active item test
                $active = false;
                if (($url == $href) || ($abs_url == $href) || ($_SERVER['URL_REQUEST_PART'] == $href) || (($href_part != '') && ($_SERVER['URL_REQUEST_PART'] == $href_part)) || (($_SERVER['URL_REQUEST_PART'] == '') && (($href == $home_url) || ($href == $home_directory)))) {
                    $active = true;
                }
                $title = $span = '';

                if ($m['descr']) {
                    if (($description == 'title' || $description == 'both') && $targetBlank) {
                        $title = html::escapeHTML($m['descr']) . ' (' .
                        __('new window') . ')';
                    } elseif ($description == 'title' || $description == 'both') {
                        $title = html::escapeHTML($m['descr']);
                    }
                    if ($description == 'span' || $description == 'both') {
                        $span = ' <span class="simple-menu-descr">' . html::escapeHTML($m['descr']) . '</span>';
                    }
                }

                if (empty($title) && $targetBlank) {
                    $title = __('new window');
                }
                if ($active && !$targetBlank) {
                    $title = (empty($title) ? __('Active page') : $title . ' (' . __('active page') . ')');
                }

                $label = html::escapeHTML($m['label']);

                $item = new ArrayObject([
                    'url'    => $href,   // URL
                    'label'  => $label,  // <a> link label
                    'title'  => $title,  // <a> link title (optional)
                    'span'   => $span,   // description (will be displayed after <a> link and before the </a>)
                    'active' => $active, // status (true/false)
                    'class'  => '',      // additional <li> class (optional)
                ]);

                # --BEHAVIOR-- publicSimpleMenuItem
                dcCore::app()->callBehavior('publicSimpleMenuItem', $i, $item);

                $ret .= '<li class="li' . ($i + 1) .
                    ($item['active'] ? ' active' : '') .
                    ($i === 0 ? ' li-first' : '') .
                    ($i === count($menu) - 1 ? ' li-last' : '') .
                    ($item['class'] ? ' ' . $item['class'] : '') .
                    '">' .

                    '<a href="' . $item['url'] . '"' .
                    (!empty($item['title']) ? ' title="' . $item['label'] . ' - ' . $item['title'] . '"' : '') .
                    (($targetBlank) ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' .

                    '<span class="simple-menu-label">' . $item['label'] . '</span>' . $item['span'] .

                    '</a>' .

                    '</li>';
            }
            // Final rendering
            if ($ret) {
                $ret = '<nav role="navigation"><ul ' . ($id ? 'id="' . $id . '"' : '') . ' class="simple-menu' . ($class ? ' ' . $class : '') . '">' . "\n" . $ret . "\n" . '</ul></nav>';
            }
        }

        return $ret;
    }
}
