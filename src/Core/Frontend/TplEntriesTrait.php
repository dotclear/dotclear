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
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Template\Template;

trait TplEntriesTrait
{
    /**
     * tpl:Entries [attributes] : Entries loop (tpl block)
     *
     * attributes:
     *
     *      - lastn               int         Limit number of results to specified value
     *      - author              string      Get entries for a given user id
     *      - category            string      Get entries for specific categories only (comma-separated categories), see note 1
     *      - no_category         (1|0)       Get entries without category
     *      - with_category       (1|0)       Get entries with a category
     *      - no_context          (1|0)       Override context information
     *      - sortby              (title|selected|author|date|id)     Specify entries sort criteria (default : date), see note 2
     *      - order               (desc|asc)  specify entries order (default : desc)
     *      - no_content          (0|1)       Do not retrieve entries content
     *      - selected            (0|1)       Retrieve posts marked as selected only (if 1) or not selected only (if 0)
     *      - url                 string      Retrieve post by its url
     *      - type                (post|page|…)   Restrict to entries with this type (comma separated types)
     *      - status              level       Filter on the given comma separated entries status levels
     *      - age                 string      Retrieve posts by maximum age, see note 3
     *      - ignore_pagination   (0|1)       Ignore page number provided in URL, see note 4
     *
     *      Notes:
     *
     *      1) Use ! as prefix to inverse test
     *      2) Comma-separated sortbies can be specified. Use "?asc" or "?desc" as suffix to provide an order for each
     *      3) Examples: -2 days, last month, last week
     *      4) Useful when using multiple tpl:Entries on the same page
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function Entries(ArrayObject $attr, string $content): string
    {
        $lastn = -1;
        if (isset($attr['lastn']) && is_numeric($attr['lastn'])) {
            $lastn = abs((int) $attr['lastn']);
        }

        $params = 'if (App::frontend()->getPageNumber() === 0) { App::frontend()->setPageNumber(1); }' . "\n";
        $params .= "if (!isset(\$params) || !isset(\$params['sql'])) { \$params['sql'] = ''; }\n";

        if ($lastn !== 0) {
            // Set limit (aka nb of entries needed)
            if ($lastn > 0) {
                // nb of entries per page specified in template -> regular pagination
                $params .= "\$params['limit'] = " . $lastn . ";\n";
                $params .= '$nb_entry_first_page = $nb_entry_per_page = ' . $lastn . ";\n";
            } else {
                // nb of entries per page not specified -> use ctx settings
                $params .= "\$nb_entry_first_page=App::frontend()->context()->nb_entry_first_page; \$nb_entry_per_page = App::frontend()->context()->nb_entry_per_page;\n";
                $params .= "if (App::url()->isType(['default', 'default-page'])) {\n";
                $params .= "    \$params['limit'] = (App::frontend()->getPageNumber() === 1 ? \$nb_entry_first_page : \$nb_entry_per_page);\n";
                $params .= "} else {\n";
                $params .= "    \$params['limit'] = \$nb_entry_per_page;\n";
                $params .= "}\n";
            }

            // Set offset (aka index of first entry)
            $ignore = isset($attr['ignore_pagination']) && is_numeric($ignore = $attr['ignore_pagination']) ? (int) $ignore : 0;
            if (!isset($attr['ignore_pagination']) || $ignore === 0) {
                // standard pagination, set offset
                $params .= "if (App::url()->isType(['default', 'default-page'])) {\n";
                $params .= "    \$params['limit'] = [(App::frontend()->getPageNumber() === 1 ? 0 : (App::frontend()->getPageNumber() - 2) * \$nb_entry_per_page + \$nb_entry_first_page),\$params['limit']];\n";
                $params .= "} else {\n";
                $params .= "    \$params['limit'] = [(App::frontend()->getPageNumber() - 1) * \$nb_entry_per_page,\$params['limit']];\n";
                $params .= "}\n";
            } else {
                // no pagination, get all posts from 0 to limit
                $params .= "\$params['limit'] = [0, \$params['limit']];\n";
            }
        }

        if (isset($attr['author']) && is_string($attr['author'])) {
            $params .= "\$params['user_id'] = '" . addslashes($attr['author']) . "';\n";
        }

        if (isset($attr['category']) && is_string($attr['category'])) {
            $params .= "\$params['cat_url'] = '" . addslashes($attr['category']) . "';\n";
            $params .= Ctx::class . "::categoryPostParam(\$params);\n";
        }

        if (isset($attr['with_category']) && (bool) $attr['with_category']) {
            $params .= "\$params['sql'] .= ' AND P.cat_id IS NOT NULL ';\n";
        }

        if (isset($attr['no_category']) && (bool) $attr['no_category']) {
            $params .= "\$params['sql'] .= ' AND P.cat_id IS NULL ';\n";
            $params .= "unset(\$params['cat_url']);\n";
        }

        if (!empty($attr['type']) && is_string($attr['type'])) {
            $params .= "\$params['post_type'] = preg_split('/\s*,\s*/','" . addslashes($attr['type']) . "',-1,PREG_SPLIT_NO_EMPTY);\n";
        }

        if (!empty($attr['status']) && is_string($attr['status'])) {
            $params .= "\$params['post_status'] = preg_split('/\s*,\s*/','" . addslashes($attr['status']) . "',-1,PREG_SPLIT_NO_EMPTY);\n";
        }

        if (!empty($attr['url']) && is_string($attr['url'])) {
            $params .= "\$params['post_url'] = '" . addslashes($attr['url']) . "';\n";
        }

        if (empty($attr['no_context'])) {
            if (!isset($attr['author'])) {
                $params .= 'if (App::frontend()->context()->exists("users")) { ' .
                    "\$params['user_id'] = App::frontend()->context()->users->user_id; " .
                    "}\n";
            }

            if (!isset($attr['category']) && (!isset($attr['no_category']) || !(bool) $attr['no_category'])) {
                $params .= 'if (App::frontend()->context()->exists("categories")) { ' .
                    "\$params['cat_id'] = App::frontend()->context()->categories->cat_id.(App::blog()->settings()->get('system')->getBool('inc_subcats', false) ? ' ?sub' : '');" .
                    "}\n";
            }

            $params .= 'if (App::frontend()->context()->exists("archives")) { ' .
                "\$params['post_year'] = App::frontend()->context()->archives->year(); " .
                "\$params['post_month'] = App::frontend()->context()->archives->month(); ";
            if (!isset($attr['lastn'])) {
                $params .= "unset(\$params['limit']); ";
            }

            $params .= "}\n";

            $params .= 'if (App::frontend()->context()->exists("langs")) { ' .
                "\$params['post_lang'] = App::frontend()->context()->langs->post_lang; " .
                "}\n";

            $params .= 'if (isset(App::frontend()->search)) { ' .
                "\$params['search'] = App::frontend()->search; " .
                "}\n";
        }

        $params .= "\$params['order'] = '" . $this->getSortByStr($attr, 'post') . "';\n";

        if (isset($attr['no_content']) && (bool) $attr['no_content']) {
            $params .= "\$params['no_content'] = true;\n";
        }

        if (isset($attr['selected'])) {
            $params .= "\$params['post_selected'] = " . (int) (bool) $attr['selected'] . ';';
        }

        if (isset($attr['age'])) {
            $age = static::getAge($attr);
            $params .= $age === '' ? '' : "\$params['sql'] .= ' AND P.post_dt > \'" . $age . "\'';\n";
        }

        $res = "<?php\n";
        $res .= $params;
        # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
        $res .= App::behavior()->callBehavior(
            'templatePrepareParams',
            ['tag' => 'Entries', 'method' => 'blog::getPosts'],
            $attr,
            $content
        );
        $res .= 'App::frontend()->context()->post_params = $params;' . "\n";
        $res .= 'App::frontend()->context()->posts = App::blog()->getPosts($params); unset($params);' . "\n";
        $res .= "?>\n";

        return $res . '<?php while (App::frontend()->context()->posts->fetch()) : ?>' . $content . '<?php endwhile; ' . 'App::frontend()->context()->posts = null; App::frontend()->context()->post_params = null; ?>';
    }

    /**
     * tpl:DateHeader : Displays content, if post is the first post of the given day (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function DateHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->posts->firstPostOfDay()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:DateFooter : Displays content, if post is the last post of the given day (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function DateFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->posts->lastPostOfDay()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:EntryIf [attributes] : Include content if entry tests is true (tpl block)
     *
     * attributes:
     *
     *      - type            (post|page|…)   Post has a given type (default: "post")
     *      - url             string          Post has given url
     *      - author          string          Post has given user_id
     *      - category        string          Post has a given category URL, see note 1
     *      - categories      string          Post has a given categories (comma separated) URL, see note 1
     *      - first           (0|1)           Post is the first post from list (if 1) or not (if 0)
     *      - odd             (0|1)           Post is in an odd position (if 1) or not (if 0)
     *      - even            (0|1)           Post is in an even position (if 1) or not (if 0)
     *      - extended        (0|1)           Post has an excerpt (if 1) or not (if 0)
     *      - selected        (0|1)           Post is selected (if 1) or not (if 0)
     *      - has_category    (0|1)           Post has a category (if 1) or not (if 0)
     *      - has_attachment  (0|1)           Post has attachments (if 1) or not (if 0)
     *      - comments_active (0|1)           Comments are active for this post (if 1) or not (if 0)
     *      - pings_active    (0|1)           Trackbacks are active for this post (if 1) or not (if 0)
     *      - has_comments    (0|1)           There are comments for this post (if 1) or not (if 0)
     *      - has_pings       (0|1)           There are trackbacks for this post (if 1) or not (if 0)
     *      - show_comments   (0|1)           Comments are enabled for this post (if 1) or not (if 0)
     *      - show_pings      (0|1)           Trackbacks are enabled for this post (if 1) or not (if 0)
     *      - republished     (0|1)           Post has been updated since publication (if 1) or not (if 0)
     *      - operator        (and|or)        Combination of conditions, if more than 1 specified (default: and)
     *
     *      Notes:
     *
     *      1) Use ! as prefix to inverse test, use ' sub' as suffix to includes category's sub-categories
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function EntryIf(ArrayObject $attr, string $content): string
    {
        /**
         * @var ArrayObject<array-key, string> $if
         */
        $if = new ArrayObject();

        $operator = isset($attr['operator']) && is_string($attr['operator']) ? static::getOperator($attr['operator']) : '&&';

        if (isset($attr['type']) && is_string($attr['type'])) {
            $type = trim($attr['type']);
            if ($type === '') {
                $type = 'post';
            }

            if (str_starts_with($type, '!')) {
                $type = substr($type, 1);
                $if->append('App::frontend()->context()->posts->post_type != "' . addslashes($type) . '"');
            } else {
                $if->append('App::frontend()->context()->posts->post_type == "' . addslashes($type) . '"');
            }
        }

        if (isset($attr['url']) && is_string($attr['url'])) {
            $url = trim($attr['url']);
            if (str_starts_with($url, '!')) {
                $url = substr($url, 1);
                $if->append('App::frontend()->context()->posts->post_url != "' . addslashes($url) . '"');
            } else {
                $if->append('App::frontend()->context()->posts->post_url == "' . addslashes($url) . '"');
            }
        }

        if (isset($attr['category']) && is_string($attr['category'])) {
            $category = addslashes(trim($attr['category']));
            $args     = preg_split('/\s*[?]\s*/', $category, -1, PREG_SPLIT_NO_EMPTY);
            if ($args !== false) {
                $category = array_shift($args) ?? '';
                $args     = array_flip($args);
                if (str_starts_with($category, '!')) {
                    $category = substr($category, 1);
                    if (isset($args['sub'])) {
                        $if->append('(!App::frontend()->context()->posts->underCat("' . $category . '"))');
                    } else {
                        $if->append('(App::frontend()->context()->posts->cat_url != "' . $category . '")');
                    }
                } elseif (isset($args['sub'])) {
                    $if->append('(App::frontend()->context()->posts->underCat("' . $category . '"))');
                } else {
                    $if->append('(App::frontend()->context()->posts->cat_url == "' . $category . '")');
                }
            }
        }

        if (isset($attr['categories']) && is_string($attr['categories'])) {
            $categories = explode(',', addslashes(trim($attr['categories'])));
            foreach ($categories as $category) {
                $args = preg_split('/\s*[?]\s*/', trim($category), -1, PREG_SPLIT_NO_EMPTY);
                if ($args !== false) {
                    $category = array_shift($args) ?? '';
                    $args     = array_flip($args);
                    if (str_starts_with($category, '!')) {
                        $category = substr($category, 1);
                        if (isset($args['sub'])) {
                            $if->append('(!App::frontend()->context()->posts->underCat("' . $category . '"))');
                        } else {
                            $if->append('(App::frontend()->context()->posts->cat_url != "' . $category . '")');
                        }
                    } elseif (isset($args['sub'])) {
                        $if->append('(App::frontend()->context()->posts->underCat("' . $category . '"))');
                    } else {
                        $if->append('(App::frontend()->context()->posts->cat_url == "' . $category . '")');
                    }
                }
            }
        }

        if (isset($attr['first'])) {
            $sign = (bool) $attr['first'] ? '=' : '!';
            $if->append('App::frontend()->context()->posts->index() ' . $sign . '= 0');
        }

        if (isset($attr['odd'])) {
            $sign = (bool) $attr['odd'] ? '=' : '!';
            $if->append('(App::frontend()->context()->posts->index()+1)%2 ' . $sign . '= 1');
        }

        if (isset($attr['even'])) {
            $sign = (bool) $attr['even'] ? '=' : '!';
            $if->append('(App::frontend()->context()->posts->index()+1)%2 ' . $sign . '= 0');
        }

        if (isset($attr['extended'])) {
            $sign = (bool) $attr['extended'] ? '' : '!';
            $if->append($sign . 'App::frontend()->context()->posts->isExtended()');
        }

        if (isset($attr['selected'])) {
            $sign = (bool) $attr['selected'] ? '' : '!';
            $if->append($sign . '(bool)App::frontend()->context()->posts->post_selected');
        }

        if (isset($attr['has_category'])) {
            $sign = (bool) $attr['has_category'] ? '' : '!';
            $if->append($sign . 'App::frontend()->context()->posts->cat_id');
        }

        if (isset($attr['comments_active'])) {
            $sign = (bool) $attr['comments_active'] ? '' : '!';
            $if->append($sign . 'App::frontend()->context()->posts->commentsActive()');
        }

        if (isset($attr['pings_active'])) {
            $sign = (bool) $attr['pings_active'] ? '' : '!';
            $if->append($sign . 'App::frontend()->context()->posts->trackbacksActive()');
        }

        if (isset($attr['has_comment'])) {
            $sign = (bool) $attr['has_comment'] ? '' : '!';
            $if->append($sign . 'App::frontend()->context()->posts->hasComments()');
        }

        if (isset($attr['has_ping'])) {
            $sign = (bool) $attr['has_ping'] ? '' : '!';
            $if->append($sign . 'App::frontend()->context()->posts->hasTrackbacks()');
        }

        if (isset($attr['show_comments'])) {
            if ((bool) $attr['show_comments']) {
                $if->append('(App::frontend()->context()->posts->hasComments() || App::frontend()->context()->posts->commentsActive())');
            } else {
                $if->append('(!App::frontend()->context()->posts->hasComments() && !App::frontend()->context()->posts->commentsActive())');
            }
        }

        if (isset($attr['show_pings'])) {
            if ((bool) $attr['show_pings']) {
                $if->append('(App::frontend()->context()->posts->hasTrackbacks() || App::frontend()->context()->posts->trackbacksActive())');
            } else {
                $if->append('(!App::frontend()->context()->posts->hasTrackbacks() && !App::frontend()->context()->posts->trackbacksActive())');
            }
        }

        if (isset($attr['republished'])) {
            $sign = (bool) $attr['republished'] ? '' : '!';
            $if->append($sign . '(bool)App::frontend()->context()->posts->isRepublished()');
        }

        if (isset($attr['author']) && is_string($attr['author'])) {
            $author = trim($attr['author']);
            if (str_starts_with($author, '!')) {
                $author = substr($author, 1);
                $if->append('App::frontend()->context()->posts->user_id != "' . $author . '"');
            } else {
                $if->append('App::frontend()->context()->posts->user_id == "' . $author . '"');
            }
        }

        # --BEHAVIOR-- templatePrepareParams -- string, ArrayObject, array<int,string>
        App::behavior()->callBehavior('tplIfConditions', 'EntryIf', $attr, $content, $if);

        if (count($if) > 0) {
            return '<?php if(' . implode(' ' . $operator . ' ', $if->getArrayCopy()) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /**
     * tpl:EntryIfFirst [attributes] : Displays value if entry is the first one (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: first)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryIfFirst(ArrayObject $attr): string
    {
        $ret = isset($attr['return']) && is_string($ret = $attr['return']) ? $ret : 'first';
        $ret = Html::escapeHTML($ret);

        return '<?php if (App::frontend()->context()->posts->index() == 0) { echo \'' . addslashes($ret) . "'; } ?>";
    }

    /**
     * tpl:EntryIfOdd [attributes] : Displays value if entry is in odd position (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: odd)
     *      - even        string      Value to display if not (default: <empty>)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryIfOdd(ArrayObject $attr): string
    {
        $odd = isset($attr['return']) && is_string($odd = $attr['return']) ? $odd : 'odd';
        $odd = Html::escapeHTML($odd);

        $even = isset($attr['even']) && is_string($even = $attr['even']) ? $even : '';
        $even = Html::escapeHTML($even);

        return '<?= ((App::frontend()->context()->posts->index()+1)%2 ? "' . addslashes($odd) . '" : "' . addslashes($even) . '") ?>';
    }

    /**
     * tpl:EntryIfEven [attributes] : Displays value if entry is in even position (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: even)
     *      - odd         string      Value to display if not (default: <empty>)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryIfEven(ArrayObject $attr): string
    {
        $even = isset($attr['return']) && is_string($even = $attr['return']) ? $even : 'even';
        $even = Html::escapeHTML($even);

        $odd = isset($attr['odd']) && is_string($odd = $attr['odd']) ? $odd : '';
        $odd = Html::escapeHTML($odd);

        return '<?= ((App::frontend()->context()->posts->index()+1)%2+1 ? "' . addslashes($even) . '" : "' . addslashes($odd) . '") ?>';
    }

    /**
     * tpl:EntryIfSelected [attributes] : Displays value if entry is selected (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: selected)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryIfSelected(ArrayObject $attr): string
    {
        $ret = isset($attr['return']) && is_string($ret = $attr['return']) ? $ret : 'selected';
        $ret = Html::escapeHTML($ret);

        return '<?php if (App::frontend()->context()->posts->post_selected) { echo \'' . addslashes($ret) . "'; } ?>";
    }

    /**
     * tpl:EntryContent [attributes] : Displays entry content (tpl value)
     *
     * attributes:
     *
     *      - absolute_urls   (1|0)   Transforms local URLs to absolute one
     *      - full            (1|0)   Returns full content with excerpt
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryContent(ArrayObject $attr): string
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        $filters = $this->getFilters($attr);

        if (!empty($attr['full'])) {
            return '<?= ' . sprintf(
                $filters,
                'App::frontend()->context()->posts->getExcerpt(' . $urls . ').' . '(strlen(App::frontend()->context()->posts->getExcerpt(' . $urls . ')) ? " " : "").' . 'App::frontend()->context()->posts->getContent(' . $urls . ')'
            ) . ' ?>';
        }

        return '<?= ' . sprintf(
            $filters,
            'App::frontend()->context()->posts->getContent(' . $urls . ')'
        ) . ' ?>';
    }

    /**
     * tpl:EntryIfContentCut [attributes] : Displays ccontent if entry content has been cut (tpl block)
     *
     * attributes:
     *
     *      - cut_string      int     Cut length, see self::getFilters()
     *      - absolute_urls   (1|0)   Transforms local URLs to absolute one
     *      - full            (1|0)   Returns full content with excerpt
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function EntryIfContentCut(ArrayObject $attr, string $content): string
    {
        if (empty($attr['cut_string'])) {
            return '';
        }

        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        $short              = $this->getFilters($attr);
        $cut                = $attr['cut_string'];
        $attr['cut_string'] = 0;
        $full               = $this->getFilters($attr);
        $attr['cut_string'] = $cut;

        if (!empty($attr['full'])) {
            return '<?php if (strlen(' . sprintf(
                $full,
                'App::frontend()->context()->posts->getExcerpt(' . $urls . ').' . '(strlen(App::frontend()->context()->posts->getExcerpt(' . $urls . ')) ? " " : "").' . 'App::frontend()->context()->posts->getContent(' . $urls . ')'
            ) . ') > ' . 'strlen(' . sprintf(
                $short,
                'App::frontend()->context()->posts->getExcerpt(' . $urls . ').' . '(strlen(App::frontend()->context()->posts->getExcerpt(' . $urls . ')) ? " " : "").' . 'App::frontend()->context()->posts->getContent(' . $urls . ')'
            ) . ')) : ?>' . $content . '<?php endif; ?>';
        }

        return '<?php if (strlen(' . sprintf(
            $full,
            'App::frontend()->context()->posts->getContent(' . $urls . ')'
        ) . ') > ' . 'strlen(' . sprintf(
            $short,
            'App::frontend()->context()->posts->getContent(' . $urls . ')'
        ) . ')) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:EntryExcerpt [attributes] : Displays entry excerpt (tpl value)
     *
     * attributes:
     *
     *      - absolute_urls   (1|0)   Transforms local URLs to absolute one
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryExcerpt(ArrayObject $attr): string
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->getExcerpt(' . $urls . ')') . ' ?>';
    }

    /**
     * tpl:EntryAuthorCommonName [attributes] : Displays entry author common name (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryAuthorCommonName(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->getAuthorCN()') . ' ?>';
    }

    /**
     * tpl:EntryAuthorDisplayName [attributes] : Displays entry author display name (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryAuthorDisplayName(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->user_displayname') . ' ?>';
    }

    /**
     * tpl:EntryAuthorID [attributes] : Displays entry author ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryAuthorID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->user_id') . ' ?>';
    }

    /**
     * tpl:EntryAuthorEmail [attributes] : Displays entry author email (tpl value)
     *
     * attributes:
     *
     *      - spam_protected  (1|0)   Protect email from spam (default: 1)
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryAuthorEmail(ArrayObject $attr): string
    {
        $protect = 'true';
        if (isset($attr['spam_protected']) && !(bool) $attr['spam_protected']) {
            $protect = 'false';
        }

        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->getAuthorEmail(' . $protect . ')') . ' ?>';
    }

    /**
     * tpl:EntryAuthorEmailMD5 [attributes] : Displays entry author email MD5 sum (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryAuthorEmailMD5(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'md5(App::frontend()->context()->posts->getAuthorEmail(false))') . ' ?>';
    }

    /**
     * tpl:EntryAuthorLink [attributes] : Displays entry author link (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryAuthorLink(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->getAuthorLink()') . ' ?>';
    }

    /**
     * tpl:EntryAuthorURL [attributes] : Displays entry author URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryAuthorURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->user_url') . ' ?>';
    }

    /**
     * tpl:EntryBasename [attributes] : Displays entry basename URL, relative to /post (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryBasename(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->post_url') . ' ?>';
    }

    /**
     * tpl:EntryCategory [attributes] : Displays entry fullname category (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryCategory(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->cat_title') . ' ?>';
    }

    /**
     * tpl:EntryCategoryDescription [attributes] : Displays entry category description (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryCategoryDescription(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->cat_desc') . ' ?>';
    }

    /**
     * tpl:EntryCategoriesBreadcrumb : Current entry category's parents loop (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function EntryCategoriesBreadcrumb(ArrayObject $attr, string $content): string
    {
        return
        "<?php\n" .
        'App::frontend()->context()->categories = App::blog()->getCategoryParents(App::frontend()->context()->posts->cat_id);' . "\n" .
        'while (App::frontend()->context()->categories->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->categories = null; ?>';
    }

    /**
     * tpl:EntryCategoryID [attributes] : Displays entry category ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryCategoryID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->cat_id') . ' ?>';
    }

    /**
     * tpl:EntryCategoryURL [attributes] : Displays entry category URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryCategoryURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->getCategoryURL()') . ' ?>';
    }

    /**
     * tpl:EntryCategoryShortURL [attributes] : Displays entry category short URL, relative to /category/ (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryCategoryShortURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->cat_url') . ' ?>';
    }

    /**
     * tpl:EntryFeedID [attributes] : Displays entry feed ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryFeedID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->getFeedID()') . ' ?>';
    }

    /**
     * tpl:EntryFirstImage [attributes] : Extracts entry first image if exists (tpl value)
     *
     * attributes:
     *
     *      - size            (sq|t|s|m|o)    Image size to extract
     *      - class           string          Class to add on image tag
     *      - with_category   (1|0)           Search in entry category description if present (default 0)
     *      - no_tag          (1|0)           Return image URL without HTML tag (default 0)
     *      - content_only    (1|0)           Search in content entry only, not in excerpt (default 0)
     *      - cat_only        (1|0)           Search in category description only (default 0)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryFirstImage(ArrayObject $attr): string
    {
        $size          = isset($attr['size'])  && is_string($size = $attr['size']) ? $size : '';
        $class         = isset($attr['class']) && is_string($class = $attr['class']) ? $class : '';
        $with_category = empty($attr['with_category']) ? 'false' : 'true';
        $no_tag        = empty($attr['no_tag']) ? 'false' : 'true';
        $content_only  = empty($attr['content_only']) ? 'false' : 'true';
        $cat_only      = empty($attr['cat_only']) ? 'false' : 'true';

        return '<?= ' . Ctx::class . "::EntryFirstImageHelper('" . addslashes($size) . "'," . $with_category . ",'" . addslashes($class) . "'," . $no_tag . ',' . $content_only . ',' . $cat_only . ') ?>';
    }

    /**
     * tpl:IfEntryFirstImage [attributes] : Check if an Tpl:EntruFirstImage will provide something
     *
     * attributes: same as tpl:EntryFirstImage (see above)
     *
     * @param       ArrayObject<array-key, mixed>   $attr       The attribtes
     * @param       string                          $content    The content
     */
    public static function IfEntryFirstImage(ArrayObject $attr, string $content): string
    {
        $size          = empty($attr['size'])  || !is_string($attr['size']) ? '' : $attr['size'];
        $class         = empty($attr['class']) || !is_string($attr['class']) ? '' : $attr['class'];
        $with_category = empty($attr['with_category']) ? 'false' : 'true';
        $no_tag        = empty($attr['no_tag']) ? 'false' : 'true';
        $content_only  = empty($attr['content_only']) ? 'false' : 'true';
        $cat_only      = empty($attr['cat_only']) ? 'false' : 'true';

        return
        '<?php if (' . Ctx::class . "::EntryFirstImageHelper('" . addslashes($size) . "'," . $with_category . ",'" . addslashes($class) . "'," .
        $no_tag . ',' . $content_only . ',' . $cat_only . ") != '') : ?>" .
        $content .
        '<?php endif; ?>';
    }

    /**
     * tpl:EntryID [attributes] : Displays entry ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->post_id') . ' ?>';
    }

    /**
     * tpl:EntryLang [attributes] : Displays entry lang or blog lang if not defined (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryLang(ArrayObject $attr): string
    {
        $filters = $this->getFilters($attr);

        return
        '<?php if (App::frontend()->context()->posts->post_lang) { ' .
        'echo ' . sprintf($filters, 'App::frontend()->context()->posts->post_lang') . '; ' .
        '} else {' .
        'echo ' . sprintf($filters, 'App::blog()->settings()->get("system")->getStr("lang", false)') . '; ' .
            '} ?>';
    }

    /**
     * tpl:EntryNext [attributes] : Next entry block (tpl block)
     *
     * attributes:
     *
     *      - restrict_to_category    (0|1)    Find next post in the same category (default 0)
     *      - restrict_to_lang        (0|1)    Find next post in the same language (default 0)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function EntryNext(ArrayObject $attr, string $content): string
    {
        $restrict_to_category = empty($attr['restrict_to_category']) ? 'false' : 'true';
        $restrict_to_lang     = empty($attr['restrict_to_lang']) ? 'false' : 'true';

        return
        '<?php $next_post = App::blog()->getNextPost(App::frontend()->context()->posts,1,' . $restrict_to_category . ',' . $restrict_to_lang . '); ?>' . "\n" .
        '<?php if ($next_post !== null) : ?>' .
        '<?php App::frontend()->context()->posts = $next_post; unset($next_post);' . "\n" .
        'while (App::frontend()->context()->posts->fetch()) : ?>' .
        $content .
        '<?php endwhile; App::frontend()->context()->posts = null; ?>' .
        "<?php endif; ?>\n";
    }

    /**
     * tpl:EntryPrevious [attributes] : Previous entry block (tpl block)
     *
     * attributes:
     *
     *      - restrict_to_category    (0|1)    Find next post in the same category (default 0)
     *      - restrict_to_lang        (0|1)    Find next post in the same language (default 0)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function EntryPrevious(ArrayObject $attr, string $content): string
    {
        $restrict_to_category = empty($attr['restrict_to_category']) ? 'false' : 'true';
        $restrict_to_lang     = empty($attr['restrict_to_lang']) ? 'false' : 'true';

        return
        '<?php $prev_post = App::blog()->getNextPost(App::frontend()->context()->posts,-1,' . $restrict_to_category . ',' . $restrict_to_lang . '); ?>' . "\n" .
        '<?php if ($prev_post !== null) : ?>' .
        '<?php App::frontend()->context()->posts = $prev_post; unset($prev_post);' . "\n" .
        'while (App::frontend()->context()->posts->fetch()) : ?>' .
        $content .
        '<?php endwhile; App::frontend()->context()->posts = null; ?>' .
        "<?php endif; ?>\n";
    }

    /**
     * tpl:EntryTitle [attributes] : Displays entry title (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryTitle(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->post_title') . ' ?>';
    }

    /**
     * tpl:EntryURL [attributes] : Displays entry URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->posts->getURL()') . ' ?>';
    }

    /**
     * tpl:EntryDate [attributes] : Displays entry date (tpl value)
     *
     * attributes:
     *
     *      - format      string      Date format (see Date::str() by default if iso8601 or rfc822 not specified)
     *      - iso8601     (1|0)       If set, display date in ISO 8601 format
     *      - rfc822      (1|0)       If set, display date in RFC 822 format
     *      - upddt       (1|0)       If set, uses the post update time
     *      - creadt      (1|0)       If set, uses the post creation time (ignored if upddt is set to 1)
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryDate(ArrayObject $attr): string
    {
        $format = '';
        if (!empty($attr['format']) && is_string($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);

        $creadt = isset($attr['creadt']) && is_numeric($creadt = $attr['creadt']) && (bool) $creadt;
        $upddt  = isset($attr['upddt'])  && is_numeric($upddt = $attr['upddt']) && (bool) $upddt;

        $type = $upddt ? 'upddt' : ($creadt ? 'creadt' : '');

        $filters = $this->getFilters($attr);

        if ($rfc822) {
            return '<?= ' .
                sprintf($filters, "App::frontend()->context()->posts->getRFC822Date('" . $type . "')") . ' ?>';
        }

        if ($iso8601) {
            return '<?= ' .
                sprintf($filters, "App::frontend()->context()->posts->getISO8601Date('" . $type . "')") . ' ?>';
        }

        return '<?= ' . sprintf($filters, "App::frontend()->context()->posts->getDate('" . $format . "','" . $type . "')") . ' ?>';
    }

    /**
     * tpl:EntryTime [attributes] : Displays entry date (tpl value)
     *
     * attributes:
     *
     *      - format      string      Time format
     *      - upddt       (1|0)       If set, uses the post update time
     *      - creadt      (1|0)       If set, uses the post creation time
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryTime(ArrayObject $attr): string
    {
        $format = '';
        if (!empty($attr['format']) && is_string($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $type = (empty($attr['creadt']) ? '' : 'creadt');
        $type = (empty($attr['upddt']) ? $type : 'upddt');

        return '<?= ' . sprintf($this->getFilters($attr), "App::frontend()->context()->posts->getTime('" . $format . "','" . $type . "')") . ' ?>';
    }

    /**
     * tpl:EntriesHeader : First entries result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function EntriesHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->posts->isStart()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:EntriesFooter : Last entries result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function EntriesFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->posts->isEnd()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:EntryCommentCount [attributes] : Number of comments for entry (tpl value)
     *
     * attributes:
     *
     *      - none        string      Text to display for "no comments" (default: no comments)
     *      - one         string      Text to display for "one comment" (default: one comment)
     *      - more        string      Text to display for "more comments" (default: %s comments, see note 1)
     *      - count_all   (1|0)       Count comments plus trackbacks
     *
     *      Notes:
     *
     *      1) %s will be replaced by the number of comments
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryCommentCount(ArrayObject $attr): string
    {
        if (empty($attr['count_all'])) {
            $operation = 'App::frontend()->context()->posts->nb_comment';
        } else {
            $operation = '(App::frontend()->context()->posts->nb_comment + App::frontend()->context()->posts->nb_trackback)';
        }

        return $this->displayCounter(
            $operation,
            [
                'none' => 'no comments',
                'one'  => 'one comment',
                'more' => '%d comments',
            ],
            $attr,
            false
        );
    }

    /**
     * tpl:EntryPingCount [attributes] : Number of pings (see note 1) for entry (tpl value)
     *
     * attributes:
     *
     *      - none        string      Text to display for "no pings" (default: no pings)
     *      - one         string      Text to display for "one ping" (default: one ping)
     *      - more        string      Text to display for "more pings" (default: %s pings, see note 2)
     *
     *      Notes:
     *
     *      1) A ping may be a trackback, a pingback or a webmention
     *      2) %s will be replaced by the number of pings
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryPingCount(ArrayObject $attr): string
    {
        return $this->displayCounter(
            'App::frontend()->context()->posts->nb_trackback',
            [
                'none' => 'no trackbacks',
                'one'  => 'one trackback',
                'more' => '%d trackbacks',
            ],
            $attr,
            false
        );
    }

    /**
     * tpl:EntryPingData [attributes] : Display trackback RDF information (tpl value)
     *
     * attributes:
     *
     *      - format      (xml|html)  Format (default: html)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryPingData(ArrayObject $attr): string
    {
        $format = isset($attr['format']) && is_string($format = $attr['format']) && $format === 'xml' ? $format : 'html';

        return "<?php if (App::frontend()->context()->posts->trackbacksActive()) { echo App::frontend()->context()->posts->getTrackbackData('" . $format . "'); } ?>\n";
    }

    /**
     * tpl:EntryPingLink : Display trackback link (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function EntryPingLink(ArrayObject $attr): string
    {
        return "<?php if (App::frontend()->context()->posts->trackbacksActive()) { echo App::frontend()->context()->posts->getTrackbackLink(); } ?>\n";
    }
}
