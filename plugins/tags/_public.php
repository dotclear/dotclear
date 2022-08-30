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

# Localized string we find in template
__("This tag's comments Atom feed");
__("This tag's entries Atom feed");

require __DIR__ . '/_widgets.php';

dcCore::app()->tpl->addBlock('Tags', ['tplTags', 'Tags']);
dcCore::app()->tpl->addBlock('TagsHeader', ['tplTags', 'TagsHeader']);
dcCore::app()->tpl->addBlock('TagsFooter', ['tplTags', 'TagsFooter']);
dcCore::app()->tpl->addBlock('EntryTags', ['tplTags', 'EntryTags']);
dcCore::app()->tpl->addBlock('TagIf', ['tplTags', 'TagIf']);
dcCore::app()->tpl->addValue('TagID', ['tplTags', 'TagID']);
dcCore::app()->tpl->addValue('TagCount', ['tplTags', 'TagCount']);
dcCore::app()->tpl->addValue('TagPercent', ['tplTags', 'TagPercent']);
dcCore::app()->tpl->addValue('TagRoundPercent', ['tplTags', 'TagRoundPercent']);
dcCore::app()->tpl->addValue('TagURL', ['tplTags', 'TagURL']);
dcCore::app()->tpl->addValue('TagCloudURL', ['tplTags', 'TagCloudURL']);
dcCore::app()->tpl->addValue('TagFeedURL', ['tplTags', 'TagFeedURL']);

# Kept for backward compatibility (for now)
dcCore::app()->tpl->addBlock('MetaData', ['tplTags', 'Tags']);
dcCore::app()->tpl->addBlock('MetaDataHeader', ['tplTags', 'TagsHeader']);
dcCore::app()->tpl->addBlock('MetaDataFooter', ['tplTags', 'TagsFooter']);
dcCore::app()->tpl->addValue('MetaID', ['tplTags', 'TagID']);
dcCore::app()->tpl->addValue('MetaPercent', ['tplTags', 'TagPercent']);
dcCore::app()->tpl->addValue('MetaRoundPercent', ['tplTags', 'TagRoundPercent']);
dcCore::app()->tpl->addValue('MetaURL', ['tplTags', 'TagURL']);
dcCore::app()->tpl->addValue('MetaAllURL', ['tplTags', 'TagCloudURL']);
dcCore::app()->tpl->addBlock('EntryMetaData', ['tplTags', 'EntryTags']);

dcCore::app()->addBehavior('templateBeforeBlockV2', ['behaviorsTags', 'templateBeforeBlock']);
dcCore::app()->addBehavior('publicBeforeDocumentV2', ['behaviorsTags', 'addTplPath']);

class behaviorsTags
{
    public static function templateBeforeBlock($b, $attr)
    {
        if (($b == 'Entries' || $b == 'Comments') && isset($attr['tag'])) {
            return
            "<?php\n" .
            "if (!isset(\$params)) { \$params = []; }\n" .
            "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
            "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
            "\$params['from'] .= ', '.dcCore::app()->prefix.'meta META ';\n" .
            "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
            "\$params['sql'] .= \"AND META.meta_type = 'tag' \";\n" .
            "\$params['sql'] .= \"AND META.meta_id = '" . dcCore::app()->con->escape($attr['tag']) . "' \";\n" .
                "?>\n";
        } elseif (empty($attr['no_context']) && ($b == 'Entries' || $b == 'Comments')) {
            return
                '<?php if (dcCore::app()->ctx->exists("meta") && dcCore::app()->ctx->meta->rows() && (dcCore::app()->ctx->meta->meta_type == "tag")) { ' .
                "if (!isset(\$params)) { \$params = []; }\n" .
                "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
                "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
                "\$params['from'] .= ', '.dcCore::app()->prefix.'meta META ';\n" .
                "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
                "\$params['sql'] .= \"AND META.meta_type = 'tag' \";\n" .
                "\$params['sql'] .= \"AND META.meta_id = '\".dcCore::app()->con->escape(dcCore::app()->ctx->meta->meta_id).\"' \";\n" .
                "} ?>\n";
        }
    }

    public static function addTplPath()
    {
        $tplset = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'tplset');
        if (!empty($tplset) && is_dir(__DIR__ . '/default-templates/' . $tplset)) {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates/' . $tplset);
        } else {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates/' . DC_DEFAULT_TPLSET);
        }
    }
}

