<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Users list filters form helper.
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use Dotclear\App;

class FilterUsers extends Filters
{
    public function __construct()
    {
        parent::__construct('users');

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            FiltersLibrary::getSearchFilter(),
        ]);

        # --BEHAVIOR-- adminUserFilter -- ArrayObject
        App::behavior()->callBehavior('adminUserFilterV2', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }
}
