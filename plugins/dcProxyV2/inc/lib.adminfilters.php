<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
use Dotclear\Core\Backend\Filter\Filters;

/**
 * @brief   The module backend filters aliases handler.
 * @ingroup dcProxyV2
 */
class adminGenericFilter extends Filters
{
    // @phpstan-ignore constructor.unusedParameter
    public function __construct(dcCore $core, string $type)
    {
        parent::__construct($type);
    }
}
