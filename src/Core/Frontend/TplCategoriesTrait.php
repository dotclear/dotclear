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

trait TplCategoriesTrait
{
    /**
     * tpl:Categories [attributes] : Categories loop (tpl block)
     *
     * attributes:
     *
     *      - cat_url                     Restrict to a category URL
     *      - post_type   (post|page|…)   Restrict to categories containing this type of entries
     *      - post_status level           Filter on the given comma separated entries status levels
     *      - level       int             Restrict to categories of this level (>= 1)
     *      - with_empty  (0|1)           Include empty categories
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function Categories(ArrayObject $attr, string $content): string
    {
        $params = "if (!isset(\$params)) \$params = [];\n";

        if (isset($attr['url']) && is_string($attr['url'])) {
            $params .= "\$params['cat_url'] = '" . addslashes($attr['url']) . "';\n";
        }

        if (!empty($attr['post_type']) && is_string(($attr['post_type']))) {
            $params .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (!empty($attr['post_status']) && is_string($attr['post_status'])) {
            $params .= "\$params['post_status'] = preg_split('/\s*,\s*/','" . addslashes($attr['post_status']) . "',-1,PREG_SPLIT_NO_EMPTY);\n";
        }

        if (!empty($attr['level']) && is_numeric($attr['level'])) {
            $params .= "\$params['level'] = " . (int) $attr['level'] . ";\n";
        }

        if (isset($attr['with_empty']) && ((bool) $attr['with_empty'])) {
            $params .= "\$params['without_empty'] = false;\n";
        }

        # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
        $params .= App::behavior()->callBehavior(
            'templatePrepareParams',
            [
                'tag'    => 'Categories',
                'method' => 'blog::getCategories',
            ],
            $attr,
            $content
        );

        return
        "<?php\n" .
        $params .
        'App::frontend()->context()->categories = App::blog()->getCategories($params);' . "\n" .
         "?>\n" .
         '<?php while (App::frontend()->context()->categories->fetch()) : ?>' .
         $content .
         '<?php endwhile; App::frontend()->context()->categories = null; unset($params); ?>';
    }

    /**
     * tpl:CategoriesHeader : Display content on first category element (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CategoriesHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->categories->isStart()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:CategoriesFooter : Display content on last category element (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CategoriesFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->categories->isEnd()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:CategoryIf [attributes] : Include content if category tests is true (tpl block)
     *
     * attributes:
     *
     *      - url                     Category has the given URL (see note 1)
     *      - urls                    Category has one of the given comma separated urls (see note 1 for each)
     *      - has_entries     (0|1)   Category has entries (if 1), or not (if 0)
     *      - has_description (0|1)   Category has description (if 1), or not (if 0)
     *
     *      Notes:
     *
     *      1) Use ! as prefix to inverse test, use ' sub' as suffix to includes category's sub-categories
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CategoryIf(ArrayObject $attr, string $content): string
    {
        /**
         * @var ArrayObject<array-key, string> $if
         */
        $if = new ArrayObject();

        $operator = isset($attr['operator']) && is_string($attr['operator']) ? static::getOperator($attr['operator']) : '&&';

        if (isset($attr['url']) && is_string($attr['url'])) {
            $url  = addslashes(trim($attr['url']));
            $args = preg_split('/\s*[?]\s*/', $url, -1, PREG_SPLIT_NO_EMPTY);
            if ($args !== false) {
                $url  = array_shift($args) ?? '';
                $args = array_flip($args);
                if (str_starts_with($url, '!')) {
                    $url = substr($url, 1);
                    if (isset($args['sub'])) {
                        $if->append('(!App::blog()->IsInCatSubtree(App::frontend()->context()->categories->cat_url, "' . $url . '"))');
                    } else {
                        $if->append('(App::frontend()->context()->categories->cat_url != "' . $url . '")');
                    }
                } elseif (isset($args['sub'])) {
                    $if->append('(App::blog()->IsInCatSubtree(App::frontend()->context()->categories->cat_url, "' . $url . '"))');
                } else {
                    $if->append('(App::frontend()->context()->categories->cat_url == "' . $url . '")');
                }
            }
        }

        if (isset($attr['urls']) && is_string($attr['urls'])) {
            $urls = explode(',', addslashes(trim($attr['urls'])));
            foreach ($urls as $url) {
                $args = preg_split('/\s*[?]\s*/', trim($url), -1, PREG_SPLIT_NO_EMPTY);
                if ($args !== false) {
                    $url  = array_shift($args) ?? '';
                    $args = array_flip($args);
                    if (str_starts_with($url, '!')) {
                        $url = substr($url, 1);
                        if (isset($args['sub'])) {
                            $if->append('(!App::blog()->IsInCatSubtree(App::frontend()->context()->categories->cat_url, "' . $url . '"))');
                        } else {
                            $if->append('(App::frontend()->context()->categories->cat_url != "' . $url . '")');
                        }
                    } elseif (isset($args['sub'])) {
                        $if->append('(App::blog()->IsInCatSubtree(App::frontend()->context()->categories->cat_url, "' . $url . '"))');
                    } else {
                        $if->append('(App::frontend()->context()->categories->cat_url == "' . $url . '")');
                    }
                }
            }
        }

        if (isset($attr['has_entries'])) {
            $sign = (bool) $attr['has_entries'] ? '>' : '==';
            $if->append('App::frontend()->context()->categories->nb_post ' . $sign . ' 0');
        }

        if (isset($attr['has_description'])) {
            $sign = (bool) $attr['has_description'] ? '!=' : '==';
            $if->append('App::frontend()->context()->categories->cat_desc ' . $sign . ' ""');
        }

        # --BEHAVIOR-- tplIfConditions -- string, ArrayObject, string, array<int,string>
        App::behavior()->callBehavior('tplIfConditions', 'CategoryIf', $attr, $content, $if);

        if (count($if) > 0) {
            return '<?php if(' . implode(' ' . $operator . ' ', $if->getArrayCopy()) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /**
     * tpl:CategoryFirstChildren : Current category first children loop (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CategoryFirstChildren(ArrayObject $attr, string $content): string
    {
        return
        "<?php\n" .
        'App::frontend()->context()->categories = App::blog()->getCategoryFirstChildren(App::frontend()->context()->categories->cat_id);' . "\n" .
        'while (App::frontend()->context()->categories->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->categories = null; ?>';
    }

    /**
     * tpl:CategoryParents : Current category parents loop (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CategoryParents(ArrayObject $attr, string $content): string
    {
        return
        "<?php\n" .
        'App::frontend()->context()->categories = App::blog()->getCategoryParents(App::frontend()->context()->categories->cat_id);' . "\n" .
        'while (App::frontend()->context()->categories->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->categories = null; ?>';
    }

    /**
     * tpl:CategoryFeedURL [attributes] : Category feed URL (tpl value)
     *
     * attributes:
     *
     *      - type            (rss2|atom)     Feed type, default to "atom"
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CategoryFeedURL(ArrayObject $attr): string
    {
        $type = isset($attr['type']) && is_string($type = $attr['type']) ? $type : 'atom';
        if (!(bool) preg_match('#^(rss2|atom)$#', $type)) {
            $type = 'atom';
        }

        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url().App::url()->getURLFor("feed","category/".App::frontend()->context()->categories->cat_url."/' . $type . '")') . ' ?>';
    }

    /**
     * tpl:CategoryID [attributes] : Category ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CategoryID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->categories->cat_id') . ' ?>';
    }

    /**
     * tpl:CategoryURL [attributes] : Category full URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CategoryURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url().App::url()->getURLFor("category",App::frontend()->context()->categories->cat_url)') . ' ?>';
    }

    /**
     * tpl:CategoryShortURL [attributes] : Category short URL, relative from /category/ (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CategoryShortURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->categories->cat_url') . ' ?>';
    }

    /**
     * tpl:CategoryDescription [attributes] : Category description (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CategoryDescription(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->categories->cat_desc') . ' ?>';
    }

    /**
     * tpl:CategoryTitle [attributes] : Category title (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CategoryTitle(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->categories->cat_title') . ' ?>';
    }

    /**
     * tpl:CategoryEntriesCount [attributes] : Category number of entries (tpl value)
     *
     * attributes:
     *
     *      - count_only      (1|0)   Display only counter value
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CategoryEntriesCount(ArrayObject $attr): string
    {
        return $this->displayCounter(
            sprintf($this->getFilters($attr), 'App::frontend()->context()->categories->nb_post'),
            [
                'none' => 'No post',
                'one'  => 'One post',
                'more' => '%d posts',
            ],
            $attr,
            true
        );
    }
}
