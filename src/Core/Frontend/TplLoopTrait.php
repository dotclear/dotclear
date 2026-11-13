<?php

/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use ArrayObject;

trait TplLoopTrait
{
    /**
     * tpl:LoopPosition [attributes] : Display content depending on current position (tpl block)
     *
     * attributes:
     *
     *      - start       int       Start (first = 1)
     *      - length      int       Length
     *      - even        (1|0)     Even / Odd
     *      - modulo      int       Modulo
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function LoopPosition(ArrayObject $attr, string $content): string
    {
        $start  = isset($attr['start'])  && is_numeric($start = $attr['start']) ? (int) $start : '0';
        $length = isset($attr['length']) && is_numeric($length = $attr['length']) ? (int) $length : 'null';
        $even   = isset($attr['even']) ? (int) (bool) $attr['even'] : 'null';
        $modulo = isset($attr['modulo']) && is_numeric($modulo = $attr['modulo']) ? (int) $modulo : 'null';

        if ($start > 0) {
            // PHP array is 0 based index
            $start--;
        }

        return '<?php if (App::frontend()->context()->loopPosition(' . $start . ',' . $length . ',' . $even . ',' . $modulo . ')) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:LoopIndex [attributes] : Display current loop index (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function LoopIndex(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), '(!App::frontend()->context()->cur_loop ? 0 : App::frontend()->context()->cur_loop->index() + 1)') . ' ?>';
    }

    /**
     * tpl:LoopCount [attributes] : Display current loop count (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function LoopCount(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), '(!App::frontend()->context()->cur_loop ? 0 : App::frontend()->context()->cur_loop->count())') . ' ?>';
    }
}
