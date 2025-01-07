<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Backend.Listing
 * @brief       Backend list pager helpers.
 */

namespace Dotclear\Core\Backend\Listing;

use ArrayObject;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;

/**
 * @brief   Generic class for admin listing form.
 *
 * @since   2.20
 */
class Listing
{
    /**
     * Count of elements listed.
     */
    protected ?int $rs_count;

    /**
     * Previous page label.
     */
    protected string $html_prev;

    /**
     * Next page label.
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
    public function userColumns(string $type, array|ArrayObject $cols): void
    {
        UserPref::getUserColumns($type, $cols);
    }

    /**
     * Get image for table row and legend.
     */
    public static function getRowImage(string $title, string $image, string $class, bool $with_text = false): Img|Text
    {
        $img = (new Img($image))
            ->alt(Html::escapeHTML($title))
            ->class(['mark', 'mark-' . $class]);

        return $with_text ?
            (new Text(null, $img->render() . Html::escapeHTML($title))) :
            $img;
        }
}
