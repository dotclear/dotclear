<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Blogs list filters form helper.
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use dcCore;
use Dotclear\Core\Backend\Combos;

class FilterBlogs extends Filters
{
    public function __construct()
    {
        parent::__construct('blogs');

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            FiltersLibrary::getSearchFilter(),
            $this->getBlogStatusFilter(),
        ]);

        # --BEHAVIOR-- adminBlogFilter -- ArrayObject
        dcCore::app()->behavior->callBehavior('adminBlogFilterV2', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Blog status select
     */
    public function getBlogStatusFilter(): Filter
    {
        return (new Filter('status'))
            ->param('blog_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getBlogStatusesCombo()
            ))
            ->prime(true);
    }
}
