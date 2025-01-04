<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use Dotclear\App;
use Dotclear\Helper\Stack\Filter;

/**
 * @brief   Admin list generic filters library.
 *
 * @since   2.20
 */
class FiltersLibrary
{
    /**
     * Common default input field.
     *
     * @param   string      $id     The filter ID
     * @param   string      $title  The filter title
     * @param   ?string     $param  The param ID
     *
     * @return  Filter  The Filter instance
     */
    public static function getInputFilter(string $id, string $title, ?string $param = null): Filter
    {
        return (new Filter($id))
            ->param($param ?: $id)
            ->form('input')
            ->title($title);
    }

    /**
     * Common default select field.
     *
     * @param   string          $id         The filter ID
     * @param   string          $title      The filter title
     * @param   array<mixed>    $options    The filter title
     * @param   ?string         $param      The param ID
     *
     * @return  ?Filter  The Filter instance if possible
     */
    public static function getSelectFilter(string $id, string $title, array $options, ?string $param = null): ?Filter
    {
        if ($options === []) {
            return null;
        }

        return (new Filter($id))
            ->param($param ?: $id)
            ->title($title)
            ->options($options);
    }

    /**
     * Common page filter (no field).
     *
     * @param   string  $id     The filter ID
     *
     * @return  Filter  The Filter instance
     */
    public static function getPageFilter(string $id = 'page'): Filter
    {
        return (new Filter($id))
            ->value(empty($_GET[$id]) ? 1 : max(1, (int) $_GET[$id]))
            ->param('limit', fn ($f): array => [(($f[0] - 1) * $f['nb']), $f['nb']]);
    }

    /**
     * Common search field.
     *
     * @return  Filter  The Filter instance
     */
    public static function getSearchFilter(): Filter
    {
        return (new Filter('q'))
            ->param('q', fn ($f) => $f['q'])
            ->form('input')
            ->title(__('Search:'))
            ->prime(true);
    }

    /**
     * Current blog filter (no field).
     *
     * This forces sql request to have where clause with current blog id.
     * Use your_filters->remove('current_blog')  to remove limitation.
     *
     * @param   string  $id     The filter ID
     *
     * @return  Filter  The Filter instance
     */
    public static function getCurrentBlogFilter(string $id = 'current_blog'): Filter
    {
        return (new Filter($id))
            ->value(App::con()->escape(App::blog()->id()))
            ->param('where', fn ($f): string => " AND P.blog_id = '" . App::con()->escapeStr(App::blog()->id()) . "' ");
    }
}
