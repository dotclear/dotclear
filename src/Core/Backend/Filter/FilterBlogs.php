<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Stack\Filter;

/**
 * @brief   Blogs list filters form helper.
 *
 * @since   2.20
 */
class FilterBlogs extends Filters
{
    public function __construct()
    {
        parent::__construct('blogs');

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            FiltersLibrary::getSearchFilter(),
            App::status()->blog()->filter(),
        ]);

        # --BEHAVIOR-- adminBlogFilter -- ArrayObject
        App::behavior()->callBehavior('adminBlogFilterV2', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Blog status select.
     *
     * @deprecated  since 2.33, use App::status()->blog()->filter()  instead
     *
     * @return  Filter  The blog status Filter instance.
     */
    public function getBlogStatusFilter(): Filter
    {
        return App::status()->blog()->filter();
    }
}
