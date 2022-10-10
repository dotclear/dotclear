<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class tplTags
{
    /**
     * tpl:Tags [attributes] : Tags loop (tpl block)
     *
     * attributes:
     *
     *      - type           metadata type                          Type of metadata to list, default to "tag"
     *      - limit          int                                    Max number of metadata in list
     *      - order          (asc|desc)                             Sort asc or desc
     *      - sortby         (meta_id_lower|count|latest|oldest)    Sort on information
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function Tags(ArrayObject $attr, string $content): string
    {
        $type = isset($attr['type']) ? addslashes($attr['type']) : 'tag';

        $limit = isset($attr['limit']) ? (int) $attr['limit'] : 'null';

        $combo = ['meta_id_lower', 'count', 'latest', 'oldest'];

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && in_array($attr['sortby'], $combo)) {
            $sortby = strtolower($attr['sortby']);
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] === 'desc') {
            $order = 'desc';
        }

        $res = "<?php\n" .
        "dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(dcCore::app()->meta->getMetadata(['meta_type'=>'" . $type . "','limit'=>" . $limit . ($sortby !== 'meta_id_lower' ? ",'order'=>'" . $sortby . ' ' . ($order === 'asc' ? 'ASC' : 'DESC') . "'" : '') . '])); ' . "\n" .
        "dcCore::app()->ctx->meta->sort('" . $sortby . "','" . $order . "'); " . "\n" .
        'while (dcCore::app()->ctx->meta->fetch()) : ?>' . "\n" .
        $content .
        '<?php endwhile; ' . "\n" .
        'dcCore::app()->ctx->meta = null; ?>';

        return $res;
    }

    /**
     * tpl:TagsHeader : Tags header (tpl block)
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function TagsHeader(ArrayObject $attr, string $content): string
    {
        return
        '<?php if (dcCore::app()->ctx->meta->isStart()) : ?>' .
        $content .
        '<?php endif; ?>';
    }

    /**
     * tpl:TagsFooter : Tags footer (tpl block)
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function TagsFooter(ArrayObject $attr, string $content): string
    {
        return
        '<?php if (dcCore::app()->ctx->meta->isEnd()) : ?>' .
        $content .
        '<?php endif; ?>';
    }

    /**
     * tpl:EntryTags [attributes] : Entry tags loop (tpl block)
     *
     * attributes:
     *
     *      - type           metadata type                          Type of metadata to list, default to "tag"
     *      - order          (asc|desc)                             Sort asc or desc
     *      - sortby         (meta_id_lower|count|latest|oldest)    Sort on information
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function EntryTags(ArrayObject $attr, string $content): string
    {
        $type = isset($attr['type']) ? addslashes($attr['type']) : 'tag';

        $combo = ['meta_id_lower', 'count', 'latest', 'oldest'];

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && in_array($attr['sortby'], $combo)) {
            $sortby = strtolower($attr['sortby']);
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] === 'desc') {
            $order = 'desc';
        }

        $res = "<?php\n" .
            "dcCore::app()->ctx->meta = dcCore::app()->meta->getMetaRecordset(dcCore::app()->ctx->posts->post_meta,'" . $type . "'); " .
            "dcCore::app()->ctx->meta->sort('" . $sortby . "','" . $order . "'); " .
            '?>';

        $res .= '<?php while (dcCore::app()->ctx->meta->fetch()) : ?>' . $content . '<?php endwhile; ' .
            'dcCore::app()->ctx->meta = null; ?>';

        return $res;
    }

    /**
     * tpl:TagIf [attributes] : Includes content depending on tag test (tpl block)
     *
     * attributes:
     *
     *      - has_entry       (0|1)                   Categories are set in current context (if 1) or not (if 0)
     *      - operator        (and|or)                Combination of conditions, if more than 1 specifiec (default: and)
     *
     * Notes:
     *
     *  1) Prefix with a ! to reverse test
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function TagIf($attr, $content)
    {
        $if        = [];
        $operateur = isset($attr['operator']) ? dcTemplate::getOperator($attr['operator']) : '&&';

        if (isset($attr['has_entries'])) {
            $sign = (bool) $attr['has_entries'] ? '' : '!';
            $if[] = $sign . 'dcCore::app()->ctx->meta->count';
        }

        if (!empty($if)) {
            return '<?php if(' . implode(' ' . $operateur . ' ', $if) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /**
     * tpl:TagID [attributes] : Tag ID (tpl value)
     *
     * attributes:
     *
     *      - any filters                 See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function TagID(ArrayObject $attr): string
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->ctx->meta->meta_id') . '; ?>';
    }

    /**
     * tpl:TagCount : Tag count (tpl value)
     *
     * @return     string
     */
    public static function TagCount()
    {
        return '<?php echo dcCore::app()->ctx->meta->count; ?>';
    }

    /**
     * tpl:TagPercent : Tag percentage usage (tpl value)
     *
     * @return     string
     */
    public static function TagPercent()
    {
        return '<?php echo dcCore::app()->ctx->meta->percent; ?>';
    }

    /**
     * tpl:TagRoundPercent : Tag rounded percentage usage (tpl value)
     *
     * @return     string
     */
    public static function TagRoundPercent()
    {
        return '<?php echo dcCore::app()->ctx->meta->roundpercent; ?>';
    }

    /**
     * tpl:TagURL [attributes] : Tag URL (tpl value)
     *
     * attributes:
     *
     *      - any filters                 See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function TagURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getURLFor("tag",' .
            'rawurlencode(dcCore::app()->ctx->meta->meta_id))') . '; ?>';
    }

    /**
     * tpl:TagCloudURL [attributes] : All tags URL (tpl value)
     *
     * attributes:
     *
     *      - any filters                 See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function TagCloudURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getURLFor("tags")') . '; ?>';
    }

    /**
     * tpl:TagFeedURL [attributes] : Tag feed URL (tpl value)
     *
     * attributes:
     *
     *      - type       (atom|rss2)      Feed type, default to 'rss2'
     *      - any filters                 See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function TagFeedURL($attr)
    {
        $type = !empty($attr['type']) ? $attr['type'] : 'rss2';

        if (!preg_match('#^(rss2|atom)$#', $type)) {
            $type = 'rss2';
        }

        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getURLFor("tag_feed",' .
            'rawurlencode(dcCore::app()->ctx->meta->meta_id)."/' . $type . '")') . '; ?>';
    }

    /**
     * Widget public rendering helper
     *
     * @param      dcWidget  $widget  The widget
     *
     * @return     string
     */
    public static function tagsWidget(dcWidget $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(dcCore::app()->url->type)) {
            return '';
        }

        $combo = ['meta_id_lower', 'count', 'latest', 'oldest'];

        $sort = $widget->sortby;
        if (!in_array($sort, $combo)) {
            $sort = 'meta_id_lower';
        }

        $order = $widget->orderby;
        if ($order != 'asc') {
            $order = 'desc';
        }

        $params = ['meta_type' => 'tag'];

        if ($sort != 'meta_id_lower') {
            // As optional limit may restrict result, we should set order (if not computed after)
            $params['order'] = $sort . ' ' . ($order == 'asc' ? 'ASC' : 'DESC');
        }

        if ($widget->limit !== '') {
            $params['limit'] = abs((int) $widget->limit);
        }

        $rs = dcCore::app()->meta->computeMetaStats(
            dcCore::app()->meta->getMetadata($params)
        );

        if ($rs->isEmpty()) {
            return '';
        }

        if ($sort == 'meta_id_lower') {
            // Sort resulting recordset on cleaned id
            $rs->sort($sort, $order);
        }

        $res = ($widget->title ? $widget->renderTitle(html::escapeHTML($widget->title)) : '') .
            '<ul>';

        if (dcCore::app()->url->type == 'post' && dcCore::app()->ctx->posts instanceof dcRecord) {
            dcCore::app()->ctx->meta = dcCore::app()->meta->getMetaRecordset(dcCore::app()->ctx->posts->post_meta, 'tag');
        }
        while ($rs->fetch()) {
            $class = '';
            if (dcCore::app()->url->type == 'post' && dcCore::app()->ctx->posts instanceof dcRecord) {
                while (dcCore::app()->ctx->meta->fetch()) {
                    if (dcCore::app()->ctx->meta->meta_id == $rs->meta_id) {
                        $class = ' class="tag-current"';

                        break;
                    }
                }
            }
            $res .= '<li' . $class . '><a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('tag', rawurlencode($rs->meta_id)) . '" ' .
            'class="tag' . $rs->roundpercent . '">' .
            $rs->meta_id . '</a> </li>';
        }

        $res .= '</ul>';

        if (dcCore::app()->url->getURLFor('tags') && !is_null($widget->alltagslinktitle) && $widget->alltagslinktitle !== '') {
            $res .= '<p><strong><a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('tags') . '">' .
            html::escapeHTML($widget->alltagslinktitle) . '</a></strong></p>';
        }

        return $widget->renderDiv($widget->content_only, 'tags ' . $widget->class, '', $res);
    }
}
