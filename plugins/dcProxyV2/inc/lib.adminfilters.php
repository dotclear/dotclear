<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\Core\Backend\Filter\Filters;

/**
 * @brief   The module backend filters aliases handler.
 * @ingroup dcProxyV2
 */
class adminGenericFilter extends Filters
{
    public function __construct(dcCore $core, string $type) // @phpstan-ignore-line
    {
        parent::__construct($type);
    }
}
