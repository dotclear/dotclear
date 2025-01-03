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
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief   The module frontend template.
 * @ingroup pages
 */
class FrontendTemplate
{
    /**
     * Widget public rendering helper.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function pagesWidget(WidgetsElement $widget): string
    {
        $params = [];
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $params['post_type']     = 'page';
        $params['limit']         = abs((int) $widget->get('limit'));
        $params['no_content']    = true;
        $params['post_selected'] = false;

        $sort = $widget->get('sortby');
        if (!in_array($sort, ['post_title', 'post_position', 'post_dt'])) {
            $sort = 'post_title';
        }

        $order = $widget->get('orderby');
        if ($order !== 'asc') {
            $order = 'desc';
        }
        $params['order'] = $sort . ' ' . $order;

        $rs = App::blog()->getPosts($params);

        if ($rs->isEmpty()) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '') . '<ul>';

        while ($rs->fetch()) {
            $class = '';
            if (App::url()->getType() === 'pages' && App::frontend()->context()->posts instanceof MetaRecord && App::frontend()->context()->posts->post_id == $rs->post_id) {
                $class = ' class="page-current" aria-current="page"';
            }
            $res .= '<li' . $class . '><a href="' . $rs->getURL() . '">' .
            Html::escapeHTML($rs->post_title) . '</a></li>';
        }

        $res .= '</ul>';

        return $widget->renderDiv((bool) $widget->content_only, 'pages ' . $widget->class, '', $res);
    }
}
