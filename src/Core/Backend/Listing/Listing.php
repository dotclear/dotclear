<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Backend.Listing
 * @brief       Backend list pager helpers.
 */

namespace Dotclear\Core\Backend\Listing;

use Dotclear\Core\Backend\UserPref;
use Dotclear\Database\MetaRecord;

/**
 * @brief   Generic class for admin listing form.
 *
 * @since   2.20
 */
class Listing
{
    /**
     * Count of elements listed.
     *
     * @var     int|null    $rs_count
     */
    protected ?int $rs_count;

    /**
     * Previous page label.
     *
     * @var     string  $html_prev
     */
    protected string $html_prev;

    /**
     * Next page label.
     *
     * @var     string  $html_next
     */
    protected string $html_next;

    /**
     * Constructs a new instance.
     *
     * @param   MetaRecord  $rs     The record
     * @param   mixed   $rs_count   The rs count
     */
    public function __construct(
        protected MetaRecord $rs,
        $rs_count
    ) {
        $this->rs_count  = (int) $rs_count;
        $this->html_prev = __('&#171; prev.');
        $this->html_next = __('next &#187;');
    }

    /**
     * Get user defined columns
     *
     * @param      string                                               $type   The type
     * @param      array<string, mixed>|\ArrayObject<string, mixed>     $cols   The columns
     */
    public function userColumns(string $type, $cols): void
    {
        $cols = UserPref::getUserColumns($type, $cols);
    }
}
