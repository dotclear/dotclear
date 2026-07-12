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
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Ul;
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

        if (!$widget->checkNotOnArchive(App::url()->getType())) {
            return '';
        }

        $limit = is_numeric($limit = $widget->get('limit')) ? (int) $limit : 0;

        $params['post_type']     = 'page';
        $params['limit']         = abs($limit);
        $params['no_content']    = true;
        $params['post_selected'] = false;

        $sort = is_string($sort = $widget->get('sortby')) ? $sort : '';
        if (!in_array($sort, ['post_title', 'post_position', 'post_dt'], true)) {
            $sort = 'post_title';
        }

        $order = is_string($order = $widget->get('orderby')) ? $order : '';
        if ($order !== 'asc') {
            $order = 'desc';
        }

        $params['order'] = $sort . ' ' . $order;

        $rs = App::blog()->getPosts($params);

        if ($rs->isEmpty()) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $list = [];
        while ($rs->fetch()) {
            $class = '';
            $extra = '';
            if (App::url()->isType('pages')
                && App::frontend()->context()->posts instanceof MetaRecord
                && App::frontend()->context()->posts->intField('post_id') === $rs->intField('post_id')
            ) {
                $class = 'page-current';
                $extra = 'aria-current="page"';
            }

            $url   = $rs->getURL();
            $title = $rs->strField('post_title');

            $list[] = (new Li())
                ->class($class)
                ->extra($extra)
                ->items([
                    (new Link())
                        ->href($url)
                        ->text(Html::escapeHTML($title)),
                ]);
        }

        $res .= (new Ul())
            ->items($list)
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, 'pages ' . $widget->class, '', $res);
    }
}