class tplTags
{
    public static function Tags($attr, $content)
    {
        $type = isset($attr['type']) ? addslashes($attr['type']) : 'tag';

        $limit = isset($attr['limit']) ? (int) $attr['limit'] : 'null';

        $combo = ['meta_id_lower', 'count', 'latest', 'oldest'];

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && in_array($attr['sortby'], $combo)) {
            $sortby = strtolower($attr['sortby']);
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] == 'desc') {
            $order = 'desc';
        }

        $res = "<?php\n" .
            "dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(dcCore::app()->meta->getMetadata(['meta_type'=>'"
            . $type . "','limit'=>" . $limit .
            ($sortby != 'meta_id_lower' ? ",'order'=>'" . $sortby . ' ' . ($order == 'asc' ? 'ASC' : 'DESC') . "'" : '') .
            '])); ' .
            "dcCore::app()->ctx->meta->sort('" . $sortby . "','" . $order . "'); " .
            '?>';

        $res .= '<?php while (dcCore::app()->ctx->meta->fetch()) : ?>' . $content . '<?php endwhile; ' .
            'dcCore::app()->ctx->meta = null; ?>';

        return $res;
    }

    public static function TagsHeader($attr, $content)
    {
        return
            '<?php if (dcCore::app()->ctx->meta->isStart()) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    public static function TagsFooter($attr, $content)
    {
        return
            '<?php if (dcCore::app()->ctx->meta->isEnd()) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    public static function EntryTags($attr, $content)
    {
        $type = isset($attr['type']) ? addslashes($attr['type']) : 'tag';

        $combo = ['meta_id_lower', 'count', 'latest', 'oldest'];

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && in_array($attr['sortby'], $combo)) {
            $sortby = strtolower($attr['sortby']);
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] == 'desc') {
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

    public static function TagID($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->ctx->meta->meta_id') . '; ?>';
    }

    public static function TagCount()
    {
        return '<?php echo dcCore::app()->ctx->meta->count; ?>';
    }

    public static function TagPercent()
    {
        return '<?php echo dcCore::app()->ctx->meta->percent; ?>';
    }

    public static function TagRoundPercent()
    {
        return '<?php echo dcCore::app()->ctx->meta->roundpercent; ?>';
    }

    public static function TagURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getURLFor("tag",' .
            'rawurlencode(dcCore::app()->ctx->meta->meta_id))') . '; ?>';
    }

    public static function TagCloudURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getURLFor("tags")') . '; ?>';
    }

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

    # Widget function
    public static function tagsWidget($w)
    {
        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type)) || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))) {
            return;
        }

        $combo = ['meta_id_lower', 'count', 'latest', 'oldest'];

        $sort = $w->sortby;
        if (!in_array($sort, $combo)) {
            $sort = 'meta_id_lower';
        }

        $order = $w->orderby;
        if ($order != 'asc') {
            $order = 'desc';
        }

        $params = ['meta_type' => 'tag'];

        if ($sort != 'meta_id_lower') {
            // As optional limit may restrict result, we should set order (if not computed after)
            $params['order'] = $sort . ' ' . ($order == 'asc' ? 'ASC' : 'DESC');
        }

        if ($w->limit !== '') {
            $params['limit'] = abs((int) $w->limit);
        }

        $rs = dcCore::app()->meta->computeMetaStats(
            dcCore::app()->meta->getMetadata($params)
        );

        if ($rs->isEmpty()) {
            return;
        }

        if ($sort == 'meta_id_lower') {
            // Sort resulting recordset on cleaned id
            $rs->sort($sort, $order);
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<ul>';

        if (dcCore::app()->url->type == 'post' && dcCore::app()->ctx->posts instanceof record) {
            dcCore::app()->ctx->meta = dcCore::app()->meta->getMetaRecordset(dcCore::app()->ctx->posts->post_meta, 'tag');
        }
        while ($rs->fetch()) {
            $class = '';
            if (dcCore::app()->url->type == 'post' && dcCore::app()->ctx->posts instanceof record) {
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

        if (dcCore::app()->url->getURLFor('tags') && !is_null($w->alltagslinktitle) && $w->alltagslinktitle !== '') {
            $res .= '<p><strong><a href="' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('tags') . '">' .
            html::escapeHTML($w->alltagslinktitle) . '</a></strong></p>';
        }

        return $w->renderDiv($w->content_only, 'tags ' . $w->class, '', $res);
    }
}

class urlTags extends dcUrlHandlers
{
    public static function tag($args)
    {
        $n = self::getPageNumber($args);

        if ($args == '' && !$n) {
            self::p404();
        } elseif (preg_match('%(.*?)/feed/(rss2|atom)?$%u', $args, $m)) {
            $type = $m[2] == 'atom' ? 'atom' : 'rss2';
            $mime = 'application/xml';

            dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(
                dcCore::app()->meta->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $m[1], ])
            );

            if (dcCore::app()->ctx->meta->isEmpty()) {
                self::p404();
            } else {
                $tpl = $type;

                if ($type == 'atom') {
                    $mime = 'application/atom+xml';
                }

                self::serveDocument($tpl . '.xml', $mime);
            }
        } else {
            if ($n) {
                dcCore::app()->public->setPageNumber($n);
            }

            dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(
                dcCore::app()->meta->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $args, ])
            );

            if (dcCore::app()->ctx->meta->isEmpty()) {
                self::p404();
            } else {
                self::serveDocument('tag.html');
            }
        }
    }

    public static function tags()
    {
        self::serveDocument('tags.html');
    }

    public static function tagFeed($args)
    {
        if (!preg_match('#^(.+)/(atom|rss2)(/comments)?$#', $args, $m)) {
            self::p404();
        } else {
            $tag      = $m[1];
            $type     = $m[2];
            $comments = !empty($m[3]);

            dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(
                dcCore::app()->meta->getMetadata([
                    'meta_type' => 'tag',
                    'meta_id'   => $tag, ])
            );

            if (dcCore::app()->ctx->meta->isEmpty()) {
                # The specified tag does not exist.
                self::p404();
            } else {
                dcCore::app()->ctx->feed_subtitle = ' - ' . __('Tag') . ' - ' . dcCore::app()->ctx->meta->meta_id;

                if ($type == 'atom') {
                    $mime = 'application/atom+xml';
                } else {
                    $mime = 'application/xml';
                }

                $tpl = $type;
                if ($comments) {
                    $tpl .= '-comments';
                    dcCore::app()->ctx->nb_comment_per_page = dcCore::app()->blog->settings->system->nb_comment_per_feed;
                } else {
                    dcCore::app()->ctx->nb_entry_per_page = dcCore::app()->blog->settings->system->nb_post_per_feed;
                    dcCore::app()->ctx->short_feed_items  = dcCore::app()->blog->settings->system->short_feed_items;
                }
                $tpl .= '.xml';

                self::serveDocument($tpl, $mime);
            }
        }
    }
}
