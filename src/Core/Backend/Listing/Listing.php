<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Listing;

use Dotclear\Core\Backend\UserPref;
use Dotclear\Database\MetaRecord;

class Listing
{
    /**
     * MetaRecord Elements listed
     */
    protected $rs;

    /**
     * int|null Count of elements listed
     */
    protected $rs_count;

    /**
     * string Previous page label
     */
    protected $html_prev;

    /**
     * string Next page label
     */
    protected $html_next;

    /**
     * Constructs a new instance.
     *
     * @param      MetaRecord   $rs        The record
     * @param      mixed        $rs_count  The rs count
     */
    public function __construct(MetaRecord $rs, $rs_count)
    {
        $this->rs        = &$rs;
        $this->rs_count  = (int) $rs_count;
        $this->html_prev = __('&#171; prev.');
        $this->html_next = __('next &#187;');
    }

    /**
     * Get user defined columns
     *
     * @param      string               $type   The type
     * @param      array|ArrayObject    $cols   The columns
     */
    public function userColumns(string $type, $cols)
    {
        $cols = UserPref::getUserColumns($type, $cols);
    }
}
