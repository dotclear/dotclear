<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;

class FrontendTemplate
{
    /**
     * Widget public rendering helper
     *
     * @param      WidgetsElement  $widget  The widget
     *
     * @return     string
     */
    public static function pagesWidget(WidgetsElement $widget)
    {
        $params = [];
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->type)) {
            return '';
        }

        $params['post_type']     = 'page';
        $params['limit']         = abs((int) $widget->limit);
        $params['no_content']    = true;
        $params['post_selected'] = false;

        $sort = $widget->sortby;
        if (!in_array($sort, ['post_title', 'post_position', 'post_dt'])) {
            $sort = 'post_title';
        }

        $order = $widget->orderby;
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
            if (App::url()->type === 'pages' && App::frontend()->ctx->posts instanceof MetaRecord && App::frontend()->ctx->posts->post_id == $rs->post_id) {
                $class = ' class="page-current" aria-current="page"';
            }
            $res .= '<li' . $class . '><a href="' . $rs->getURL() . '">' .
            Html::escapeHTML($rs->post_title) . '</a></li>';
        }

        $res .= '</ul>';

        return $widget->renderDiv((bool) $widget->content_only, 'pages ' . $widget->class, '', $res);
    }
}
