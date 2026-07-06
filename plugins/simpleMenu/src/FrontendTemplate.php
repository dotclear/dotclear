<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief   The module frontend template.
 * @ingroup simpleMenu
 */
class FrontendTemplate
{
    /**
     * tpl:SimpleMenu [attributes] : Display current loop index (tpl value).
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
     * @param   ArrayObject<string, mixed>     $attr   The attributes
     */
    public static function simpleMenu(ArrayObject $attr): string
    {
        if (!App::blog()->settings()->get(My::WORKSPACE)->getBool(My::SETTING_ACTIVE)) {
            return '';
        }

        $class       = isset($attr['class'])       && is_string($class = $attr['class']) ? trim($class) : '';
        $id          = isset($attr['id'])          && is_string($id = $attr['id']) ? trim($id) : '';
        $description = isset($attr['description']) && is_string($description = $attr['description']) ? trim($description) : '';

        if (!preg_match('#^(title|span|both|none)$#', $description)) {
            $description = '';
        }

        return '<?= ' . self::class . '::displayMenu(' .
        "'" . addslashes($class) . "'," .
        "'" . addslashes($id) . "'," .
        "'" . addslashes($description) . "'" .
            ') ?>';
    }

    /**
     * Widget rendering function.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function simpleMenuWidget(WidgetsElement $widget): string
    {
        $descr_type = [0 => 'span', 1 => 'title', 2 => 'both', 3 => 'none'];

        if (!App::blog()->settings()->get(My::WORKSPACE)->getBool(My::SETTING_ACTIVE)) {
            return '';
        }

        if ($widget->offline) {
            return '';
        }

        if (($widget->homeonly == 1 && !App::url()->isHome(App::url()->getType())) || ($widget->homeonly == 2 && App::url()->isHome(App::url()->getType()))) {
            return '';
        }

        $type        = is_numeric($type = $widget->get('description')) ? (int) $type : 1;
        $description = $descr_type[$type];
        $menu        = self::displayMenu('', '', $description);
        if ($menu === '') {
            return '';
        }

        return $widget->renderDiv(
            (bool) $widget->content_only,
            'simple-menu ' . $widget->class,
            '',
            ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '') . $menu
        );
    }

    /**
     * Menu rendering helper (used by template and widget rendering callbacks).
     *
     * @param   string  $class          The class
     * @param   string  $id             The identifier
     * @param   string  $description    The description (see above)
     */
    public static function displayMenu(string $class = '', string $id = '', string $description = ''): string
    {
        $ret = '';

        if (!App::blog()->settings()->get(My::WORKSPACE)->getBool(My::SETTING_ACTIVE)) {
            return $ret;
        }

        // Load menu
        $simple_menu = SimpleMenu::load(My::WORKSPACE, My::SETTING_MENU);
        if ($simple_menu->menu()->count() > 0) {
            $menu = $simple_menu->menu();

            /**
             * @var array<array-key, Li> $items
             */
            $items = [];

            // Current relative URL
            $url     = isset($_SERVER['REQUEST_URI']) && is_string($url = $_SERVER['REQUEST_URI']) ? $url : '';
            $abs_url = Http::getHost() . $url;

            // Home recognition var
            $home_url       = Html::stripHostURL(App::blog()->url());
            $home_directory = dirname($home_url);
            if ($home_directory !== '/') {
                $home_directory .= '/';
            }

            // Menu items loop
            foreach ($menu as $i => $m) {
                if ($m->getDisabled()) {
                    continue;
                }

                $href = Html::escapeHTML($m->getUrl());

                // Cope with request only URL (ie ?query_part)
                $href_part = '';
                if ($href !== '' && str_starts_with($href, '?')) {
                    $href_part = substr($href, 1);
                }

                $targetBlank = $m->getTargetBlank();

                // Active item test
                $active = false;
                if (($url === $href) || ($abs_url === $href) || ($_SERVER['URL_REQUEST_PART'] == $href) || (($href_part !== '') && ($_SERVER['URL_REQUEST_PART'] == $href_part)) || (($_SERVER['URL_REQUEST_PART'] == '') && (($href === $home_url) || ($href === $home_directory)))) {
                    $active = true;
                }

                $title = '';
                $span  = '';

                $descr = Html::escapeHTML($m->getDescription());
                if ($descr !== '') {
                    if (($description === 'title' || $description === 'both') && $targetBlank) {
                        $title = $descr . ' (' .
                        __('new window') . ')';
                    } elseif ($description === 'title' || $description === 'both') {
                        $title = $descr;
                    }
                    if ($description === 'span' || $description === 'both') {
                        $span = ' ' . (new Span($descr))
                            ->class('simple-menu-descr')
                        ->render();
                    }
                }

                if ($title === '' && $targetBlank) {
                    $title = __('new window');
                }
                if ($active && !$targetBlank) {
                    $title = ($title === '' ? __('Active page') : $title . ' (' . __('active page') . ')');
                }

                $label = Html::escapeHTML($m->getLabel());
                $data  = Html::escapeHTML($m->getData());

                $item = new ArrayObject([
                    'url'    => $href,   // URL
                    'label'  => $label,  // <a> link label
                    'title'  => $title,  // <a> link title (optional)
                    'span'   => $span,   // description (will be displayed after <a> link and before the </a>)
                    'active' => $active, // status (true/false)
                    'class'  => '',      // additional <li> class (optional)
                    'data'   => $data,   // Custom data (data-menuitem attribute of link)
                ]);

                # --BEHAVIOR-- publicSimpleMenuItem -- int, ArrayObject
                App::behavior()->callBehavior('publicSimpleMenuItem', $i, $item);

                $li_class = isset($item['class']) && is_string($li_class = $item['class']) ? $li_class : '';
                $li_url   = isset($item['url'])   && is_string($li_url = $item['url']) ? $li_url : '';
                $li_title = isset($item['title']) && is_string($li_title = $item['title']) ? $li_title : '';
                $li_label = isset($item['label']) && is_string($li_label = $item['label']) ? $li_label : '';
                $li_data  = isset($item['data'])  && is_string($li_data = $item['data']) ? $li_data : '';
                $li_span  = isset($item['span'])  && is_string($li_span = $item['span']) ? $li_span : '';

                $link = (new Link())
                    ->href($li_url)
                    ->items([
                        (new Span($li_label))
                            ->class('simple-menu-label'),
                        (new Text(null, $li_span)),
                    ]);
                if ($li_title !== '') {
                    $link->title($li_title);
                }
                if ($targetBlank) {
                    $link->extra('target="_blank" rel="noopener noreferrer"');
                }
                if ($li_data !== '') {
                    $link->data([
                        'menuitem' => $li_data,
                    ]);
                }

                $classes = [
                    'li' . ($i + 1),
                    $item['active'] ? 'active' : '',
                    $i === 0 ? 'li-first' : '',
                    $i === count($menu) - 1 ? 'li-last' : '',
                    $li_class,
                ];

                $items[] = (new Li())
                    ->class($classes)
                    ->items([
                        $link,
                    ]);
            }

            // Final rendering
            if ($items !== []) {
                $ret = (new Div(null, 'nav'))
                    ->class('simple-menu-navigation')
                    ->items([
                        (new Ul($id !== '' ? $id : null))
                            ->class(['simple-menu', $class])
                            ->items($items),
                    ])
                ->render();
            }
        }

        return $ret;
    }
}
