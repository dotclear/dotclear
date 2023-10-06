<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Database\MetaRecord;

/**
 * @brief   The module backend listing aliases handler.
 * @ingroup dcProxyV2
 */
class adminGenericList extends Listing
{
    /**
     * Constructs a new instance.
     *
     * @param   Dotclear\Database\Record|Dotclear\Database\StaticRecord|Dotclear\Database\MetaRecord    $rs         The record
     * @param   mixed                                                                                   $rs_count   The rs count
     */
    public function __construct(dcCore $core, $rs, $rs_count)   // @phpstan-ignore-line
    {
        if (!($rs instanceof MetaRecord)) {
            $rs = new MetaRecord($rs);
        }
        parent::__construct($rs, $rs_count);
    }
}
