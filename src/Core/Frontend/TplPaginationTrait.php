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
use Dotclear\App;

trait TplPaginationTrait
{
    /**
     * tpl:Pagination [attributes] : Pagination container (tpl block)
     *
     * attributes:
     *
     *      - no_context  (0|1)       Override test on posts count vs number of posts per page
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function Pagination(ArrayObject $attr, string $content): string
    {
        $params = "<?php\n" .
            '$params = App::frontend()->context()->post_params;' . "\n" .
            # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
            App::behavior()->callBehavior(
                'templatePrepareParams',
                [
                    'tag'    => 'Pagination',
                    'method' => 'blog::getPosts',
                ],
                $attr,
                $content
            ) .
            'App::frontend()->context()->pagination = App::blog()->getPosts($params,true); unset($params);' . "\n" .
            "?>\n";

        if (isset($attr['no_context']) && (bool) $attr['no_context']) {
            return $params . $content;
        }

        return $params . '<?php if (App::frontend()->context()->pagination->cardinal() > App::frontend()->context()->posts->count()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:PaginationCounter [attributes] : Display the number of pages (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PaginationCounter(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), '(string) ' . Ctx::class . '::PaginationNbPages()') . ' ?>';
    }

    /**
     * tpl:PaginationCurrent [attributes] : Display the number of current page (tpl value)
     *
     * attributes:
     *
     *      - offset      int     Current offset
     *      - any filters         See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PaginationCurrent(ArrayObject $attr): string
    {
        $offset = isset($attr['offset']) && is_numeric($offset = $attr['offset']) ? (int) $offset : 0;

        return '<?= ' . sprintf($this->getFilters($attr), '(string) ' . Ctx::class . '::PaginationPosition(' . $offset . ')') . ' ?>';
    }

    /**
     * tpl:PaginationIf [attributes] : Includes content depending on pagination test (tpl block)
     *
     * attributes:
     *
     *      - start   (0|1)       First page (if 1) or not (if 0)
     *      - end     (0|1)       Last page (if 1) or not (if 0)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function PaginationIf(ArrayObject $attr, string $content): string
    {
        /**
         * @var ArrayObject<array-key, string>
         */
        $if = new ArrayObject();

        if (isset($attr['start'])) {
            $sign = (bool) $attr['start'] ? '' : '!';
            $if->append($sign . Ctx::class . '::PaginationStart()');
        }

        if (isset($attr['end'])) {
            $sign = (bool) $attr['end'] ? '' : '!';
            $if->append($sign . Ctx::class . '::PaginationEnd()');
        }

        # --BEHAVIOR-- tplIfConditions -- string, ArrayObject, array<int,string>
        App::behavior()->callBehavior('tplIfConditions', 'PaginationIf', $attr, $content, $if);

        if (count($if) > 0) {
            return '<?php if(' . implode(' && ', $if->getArrayCopy()) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /**
     * tpl:PaginationURL [attributes] : Display link to previous/next page (tpl value)
     *
     * attributes:
     *
     *      - offset      int     Page offset (negative for previous pages), default: 0
     *      - any filters         See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PaginationURL(ArrayObject $attr): string
    {
        $offset = 0;
        if (isset($attr['offset']) && is_numeric($attr['offset'])) {
            $offset = (int) $attr['offset'];
        }

        return '<?= ' . sprintf($this->getFilters($attr), Ctx::class . '::PaginationURL(' . $offset . ')') . ' ?>';
    }
}
