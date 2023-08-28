<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Admin list generic filters library.
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use dcCore;
use Dotclear\Core\Core;

class FiltersLibrary
{
    /**
     * Common default input field
     */
    public static function getInputFilter(string $id, string $title, ?string $param = null): Filter
    {
        return (new Filter($id))
            ->param($param ?: $id)
            ->form('input')
            ->title($title);
    }

    /**
     * Common default select field
     */
    public static function getSelectFilter(string $id, string $title, array $options, ?string $param = null): ?Filter
    {
        if (empty($options)) {
            return null;
        }

        return (new Filter($id))
            ->param($param ?: $id)
            ->title($title)
            ->options($options);
    }

    /**
     * Common page filter (no field)
     */
    public static function getPageFilter(string $id = 'page'): Filter
    {
        return (new Filter($id))
            ->value(!empty($_GET[$id]) ? max(1, (int) $_GET[$id]) : 1)
            ->param('limit', fn ($f) => [(($f[0] - 1) * $f['nb']), $f['nb']]);
    }

    /**
     * Common search field
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
     */
    public static function getCurrentBlogFilter(string $id = 'current_blog'): Filter
    {
        return (new Filter($id))
            ->value(Core::con()->escape(Core::blog()->id))
            ->param('where', fn ($f) => " AND P.blog_id = '" . Core::con()->escape(Core::blog()->id) . "' ");
    }
}
