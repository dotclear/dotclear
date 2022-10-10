<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class adminGenericList extends adminGenericListV2
{
    /**
     * Constructs a new instance.
     *
     * @param      mixed   $rs        The record
     * @param      mixed   $rs_count  The rs count
     */
    public function __construct(dcCore $core, $rs, $rs_count)
    {
        if ($rs && !($rs instanceof dcRecord)) {
            $rs = new dcRecord($rs);
        }
        parent::__construct($rs, $rs_count);
    }
}
