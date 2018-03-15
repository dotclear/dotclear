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

if (!defined('DC_RC_PATH')) {return;}

# Localized string we find in template
__("This tag's comments Atom feed");
__("This tag's entries Atom feed");

require dirname(__FILE__) . '/_widgets.php';

$core->tpl->addBlock('Tags', array('tplTags', 'Tags'));
$core->tpl->addBlock('TagsHeader', array('tplTags', 'TagsHeader'));
$core->tpl->addBlock('TagsFooter', array('tplTags', 'TagsFooter'));
$core->tpl->addBlock('EntryTags', array('tplTags', 'EntryTags'));
$core->tpl->addBlock('TagIf', array('tplTags', 'TagIf'));
$core->tpl->addValue('TagID', array('tplTags', 'TagID'));
$core->tpl->addValue('TagCount', array('tplTags', 'TagCount'));
$core->tpl->addValue('TagPercent', array('tplTags', 'TagPercent'));
$core->tpl->addValue('TagRoundPercent', array('tplTags', 'TagRoundPercent'));
$core->tpl->addValue('TagURL', array('tplTags', 'TagURL'));
$core->tpl->addValue('TagCloudURL', array('tplTags', 'TagCloudURL'));
$core->tpl->addValue('TagFeedURL', array('tplTags', 'TagFeedURL'));

# Kept for backward compatibility (for now)
$core->tpl->addBlock('MetaData', array('tplTags', 'Tags'));
$core->tpl->addBlock('MetaDataHeader', array('tplTags', 'TagsHeader'));
$core->tpl->addBlock('MetaDataFooter', array('tplTags', 'TagsFooter'));
$core->tpl->addValue('MetaID', array('tplTags', 'TagID'));
$core->tpl->addValue('MetaPercent', array('tplTags', 'TagPercent'));
$core->tpl->addValue('MetaRoundPercent', array('tplTags', 'TagRoundPercent'));
$core->tpl->addValue('MetaURL', array('tplTags', 'TagURL'));
$core->tpl->addValue('MetaAllURL', array('tplTags', 'TagCloudURL'));
$core->tpl->addBlock('EntryMetaData', array('tplTags', 'EntryTags'));

$core->addBehavior('templateBeforeBlock', array('behaviorsTags', 'templateBeforeBlock'));
$core->addBehavior('publicBeforeDocument', array('behaviorsTags', 'addTplPath'));

class behaviorsTags
{
    public static function templateBeforeBlock($core, $b, $attr)
    {
        if (($b == 'Entries' || $b == 'Comments') && isset($attr['tag'])) {
            return
            "<?php\n" .
            "if (!isset(\$params)) { \$params = array(); }\n" .
            "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
            "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
            "\$params['from'] .= ', '.\$core->prefix.'meta META ';\n" .
            "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
            "\$params['sql'] .= \"AND META.meta_type = 'tag' \";\n" .
            "\$params['sql'] .= \"AND META.meta_id = '" . $core->con->escape($attr['tag']) . "' \";\n" .
                "?>\n";
        } elseif (empty($attr['no_context']) && ($b == 'Entries' || $b == 'Comments')) {
            return
                '<?php if ($_ctx->exists("meta") && ($_ctx->meta->meta_type == "tag")) { ' .
                "if (!isset(\$params)) { \$params = array(); }\n" .
                "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
                "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
                "\$params['from'] .= ', '.\$core->prefix.'meta META ';\n" .
                "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
                "\$params['sql'] .= \"AND META.meta_type = 'tag' \";\n" .
                "\$params['sql'] .= \"AND META.meta_id = '\".\$core->con->escape(\$_ctx->meta->meta_id).\"' \";\n" .
                "} ?>\n";
        }
    }

    public static function addTplPath($core)
    {
        $tplset = $core->themes->moduleInfo($core->blog->settings->system->theme, 'tplset');
        if (!empty($tplset) && is_dir(dirname(__FILE__) . '/default-templates/' . $tplset)) {
            $core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__) . '/default-templates/' . $tplset);
        } else {
            $core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__) . '/default-templates/' . DC_DEFAULT_TPLSET);
        }
    }

}

class tplTags
{
    public static function Tags($attr, $content)
    {
        $type = isset($attr['type']) ? addslashes($attr['type']) : 'tag';

        $limit = isset($attr['limit']) ? (integer) $attr['limit'] : 'null';

        $combo = array('meta_id_lower', 'count', 'latest', 'oldest');

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && in_array($attr['sortby'], $combo)) {
            $sortby = strtolower($attr['sortby']);
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] == 'desc') {
            $order = 'desc';
        }

        $res =
            "<?php\n" .
            "\$_ctx->meta = \$core->meta->computeMetaStats(\$core->meta->getMetadata(array('meta_type'=>'"
            . $type . "','limit'=>" . $limit .
            ($sortby != 'meta_id_lower' ? ",'order'=>'" . $sortby . ' ' . ($order == 'asc' ? 'ASC' : 'DESC' ) . "'" : '')  .
            "))); " .
            "\$_ctx->meta->sort('" . $sortby . "','" . $order . "'); " .
            '?>';

        $res .=
            '<?php while ($_ctx->meta->fetch()) : ?>' . $content . '<?php endwhile; ' .
            '$_ctx->meta = null; ?>';

        return $res;
    }

    public static function TagsHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->meta->isStart()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    public static function TagsFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->meta->isEnd()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    public static function EntryTags($attr, $content)
    {
        $type = isset($attr['type']) ? addslashes($attr['type']) : 'tag';

        $combo = array('meta_id_lower', 'count', 'latest', 'oldest');

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && in_array($attr['sortby'], $combo)) {
            $sortby = strtolower($attr['sortby']);
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] == 'desc') {
            $order = 'desc';
        }

        $res =
            "<?php\n" .
            "\$_ctx->meta = \$core->meta->getMetaRecordset(\$_ctx->posts->post_meta,'" . $type . "'); " .
            "\$_ctx->meta->sort('" . $sortby . "','" . $order . "'); " .
            '?>';

        $res .=
            '<?php while ($_ctx->meta->fetch()) : ?>' . $content . '<?php endwhile; ' .
            '$_ctx->meta = null; ?>';

        return $res;
    }

    public static function TagIf($attr, $content)
    {
        $if        = array();
        $operateur = isset($attr['operator']) ? dcTemplate::getOperator($attr['operator']) : '&&';

        if (isset($attr['has_entries'])) {
            $sign = (boolean) $attr['has_entries'] ? '' : '!';
            $if[] = $sign . '$_ctx->meta->count';
        }

        if (!empty($if)) {
            return '<?php if(' . implode(' ' . $operateur . ' ', $if) . ') : ?>' . $content . '<?php endif; ?>';
        } else {
            return $content;
        }
    }

    public static function TagID($attr)
    {
        $f = $GLOBALS['core']->tpl->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->meta->meta_id') . '; ?>';
    }

    public static function TagCount($attr)
    {
        return '<?php echo $_ctx->meta->count; ?>';
    }

    public static function TagPercent($attr)
    {
        return '<?php echo $_ctx->meta->percent; ?>';
    }

    public static function TagRoundPercent($attr)
    {
        return '<?php echo $_ctx->meta->roundpercent; ?>';
    }

    public static function TagURL($attr)
    {
        $f = $GLOBALS['core']->tpl->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("tag",' .
            'rawurlencode($_ctx->meta->meta_id))') . '; ?>';
    }

    public static function TagCloudURL($attr)
    {
        $f = $GLOBALS['core']->tpl->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("tags")') . '; ?>';
    }

    public static function TagFeedURL($attr)
    {
        $type = !empty($attr['type']) ? $attr['type'] : 'rss2';

        if (!preg_match('#^(rss2|atom)$#', $type)) {
            $type = 'rss2';
        }

        $f = $GLOBALS['core']->tpl->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("tag_feed",' .
            'rawurlencode($_ctx->meta->meta_id)."/' . $type . '")') . '; ?>';
    }

    # Widget function
    public static function tagsWidget($w)
    {
        global $core, $_ctx;

        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && $core->url->type != 'default') ||
            ($w->homeonly == 2 && $core->url->type == 'default')) {
            return;
        }

        $combo = array('meta_id_lower', 'count', 'latest', 'oldest');

        $sort = $w->sortby;
        if (!in_array($sort, $combo)) {
            $sort = 'meta_id_lower';
        }

        $order = $w->orderby;
        if ($order != 'asc') {
            $order = 'desc';
        }

        $params = array('meta_type' => 'tag');

        if ($sort != 'meta_id_lower') {
            // As optional limit may restrict result, we should set order (if not computed after)
            $params['order'] = $sort . ' ' . ($order == 'asc' ? 'ASC' : 'DESC');
        }

        if ($w->limit !== '') {
            $params['limit'] = abs((integer) $w->limit);
        }

        $rs = $core->meta->computeMetaStats(
            $core->meta->getMetadata($params));

        if ($rs->isEmpty()) {
            return;
        }

        if ($sort == 'meta_id_lower') {
            // Sort resulting recordset on cleaned id
            $rs->sort($sort, $order);
        }

        $res =
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            '<ul>';

        if ($core->url->type == 'post' && $_ctx->posts instanceof record) {
            $_ctx->meta = $core->meta->getMetaRecordset($_ctx->posts->post_meta, 'tag');
        }
        while ($rs->fetch()) {
            $class = '';
            if ($core->url->type == 'post' && $_ctx->posts instanceof record) {
                while ($_ctx->meta->fetch()) {
                    if ($_ctx->meta->meta_id == $rs->meta_id) {
                        $class = ' class="tag-current"';
                        break;
                    }
                }
            }
            $res .=
            '<li' . $class . '><a href="' . $core->blog->url . $core->url->getURLFor('tag', rawurlencode($rs->meta_id)) . '" ' .
            'class="tag' . $rs->roundpercent . '">' .
            $rs->meta_id . '</a> </li>';
        }

        $res .= '</ul>';

        if ($core->url->getURLFor('tags') && !is_null($w->alltagslinktitle) && $w->alltagslinktitle !== '') {
            $res .=
            '<p><strong><a href="' . $core->blog->url . $core->url->getURLFor("tags") . '">' .
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
            $type     = $m[2] == 'atom' ? 'atom' : 'rss2';
            $mime     = 'application/xml';
            $comments = !empty($m[3]);

            $GLOBALS['_ctx']->meta = $GLOBALS['core']->meta->computeMetaStats(
                $GLOBALS['core']->meta->getMetadata(array(
                    'meta_type' => 'tag',
                    'meta_id'   => $m[1])));

            if ($GLOBALS['_ctx']->meta->isEmpty()) {
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
                $GLOBALS['_page_number'] = $n;
            }

            $GLOBALS['_ctx']->meta = $GLOBALS['core']->meta->computeMetaStats(
                $GLOBALS['core']->meta->getMetadata(array(
                    'meta_type' => 'tag',
                    'meta_id'   => $args)));

            if ($GLOBALS['_ctx']->meta->isEmpty()) {
                self::p404();
            } else {
                self::serveDocument('tag.html');
            }
        }
    }

    public static function tags($args)
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

            $GLOBALS['_ctx']->meta = $GLOBALS['core']->meta->computeMetaStats(
                $GLOBALS['core']->meta->getMetadata(array(
                    'meta_type' => 'tag',
                    'meta_id'   => $tag)));

            if ($GLOBALS['_ctx']->meta->isEmpty()) {
                # The specified tag does not exist.
                self::p404();
            } else {
                $GLOBALS['_ctx']->feed_subtitle = ' - ' . __('Tag') . ' - ' . $GLOBALS['_ctx']->meta->meta_id;

                if ($type == 'atom') {
                    $mime = 'application/atom+xml';
                } else {
                    $mime = 'application/xml';
                }

                $tpl = $type;
                if ($comments) {
                    $tpl .= '-comments';
                    $GLOBALS['_ctx']->nb_comment_per_page = $GLOBALS['core']->blog->settings->system->nb_comment_per_feed;
                } else {
                    $GLOBALS['_ctx']->nb_entry_per_page = $GLOBALS['core']->blog->settings->system->nb_post_per_feed;
                    $GLOBALS['_ctx']->short_feed_items  = $GLOBALS['core']->blog->settings->system->short_feed_items;
                }
                $tpl .= '.xml';

                self::serveDocument($tpl, $mime);
            }
        }
    }
}
