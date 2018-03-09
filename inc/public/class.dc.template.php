<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class dcTemplate extends template
{
    private $core;
    private $current_tag;

    protected $unknown_value_handler = null;
    protected $unknown_block_handler = null;

    public function __construct($cache_dir, $self_name, $core)
    {
        parent::__construct($cache_dir, $self_name);

        $this->remove_php = !$core->blog->settings->system->tpl_allow_php;
        $this->use_cache  = $core->blog->settings->system->tpl_use_cache;

        $this->tag_block = '<tpl:(\w+)(?:(\s+.*?)>|>)((?:[^<]|<(?!/?tpl:\1)|(?R))*)</tpl:\1>';
        $this->tag_value = '{{tpl:(\w+)(\s(.*?))?}}';

        $this->core = &$core;

        # Transitional tags
        $this->addValue('EntryTrackbackCount', array($this, 'EntryPingCount'));
        $this->addValue('EntryTrackbackData', array($this, 'EntryPingData'));
        $this->addValue('EntryTrackbackLink', array($this, 'EntryPingLink'));

        # l10n
        $this->addValue('lang', array($this, 'l10n'));

        # Loops test tags
        $this->addBlock('LoopPosition', array($this, 'LoopPosition'));
        $this->addValue('LoopIndex', array($this, 'LoopIndex'));

        # Archives
        $this->addBlock('Archives', array($this, 'Archives'));
        $this->addBlock('ArchivesHeader', array($this, 'ArchivesHeader'));
        $this->addBlock('ArchivesFooter', array($this, 'ArchivesFooter'));
        $this->addBlock('ArchivesYearHeader', array($this, 'ArchivesYearHeader'));
        $this->addBlock('ArchivesYearFooter', array($this, 'ArchivesYearFooter'));
        $this->addValue('ArchiveDate', array($this, 'ArchiveDate'));
        $this->addBlock('ArchiveNext', array($this, 'ArchiveNext'));
        $this->addBlock('ArchivePrevious', array($this, 'ArchivePrevious'));
        $this->addValue('ArchiveEntriesCount', array($this, 'ArchiveEntriesCount'));
        $this->addValue('ArchiveURL', array($this, 'ArchiveURL'));

        # Blog
        $this->addValue('BlogArchiveURL', array($this, 'BlogArchiveURL'));
        $this->addValue('BlogCopyrightNotice', array($this, 'BlogCopyrightNotice'));
        $this->addValue('BlogDescription', array($this, 'BlogDescription'));
        $this->addValue('BlogEditor', array($this, 'BlogEditor'));
        $this->addValue('BlogFeedID', array($this, 'BlogFeedID'));
        $this->addValue('BlogFeedURL', array($this, 'BlogFeedURL'));
        $this->addValue('BlogRSDURL', array($this, 'BlogRSDURL'));
        $this->addValue('BlogName', array($this, 'BlogName'));
        $this->addValue('BlogLanguage', array($this, 'BlogLanguage'));
        $this->addValue('BlogThemeURL', array($this, 'BlogThemeURL'));
        $this->addValue('BlogParentThemeURL', array($this, 'BlogParentThemeURL'));
        $this->addValue('BlogUpdateDate', array($this, 'BlogUpdateDate'));
        $this->addValue('BlogID', array($this, 'BlogID'));
        $this->addValue('BlogURL', array($this, 'BlogURL'));
        $this->addValue('BlogXMLRPCURL', array($this, 'BlogXMLRPCURL'));
        $this->addValue('BlogPublicURL', array($this, 'BlogPublicURL'));
        $this->addValue('BlogQmarkURL', array($this, 'BlogQmarkURL'));
        $this->addValue('BlogMetaRobots', array($this, 'BlogMetaRobots'));
        $this->addValue('BlogJsJQuery', array($this, 'BlogJsJQuery'));

        # Categories
        $this->addBlock('Categories', array($this, 'Categories'));
        $this->addBlock('CategoriesHeader', array($this, 'CategoriesHeader'));
        $this->addBlock('CategoriesFooter', array($this, 'CategoriesFooter'));
        $this->addBlock('CategoryIf', array($this, 'CategoryIf'));
        $this->addBlock('CategoryFirstChildren', array($this, 'CategoryFirstChildren'));
        $this->addBlock('CategoryParents', array($this, 'CategoryParents'));
        $this->addValue('CategoryFeedURL', array($this, 'CategoryFeedURL'));
        $this->addValue('CategoryURL', array($this, 'CategoryURL'));
        $this->addValue('CategoryShortURL', array($this, 'CategoryShortURL'));
        $this->addValue('CategoryDescription', array($this, 'CategoryDescription'));
        $this->addValue('CategoryTitle', array($this, 'CategoryTitle'));
        $this->addValue('CategoryEntriesCount', array($this, 'CategoryEntriesCount'));

        # Comments
        $this->addBlock('Comments', array($this, 'Comments'));
        $this->addValue('CommentAuthor', array($this, 'CommentAuthor'));
        $this->addValue('CommentAuthorDomain', array($this, 'CommentAuthorDomain'));
        $this->addValue('CommentAuthorLink', array($this, 'CommentAuthorLink'));
        $this->addValue('CommentAuthorMailMD5', array($this, 'CommentAuthorMailMD5'));
        $this->addValue('CommentAuthorURL', array($this, 'CommentAuthorURL'));
        $this->addValue('CommentContent', array($this, 'CommentContent'));
        $this->addValue('CommentDate', array($this, 'CommentDate'));
        $this->addValue('CommentTime', array($this, 'CommentTime'));
        $this->addValue('CommentEmail', array($this, 'CommentEmail'));
        $this->addValue('CommentEntryTitle', array($this, 'CommentEntryTitle'));
        $this->addValue('CommentFeedID', array($this, 'CommentFeedID'));
        $this->addValue('CommentID', array($this, 'CommentID'));
        $this->addBlock('CommentIf', array($this, 'CommentIf'));
        $this->addValue('CommentIfFirst', array($this, 'CommentIfFirst'));
        $this->addValue('CommentIfMe', array($this, 'CommentIfMe'));
        $this->addValue('CommentIfOdd', array($this, 'CommentIfOdd'));
        $this->addValue('CommentIP', array($this, 'CommentIP'));
        $this->addValue('CommentOrderNumber', array($this, 'CommentOrderNumber'));
        $this->addBlock('CommentsFooter', array($this, 'CommentsFooter'));
        $this->addBlock('CommentsHeader', array($this, 'CommentsHeader'));
        $this->addValue('CommentPostURL', array($this, 'CommentPostURL'));
        $this->addBlock('IfCommentAuthorEmail', array($this, 'IfCommentAuthorEmail'));
        $this->addValue('CommentHelp', array($this, 'CommentHelp'));

        # Comment preview
        $this->addBlock('IfCommentPreview', array($this, 'IfCommentPreview'));
        $this->addBlock('IfCommentPreviewOptional', array($this, 'IfCommentPreviewOptional'));
        $this->addValue('CommentPreviewName', array($this, 'CommentPreviewName'));
        $this->addValue('CommentPreviewEmail', array($this, 'CommentPreviewEmail'));
        $this->addValue('CommentPreviewSite', array($this, 'CommentPreviewSite'));
        $this->addValue('CommentPreviewContent', array($this, 'CommentPreviewContent'));
        $this->addValue('CommentPreviewCheckRemember', array($this, 'CommentPreviewCheckRemember'));

        # Entries
        $this->addBlock('DateFooter', array($this, 'DateFooter'));
        $this->addBlock('DateHeader', array($this, 'DateHeader'));
        $this->addBlock('Entries', array($this, 'Entries'));
        $this->addBlock('EntriesFooter', array($this, 'EntriesFooter'));
        $this->addBlock('EntriesHeader', array($this, 'EntriesHeader'));
        $this->addValue('EntryAuthorCommonName', array($this, 'EntryAuthorCommonName'));
        $this->addValue('EntryAuthorDisplayName', array($this, 'EntryAuthorDisplayName'));
        $this->addValue('EntryAuthorEmail', array($this, 'EntryAuthorEmail'));
        $this->addValue('EntryAuthorEmailMD5', array($this, 'EntryAuthorEmailMD5'));
        $this->addValue('EntryAuthorID', array($this, 'EntryAuthorID'));
        $this->addValue('EntryAuthorLink', array($this, 'EntryAuthorLink'));
        $this->addValue('EntryAuthorURL', array($this, 'EntryAuthorURL'));
        $this->addValue('EntryBasename', array($this, 'EntryBasename'));
        $this->addValue('EntryCategory', array($this, 'EntryCategory'));
        $this->addValue('EntryCategoryDescription', array($this, 'EntryCategoryDescription'));
        $this->addBlock('EntryCategoriesBreadcrumb', array($this, 'EntryCategoriesBreadcrumb'));
        $this->addValue('EntryCategoryID', array($this, 'EntryCategoryID'));
        $this->addValue('EntryCategoryURL', array($this, 'EntryCategoryURL'));
        $this->addValue('EntryCategoryShortURL', array($this, 'EntryCategoryShortURL'));
        $this->addValue('EntryCommentCount', array($this, 'EntryCommentCount'));
        $this->addValue('EntryContent', array($this, 'EntryContent'));
        $this->addValue('EntryDate', array($this, 'EntryDate'));
        $this->addValue('EntryExcerpt', array($this, 'EntryExcerpt'));
        $this->addValue('EntryFeedID', array($this, 'EntryFeedID'));
        $this->addValue('EntryFirstImage', array($this, 'EntryFirstImage'));
        $this->addValue('EntryID', array($this, 'EntryID'));
        $this->addBlock('EntryIf', array($this, 'EntryIf'));
        $this->addBlock('EntryIfContentCut', array($this, 'EntryIfContentCut'));
        $this->addValue('EntryIfFirst', array($this, 'EntryIfFirst'));
        $this->addValue('EntryIfOdd', array($this, 'EntryIfOdd'));
        $this->addValue('EntryIfSelected', array($this, 'EntryIfSelected'));
        $this->addValue('EntryLang', array($this, 'EntryLang'));
        $this->addBlock('EntryNext', array($this, 'EntryNext'));
        $this->addValue('EntryPingCount', array($this, 'EntryPingCount'));
        $this->addValue('EntryPingData', array($this, 'EntryPingData'));
        $this->addValue('EntryPingLink', array($this, 'EntryPingLink'));
        $this->addBlock('EntryPrevious', array($this, 'EntryPrevious'));
        $this->addValue('EntryTitle', array($this, 'EntryTitle'));
        $this->addValue('EntryTime', array($this, 'EntryTime'));
        $this->addValue('EntryURL', array($this, 'EntryURL'));

        # Languages
        $this->addBlock('Languages', array($this, 'Languages'));
        $this->addBlock('LanguagesHeader', array($this, 'LanguagesHeader'));
        $this->addBlock('LanguagesFooter', array($this, 'LanguagesFooter'));
        $this->addValue('LanguageCode', array($this, 'LanguageCode'));
        $this->addBlock('LanguageIfCurrent', array($this, 'LanguageIfCurrent'));
        $this->addValue('LanguageURL', array($this, 'LanguageURL'));

        # Pagination
        $this->addBlock('Pagination', array($this, 'Pagination'));
        $this->addValue('PaginationCounter', array($this, 'PaginationCounter'));
        $this->addValue('PaginationCurrent', array($this, 'PaginationCurrent'));
        $this->addBlock('PaginationIf', array($this, 'PaginationIf'));
        $this->addValue('PaginationURL', array($this, 'PaginationURL'));

        # Trackbacks
        $this->addValue('PingBlogName', array($this, 'PingBlogName'));
        $this->addValue('PingContent', array($this, 'PingContent'));
        $this->addValue('PingDate', array($this, 'PingDate'));
        $this->addValue('PingEntryTitle', array($this, 'PingEntryTitle'));
        $this->addValue('PingFeedID', array($this, 'PingFeedID'));
        $this->addValue('PingID', array($this, 'PingID'));
        $this->addValue('PingIfFirst', array($this, 'PingIfFirst'));
        $this->addValue('PingIfOdd', array($this, 'PingIfOdd'));
        $this->addValue('PingIP', array($this, 'PingIP'));
        $this->addValue('PingNoFollow', array($this, 'PingNoFollow'));
        $this->addValue('PingOrderNumber', array($this, 'PingOrderNumber'));
        $this->addValue('PingPostURL', array($this, 'PingPostURL'));
        $this->addBlock('Pings', array($this, 'Pings'));
        $this->addBlock('PingsFooter', array($this, 'PingsFooter'));
        $this->addBlock('PingsHeader', array($this, 'PingsHeader'));
        $this->addValue('PingTime', array($this, 'PingTime'));
        $this->addValue('PingTitle', array($this, 'PingTitle'));
        $this->addValue('PingAuthorURL', array($this, 'PingAuthorURL'));

        # System
        $this->addValue('SysBehavior', array($this, 'SysBehavior'));
        $this->addBlock('SysIf', array($this, 'SysIf'));
        $this->addBlock('SysIfCommentPublished', array($this, 'SysIfCommentPublished'));
        $this->addBlock('SysIfCommentPending', array($this, 'SysIfCommentPending'));
        $this->addBlock('SysIfFormError', array($this, 'SysIfFormError'));
        $this->addValue('SysFeedSubtitle', array($this, 'SysFeedSubtitle'));
        $this->addValue('SysFormError', array($this, 'SysFormError'));
        $this->addValue('SysPoweredBy', array($this, 'SysPoweredBy'));
        $this->addValue('SysSearchString', array($this, 'SysSearchString'));
        $this->addValue('SysSelfURI', array($this, 'SysSelfURI'));

        # Generic
        $this->addValue('else', array($this, 'GenericElse'));
    }

    public function getData($________)
    {
        # --BEHAVIOR-- tplBeforeData
        if ($this->core->hasBehavior('tplBeforeData')) {
            self::$_r = $this->core->callBehavior('tplBeforeData', $this->core);
            if (self::$_r) {
                return self::$_r;
            }
        }

        parent::getData($________);

        # --BEHAVIOR-- tplAfterData
        if ($this->core->hasBehavior('tplAfterData')) {
            $this->core->callBehavior('tplAfterData', $this->core, self::$_r);
        }

        return self::$_r;
    }

    public function compileBlockNode($tag, $attr, $content)
    {
        $this->current_tag = $tag;
        $attr              = new ArrayObject($attr);
        # --BEHAVIOR-- templateBeforeBlock
        $res = $this->core->callBehavior('templateBeforeBlock', $this->core, $this->current_tag, $attr);

        # --BEHAVIOR-- templateInsideBlock
        $this->core->callBehavior('templateInsideBlock', $this->core, $this->current_tag, $attr, array(&$content));

        $res .= parent::compileBlockNode($this->current_tag, $attr, $content);

        # --BEHAVIOR-- templateAfterBlock
        $res .= $this->core->callBehavior('templateAfterBlock', $this->core, $this->current_tag, $attr);

        return $res;
    }

    public function compileValueNode($tag, $attr, $str_attr)
    {
        $this->current_tag = $tag;

        $attr = new ArrayObject($attr);
        # --BEHAVIOR-- templateBeforeValue
        $res = $this->core->callBehavior('templateBeforeValue', $this->core, $this->current_tag, $attr);

        $res .= parent::compileValueNode($this->current_tag, $attr, $str_attr);

        # --BEHAVIOR-- templateAfterValue
        $res .= $this->core->callBehavior('templateAfterValue', $this->core, $this->current_tag, $attr);

        return $res;
    }

    public function getFilters($attr, $default = array())
    {
        if (!is_array($attr) && !($attr instanceof arrayObject)) {
            $attr = array();
        }

        $p = array_merge(
            array(
                0             => null,
                'encode_xml'  => 0,
                'encode_html' => 0,
                'cut_string'  => 0,
                'lower_case'  => 0,
                'upper_case'  => 0,
                'encode_url'  => 0,
                'remove_html' => 0,
                'capitalize'  => 0,
                'strip_tags'  => 0
            ),
            $default
        );

        foreach ($attr as $k => $v) {
            // attributes names must follow this rule
            $k = preg_filter('/[a-zA-Z0-9_]/', '$0', $k);
            if ($k) {
                // addslashes protect var_export, str_replace protect sprintf;
                $p[$k] = str_replace('%', '%%', addslashes($v));
            }
        }

        return "context::global_filters(%s," . var_export($p, true) . ",'" . addslashes($this->current_tag) . "')";
    }

    public static function getOperator($op)
    {
        switch (strtolower($op)) {
            case 'or':
            case '||':
                return '||';
            case 'and':
            case '&&':
            default:
                return '&&';
        }
    }

    public function getSortByStr($attr, $table = null)
    {
        $res = array();

        $default_order = 'desc';

        $default_alias = array(
            'post'    => array(
                'title'     => 'post_title',
                'selected'  => 'post_selected',
                'author'    => 'user_id',
                'date'      => 'post_dt',
                'id'        => 'post_id',
                'comment'   => 'nb_comment',
                'trackback' => 'nb_trackback'
            ),
            'comment' => array(
                'author' => 'comment_author',
                'date'   => 'comment_dt',
                'id'     => 'comment_id'
            )
        );

        $alias = new ArrayObject();

        # --BEHAVIOR-- templateCustomSortByAlias
        $this->core->callBehavior('templateCustomSortByAlias', $alias);

        $alias = $alias->getArrayCopy();

        if (is_array($alias)) {
            foreach ($alias as $k => $v) {
                if (!is_array($v)) {
                    $alias[$k] = array();
                }
                if (!isset($default_alias[$k]) || !is_array($default_alias[$k])) {
                    $default_alias[$k] = array();
                }
                $default_alias[$k] = array_merge($default_alias[$k], $alias[$k]);
            }
        }

        if (!array_key_exists($table, $default_alias)) {
            return implode(', ', $res);
        }

        if (isset($attr['order']) && preg_match('/^(desc|asc)$/i', $attr['order'])) {
            $default_order = $attr['order'];
        }
        if (isset($attr['sortby'])) {
            $sorts = explode(',', $attr['sortby']);
            foreach ($sorts as $k => $sort) {
                $order = $default_order;
                if (preg_match('/([a-z]*)\s*\?(desc|asc)$/i', $sort, $matches)) {
                    $sort  = $matches[1];
                    $order = $matches[2];
                }
                if (array_key_exists($sort, $default_alias[$table])) {
                    array_push($res, $default_alias[$table][$sort] . ' ' . $order);
                }
            }
        }

        if (count($res) === 0) {
            array_push($res, $default_alias[$table]['date'] . ' ' . $default_order);
        }

        return implode(', ', $res);
    }

    public static function getAge($attr)
    {
        if (isset($attr['age']) && preg_match('/^(\-[0-9]+|last).*$/i', $attr['age'])) {
            if (($ts = strtotime($attr['age'])) !== false) {
                return dt::str('%Y-%m-%d %H:%m:%S', $ts);
            }
        }
        return '';
    }

    public function displayCounter($variable, $values, $attr, $count_only_by_default = false)
    {
        if (isset($attr['count_only'])) {
            $count_only = ($attr['count_only'] == 1);
        } else {
            $count_only = $count_only_by_default;
        }
        if ($count_only) {
            return "<?php echo " . $variable . "; ?>";
        } else {
            $v = $values;
            if (isset($attr['none'])) {
                $v['none'] = addslashes($attr['none']);
            }
            if (isset($attr['one'])) {
                $v['one'] = addslashes($attr['one']);
            }
            if (isset($attr['more'])) {
                $v['more'] = addslashes($attr['more']);
            }
            return
                "<?php if (" . $variable . " == 0) {\n" .
                "  printf(__('" . $v['none'] . "')," . $variable . ");\n" .
                "} elseif (" . $variable . " == 1) {\n" .
                "  printf(__('" . $v['one'] . "')," . $variable . ");\n" .
                "} else {\n" .
                "  printf(__('" . $v['more'] . "')," . $variable . ");\n" .
                "} ?>";
        }
    }
    /* TEMPLATE FUNCTIONS
    ------------------------------------------------------- */

    public function l10n($attr, $str_attr)
    {
        # Normalize content
        $str_attr = preg_replace('/\s+/x', ' ', $str_attr);

        return "<?php echo __('" . str_replace("'", "\\'", $str_attr) . "'); ?>";
    }

    public function LoopPosition($attr, $content)
    {
        $start  = isset($attr['start']) ? (integer) $attr['start'] : '0';
        $length = isset($attr['length']) ? (integer) $attr['length'] : 'null';
        $even   = isset($attr['even']) ? (integer) (boolean) $attr['even'] : 'null';
        $modulo = isset($attr['modulo']) ? (integer) $attr['modulo'] : 'null';

        if ($start > 0) {
            $start--;
        }

        return
            '<?php if ($_ctx->loopPosition(' . $start . ',' . $length . ',' . $even . ',' . $modulo . ')) : ?>' .
            $content .
            "<?php endif; ?>";
    }

    public function LoopIndex($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '(!$_ctx->cur_loop ? 0 : $_ctx->cur_loop->index() + 1)') . '; ?>';
    }

    /* Archives ------------------------------------------- */
    /*dtd
    <!ELEMENT tpl:Archives - - -- Archives dates loop -->
    <!ATTLIST tpl:Archives
    type        (day|month|year)    #IMPLIED    -- Get days, months or years, default to month --
    category    CDATA            #IMPLIED  -- Get dates of given category --
    no_context (1|0)            #IMPLIED  -- Override context information
    order    (asc|desc)        #IMPLIED  -- Sort asc or desc --
    post_type    CDATA            #IMPLIED  -- Get dates of given type of entries, default to post --
    post_lang    CDATA        #IMPLIED  -- Filter on the given language
    >
     */
    public function Archives($attr, $content)
    {
        $p = "if (!isset(\$params)) \$params = array();\n";
        $p .= "\$params['type'] = 'month';\n";
        if (isset($attr['type'])) {
            $p .= "\$params['type'] = '" . addslashes($attr['type']) . "';\n";
        }

        if (isset($attr['category'])) {
            $p .= "\$params['cat_url'] = '" . addslashes($attr['category']) . "';\n";
        }

        if (isset($attr['post_type'])) {
            $p .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (isset($attr['post_lang'])) {
            $p .= "\$params['post_lang'] = '" . addslashes($attr['post_lang']) . "';\n";
        }

        if (empty($attr['no_context']) && !isset($attr['category'])) {
            $p .=
                'if ($_ctx->exists("categories")) { ' .
                "\$params['cat_id'] = \$_ctx->categories->cat_id; " .
                "}\n";
        }

        $order = 'desc';
        if (isset($attr['order']) && preg_match('/^(desc|asc)$/i', $attr['order'])) {
            $p .= "\$params['order'] = '" . $attr['order'] . "';\n ";
        }

        $res = "<?php\n";
        $res .= $p;
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "Archives", "method" => "blog::getDates"),
            $attr, $content);
        $res .= '$_ctx->archives = $core->blog->getDates($params); unset($params);' . "\n";
        $res .= "?>\n";

        $res .=
            '<?php while ($_ctx->archives->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->archives = null; ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:ArchivesHeader - - -- First archives result container -->
     */
    public function ArchivesHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->archives->isStart()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:ArchivesFooter - - -- Last archives result container -->
     */
    public function ArchivesFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->archives->isEnd()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:ArchivesYearHeader - - -- First result of year in archives container -->
     */
    public function ArchivesYearHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->archives->yearHeader()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:ArchivesYearFooter - - -- Last result of year in archives container -->
     */
    public function ArchivesYearFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->archives->yearFooter()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:ArchiveDate - O -- Archive result date -->
    <!ATTLIST tpl:ArchiveDate
    format    CDATA    #IMPLIED  -- Date format (Default %B %Y) --
    >
     */
    public function ArchiveDate($attr)
    {
        $format = '%B %Y';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "dt::dt2str('" . $format . "',\$_ctx->archives->dt)") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:ArchiveEntriesCount - O -- Current archive result number of entries -->
     */
    public function ArchiveEntriesCount($attr)
    {
        $f = $this->getFilters($attr);
        return $this->displayCounter(
            sprintf($f, '$_ctx->archives->nb_post'),
            array(
                'none' => 'no archive',
                'one'  => 'one archive',
                'more' => '%d archives'
            ),
            $attr,
            true
        );
    }

    /*dtd
    <!ELEMENT tpl:ArchiveNext - - -- Next archive result container -->
    <!ATTLIST tpl:ArchiveNext
    type        (day|month|year)    #IMPLIED    -- Get days, months or years, default to month --
    post_type    CDATA            #IMPLIED  -- Get dates of given type of entries, default to post --
    post_lang    CDATA        #IMPLIED  -- Filter on the given language
    >
     */
    public function ArchiveNext($attr, $content)
    {
        $p = "if (!isset(\$params)) \$params = array();\n";
        $p .= "\$params['type'] = 'month';\n";
        if (isset($attr['type'])) {
            $p .= "\$params['type'] = '" . addslashes($attr['type']) . "';\n";
        }

        if (isset($attr['post_type'])) {
            $p .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (isset($attr['post_lang'])) {
            $p .= "\$params['post_lang'] = '" . addslashes($attr['post_lang']) . "';\n";
        }

        $p .= "\$params['next'] = \$_ctx->archives->dt;";

        $res = "<?php\n";
        $res .= $p;
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "ArchiveNext", "method" => "blog::getDates"),
            $attr, $content);
        $res .= '$_ctx->archives = $core->blog->getDates($params); unset($params);' . "\n";
        $res .= "?>\n";

        $res .=
            '<?php while ($_ctx->archives->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->archives = null; ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:ArchivePrevious - - -- Previous archive result container -->
    <!ATTLIST tpl:ArchivePrevious
    type        (day|month|year)    #IMPLIED    -- Get days, months or years, default to month --
    post_type    CDATA            #IMPLIED  -- Get dates of given type of entries, default to post --
    post_lang    CDATA        #IMPLIED  -- Filter on the given language
    >
     */
    public function ArchivePrevious($attr, $content)
    {
        $p = 'if (!isset($params)) $params = array();';
        $p .= "\$params['type'] = 'month';\n";
        if (isset($attr['type'])) {
            $p .= "\$params['type'] = '" . addslashes($attr['type']) . "';\n";
        }

        if (isset($attr['post_type'])) {
            $p .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (isset($attr['post_lang'])) {
            $p .= "\$params['post_lang'] = '" . addslashes($attr['post_lang']) . "';\n";
        }

        $p .= "\$params['previous'] = \$_ctx->archives->dt;";

        $res = "<?php\n";
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "ArchivePrevious", "method" => "blog::getDates"),
            $attr, $content);
        $res .= $p;
        $res .= '$_ctx->archives = $core->blog->getDates($params); unset($params);' . "\n";
        $res .= "?>\n";

        $res .=
            '<?php while ($_ctx->archives->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->archives = null; ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:ArchiveURL - O -- Current archive result URL -->
     */
    public function ArchiveURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->archives->url($core)') . '; ?>';
    }

    /* Blog ----------------------------------------------- */
    /*dtd
    <!ELEMENT tpl:BlogArchiveURL - O -- Blog Archives URL -->
     */
    public function BlogArchiveURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("archive")') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogCopyrightNotice - O -- Blog copyrght notices -->
     */
    public function BlogCopyrightNotice($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->settings->system->copyright_notice') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogDescription - O -- Blog Description -->
     */
    public function BlogDescription($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->desc') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogEditor - O -- Blog Editor -->
     */
    public function BlogEditor($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->settings->system->editor') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogFeedID - O -- Blog Feed ID -->
     */
    public function BlogFeedID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '"urn:md5:".$core->blog->uid') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogFeedURL - O -- Blog Feed URL -->
    <!ATTLIST tpl:BlogFeedURL
    type    (rss2|atom)    #IMPLIED    -- feed type (default : rss2)
    >
     */
    public function BlogFeedURL($attr)
    {
        $type = !empty($attr['type']) ? $attr['type'] : 'atom';

        if (!preg_match('#^(rss2|atom)$#', $type)) {
            $type = 'atom';
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("feed","' . $type . '")') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogName - O -- Blog Name -->
     */
    public function BlogName($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->name') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogLanguage - O -- Blog Language -->
     */
    public function BlogLanguage($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->settings->system->lang') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogThemeURL - O -- Blog's current Theme URL -->
     */
    public function BlogThemeURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->settings->system->themes_url."/".$core->blog->settings->system->theme') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogParentThemeURL - O -- Blog's current Theme's parent URL -->
     */
    public function BlogParentThemeURL($attr)
    {
        $f      = $this->getFilters($attr);
        $parent = '$core->themes->moduleInfo($core->blog->settings->system->theme,\'parent\')';
        return '<?php echo ' . sprintf($f, '$core->blog->settings->system->themes_url."/".(' . "$parent" . ' ? ' . "$parent" . ' : $core->blog->settings->system->theme)') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogPublicURL - O -- Blog Public directory URL -->
     */
    public function BlogPublicURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->settings->system->public_url') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogUpdateDate - O -- Blog last update date -->
    <!ATTLIST tpl:BlogUpdateDate
    format    CDATA    #IMPLIED    -- date format (encoded in dc:str by default if iso8601 or rfc822 not specified)
    iso8601    CDATA    #IMPLIED    -- if set, tells that date format is ISO 8601
    rfc822    CDATA    #IMPLIED    -- if set, tells that date format is RFC 822
    >
     */
    public function BlogUpdateDate($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        } else {
            $format = '%Y-%m-%d %H:%M:%S';
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);

        $f = $this->getFilters($attr);

        if ($rfc822) {
            return '<?php echo ' . sprintf($f, "dt::rfc822(\$core->blog->upddt,\$core->blog->settings->system->blog_timezone)") . '; ?>';
        } elseif ($iso8601) {
            return '<?php echo ' . sprintf($f, "dt::iso8601(\$core->blog->upddt,\$core->blog->settings->system->blog_timezone)") . '; ?>';
        } else {
            return '<?php echo ' . sprintf($f, "dt::str('" . $format . "',\$core->blog->upddt)") . '; ?>';
        }
    }

    /*dtd
    <!ELEMENT tpl:BlogID - 0 -- Blog ID -->
     */
    public function BlogID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->id') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogRSDURL - O -- Blog RSD URL -->
     */
    public function BlogRSDURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor(\'rsd\')') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogXMLRPCURL - O -- Blog XML-RPC URL -->
     */
    public function BlogXMLRPCURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor(\'xmlrpc\',$core->blog->id)') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogURL - O -- Blog URL -->
     */
    public function BlogURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogQmarkURL - O -- Blog URL, ending with a question mark -->
     */
    public function BlogQmarkURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->getQmarkURL()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:BlogMetaRobots - O -- Blog meta robots tag definition, overrides robots_policy setting -->
    <!ATTLIST tpl:BlogMetaRobots
    robots    CDATA    #IMPLIED    -- can be INDEX,FOLLOW,NOINDEX,NOFOLLOW,ARCHIVE,NOARCHIVE
    >
     */
    public function BlogMetaRobots($attr)
    {
        $robots = isset($attr['robots']) ? addslashes($attr['robots']) : '';
        return "<?php echo context::robotsPolicy(\$core->blog->settings->system->robots_policy,'" . $robots . "'); ?>";
    }

    /*dtd
    <!ELEMENT gpl:BlogJsJQuery - 0 -- Blog Js jQuery version selected -->
     */
    public function BlogJsJQuery($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->getJsJQuery()') . '; ?>';
    }

    /* Categories ----------------------------------------- */

    /*dtd
    <!ELEMENT tpl:Categories - - -- Categories loop -->
     */
    public function Categories($attr, $content)
    {
        $p = "if (!isset(\$params)) \$params = array();\n";

        if (isset($attr['url'])) {
            $p .= "\$params['cat_url'] = '" . addslashes($attr['url']) . "';\n";
        }

        if (!empty($attr['post_type'])) {
            $p .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (!empty($attr['level'])) {
            $p .= "\$params['level'] = " . (integer) $attr['level'] . ";\n";
        }

        if (isset($attr['with_empty']) && ((boolean) $attr['with_empty'] == true)) {
            $p .= '$params[\'without_empty\'] = false;';
        }

        $res = "<?php\n";
        $res .= $p;
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "Categories", "method" => "blog::getCategories"),
            $attr, $content);
        $res .= '$_ctx->categories = $core->blog->getCategories($params);' . "\n";
        $res .= "?>\n";
        $res .= '<?php while ($_ctx->categories->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->categories = null; unset($params); ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:CategoriesHeader - - -- First Categories result container -->
     */
    public function CategoriesHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->categories->isStart()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:CategoriesFooter - - -- Last Categories result container -->
     */
    public function CategoriesFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->categories->isEnd()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:CategoryIf - - -- tests on current entry -->
    <!ATTLIST tpl:CategoryIf
    url        CDATA    #IMPLIED    -- category has given url
    urls    CDATA    #IMPLIED    -- category has one of given urls
    has_entries    (0|1)    #IMPLIED    -- post is the first post from list (value : 1) or not (value : 0)
    has_description     (0|1)     #IMPLIED  -- category has description (value : 1) or not (value : 0)
    >
     */
    public function CategoryIf($attr, $content)
    {
        $if       = new ArrayObject();
        $operator = isset($attr['operator']) ? $this->getOperator($attr['operator']) : '&&';

        if (isset($attr['url'])) {
            $url = addslashes(trim($attr['url']));
            if (substr($url, 0, 1) == '!') {
                $url  = substr($url, 1);
                $if[] = '($_ctx->categories->cat_url != "' . $url . '")';
            } else {
                $if[] = '($_ctx->categories->cat_url == "' . $url . '")';
            }
        }

        if (isset($attr['urls'])) {
            $urls = explode(',', addslashes(trim($attr['urls'])));
            if (is_array($urls) && count($urls)) {
                foreach ($urls as $url) {
                    if (substr($url, 0, 1) == '!') {
                        $url  = substr($url, 1);
                        $if[] = '($_ctx->categories->cat_url != "' . $url . '")';
                    } else {
                        $if[] = '($_ctx->categories->cat_url == "' . $url . '")';
                    }
                }
            }
        }

        if (isset($attr['has_entries'])) {
            $sign = (boolean) $attr['has_entries'] ? '>' : '==';
            $if[] = '$_ctx->categories->nb_post ' . $sign . ' 0';
        }

        if (isset($attr['has_description'])) {
            $sign = (boolean) $attr['has_description'] ? '!=' : '==';
            $if[] = '$_ctx->categories->cat_desc ' . $sign . ' ""';
        }

        $this->core->callBehavior('tplIfConditions', 'CategoryIf', $attr, $content, $if);

        if (count($if) != 0) {
            return '<?php if(' . implode(' ' . $operator . ' ', (array) $if) . ') : ?>' . $content . '<?php endif; ?>';
        } else {
            return $content;
        }
    }

    /*dtd
    <!ELEMENT tpl:CategoryFirstChildren - - -- Current category first children loop -->
     */
    public function CategoryFirstChildren($attr, $content)
    {
        return
            "<?php\n" .
            '$_ctx->categories = $core->blog->getCategoryFirstChildren($_ctx->categories->cat_id);' . "\n" .
            'while ($_ctx->categories->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->categories = null; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CategoryParents - - -- Current category parents loop -->
     */
    public function CategoryParents($attr, $content)
    {
        return
            "<?php\n" .
            '$_ctx->categories = $core->blog->getCategoryParents($_ctx->categories->cat_id);' . "\n" .
            'while ($_ctx->categories->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->categories = null; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CategoryFeedURL - O -- Category feed URL -->
    <!ATTLIST tpl:CategoryFeedURL
    type    (rss2|atom)    #IMPLIED    -- feed type (default : rss2)
    >
     */
    public function CategoryFeedURL($attr)
    {
        $type = !empty($attr['type']) ? $attr['type'] : 'atom';

        if (!preg_match('#^(rss2|atom)$#', $type)) {
            $type = 'atom';
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("feed","category/".' .
            '$_ctx->categories->cat_url."/' . $type . '")') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CategoryURL - O -- Category URL (complete iabsolute URL, including blog URL) -->
     */
    public function CategoryURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("category",' .
            '$_ctx->categories->cat_url)') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CategoryShortURL - O -- Category short URL (relative URL, from /category/) -->
     */
    public function CategoryShortURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->categories->cat_url') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CategoryDescription - O -- Category description -->
     */
    public function CategoryDescription($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->categories->cat_desc') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CategoryTitle - O -- Category title -->
     */
    public function CategoryTitle($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->categories->cat_title') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CategoryEntriesCount - O -- Category number of entries -->
     */
    public function CategoryEntriesCount($attr)
    {
        $f = $this->getFilters($attr);
        return $this->displayCounter(
            sprintf($f, '$_ctx->categories->nb_post'),
            array(
                'none' => 'No post',
                'one'  => 'One post',
                'more' => '%d posts'
            ),
            $attr,
            true
        );
    }

    /* Entries -------------------------------------------- */
    /*dtd
    <!ELEMENT tpl:Entries - - -- Blog Entries loop -->
    <!ATTLIST tpl:Entries
    lastn    CDATA    #IMPLIED    -- limit number of results to specified value
    author    CDATA    #IMPLIED    -- get entries for a given user id
    category    CDATA    #IMPLIED    -- get entries for specific categories only (multiple comma-separated categories can be specified. Use "!" as prefix to exclude a category)
    no_category    CDATA    #IMPLIED    -- get entries without category
    with_category    CDATA    #IMPLIED    -- get entries with category
    no_context (1|0)    #IMPLIED  -- Override context information
    sortby    (title|selected|author|date|id)    #IMPLIED    -- specify entries sort criteria (default : date) (multiple comma-separated sortby can be specified. Use "?asc" or "?desc" as suffix to provide an order for each sorby)
    order    (desc|asc)    #IMPLIED    -- specify entries order (default : desc)
    no_content    (0|1)    #IMPLIED    -- do not retrieve entries content
    selected    (0|1)    #IMPLIED    -- retrieve posts marked as selected only (value: 1) or not selected only (value: 0)
    url        CDATA    #IMPLIED    -- retrieve post by its url
    type        CDATA    #IMPLIED    -- retrieve post with given post_type (there can be many ones separated by comma)
    age        CDATA    #IMPLIED    -- retrieve posts by maximum age (ex: -2 days, last month, last week)
    ignore_pagination    (0|1)    #IMPLIED    -- ignore page number provided in URL (useful when using multiple tpl:Entries on the same page)
    >
     */
    public function Entries($attr, $content)
    {
        $lastn = -1;
        if (isset($attr['lastn'])) {
            $lastn = abs((integer) $attr['lastn']) + 0;
        }

        $p = 'if (!isset($_page_number)) { $_page_number = 1; }' . "\n";

        if ($lastn != 0) {
            // Set limit (aka nb of entries needed)
            if ($lastn > 0) {
                // nb of entries per page specified in template -> regular pagination
                $p .= "\$params['limit'] = " . $lastn . ";\n";
                $p .= "\$nb_entry_first_page = \$nb_entry_per_page = " . $lastn . ";\n";
            } else {
                // nb of entries per page not specified -> use ctx settings
                $p .= "\$nb_entry_first_page=\$_ctx->nb_entry_first_page; \$nb_entry_per_page = \$_ctx->nb_entry_per_page;\n";
                $p .= "if ((\$core->url->type == 'default') || (\$core->url->type == 'default-page')) {\n";
                $p .= "    \$params['limit'] = (\$_page_number == 1 ? \$nb_entry_first_page : \$nb_entry_per_page);\n";
                $p .= "} else {\n";
                $p .= "    \$params['limit'] = \$nb_entry_per_page;\n";
                $p .= "}\n";
            }
            // Set offset (aka index of first entry)
            if (!isset($attr['ignore_pagination']) || $attr['ignore_pagination'] == "0") {
                // standard pagination, set offset
                $p .= "if ((\$core->url->type == 'default') || (\$core->url->type == 'default-page')) {\n";
                $p .= "    \$params['limit'] = array((\$_page_number == 1 ? 0 : (\$_page_number - 2) * \$nb_entry_per_page + \$nb_entry_first_page),\$params['limit']);\n";
                $p .= "} else {\n";
                $p .= "    \$params['limit'] = array((\$_page_number - 1) * \$nb_entry_per_page,\$params['limit']);\n";
                $p .= "}\n";
            } else {
                // no pagination, get all posts from 0 to limit
                $p .= "\$params['limit'] = array(0, \$params['limit']);\n";
            }
        }

        if (isset($attr['author'])) {
            $p .= "\$params['user_id'] = '" . addslashes($attr['author']) . "';\n";
        }

        if (isset($attr['category'])) {
            $p .= "\$params['cat_url'] = '" . addslashes($attr['category']) . "';\n";
            $p .= "context::categoryPostParam(\$params);\n";
        }

        if (isset($attr['with_category']) && $attr['with_category']) {
            $p .= "@\$params['sql'] .= ' AND P.cat_id IS NOT NULL ';\n";
        }

        if (isset($attr['no_category']) && $attr['no_category']) {
            $p .= "@\$params['sql'] .= ' AND P.cat_id IS NULL ';\n";
            $p .= "unset(\$params['cat_url']);\n";
        }

        if (!empty($attr['type'])) {
            $p .= "\$params['post_type'] = preg_split('/\s*,\s*/','" . addslashes($attr['type']) . "',-1,PREG_SPLIT_NO_EMPTY);\n";
        }

        if (!empty($attr['url'])) {
            $p .= "\$params['post_url'] = '" . addslashes($attr['url']) . "';\n";
        }

        if (empty($attr['no_context'])) {
            if (!isset($attr['author'])) {
                $p .=
                    'if ($_ctx->exists("users")) { ' .
                    "\$params['user_id'] = \$_ctx->users->user_id; " .
                    "}\n";
            }

            if (!isset($attr['category']) && (!isset($attr['no_category']) || !$attr['no_category'])) {
                $p .=
                    'if ($_ctx->exists("categories")) { ' .
                    "\$params['cat_id'] = \$_ctx->categories->cat_id.(\$core->blog->settings->system->inc_subcats?' ?sub':'');" .
                    "}\n";
            }

            $p .=
                'if ($_ctx->exists("archives")) { ' .
                "\$params['post_year'] = \$_ctx->archives->year(); " .
                "\$params['post_month'] = \$_ctx->archives->month(); ";
            if (!isset($attr['lastn'])) {
                $p .= "unset(\$params['limit']); ";
            }
            $p .=
                "}\n";

            $p .=
                'if ($_ctx->exists("langs")) { ' .
                "\$params['post_lang'] = \$_ctx->langs->post_lang; " .
                "}\n";

            $p .=
                'if (isset($_search)) { ' .
                "\$params['search'] = \$_search; " .
                "}\n";
        }

        $p .= "\$params['order'] = '" . $this->getSortByStr($attr, 'post') . "';\n";

        if (isset($attr['no_content']) && $attr['no_content']) {
            $p .= "\$params['no_content'] = true;\n";
        }

        if (isset($attr['selected'])) {
            $p .= "\$params['post_selected'] = " . (integer) (boolean) $attr['selected'] . ";";
        }

        if (isset($attr['age'])) {
            $age = $this->getAge($attr);
            $p .= !empty($age) ? "@\$params['sql'] .= ' AND P.post_dt > \'" . $age . "\'';\n" : '';
        }

        $res = "<?php\n";
        $res .= $p;
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "Entries", "method" => "blog::getPosts"),
            $attr, $content);
        $res .= '$_ctx->post_params = $params;' . "\n";
        $res .= '$_ctx->posts = $core->blog->getPosts($params); unset($params);' . "\n";
        $res .= "?>\n";
        $res .=
            '<?php while ($_ctx->posts->fetch()) : ?>' . $content . '<?php endwhile; ' .
            '$_ctx->posts = null; $_ctx->post_params = null; ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:DateHeader - O -- Displays date, if post is the first post of the given day -->
     */
    public function DateHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->posts->firstPostOfDay()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:DateFooter - O -- Displays date,  if post is the last post of the given day -->
     */
    public function DateFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->posts->lastPostOfDay()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:EntryIf - - -- tests on current entry -->
    <!ATTLIST tpl:EntryIf
    type    CDATA    #IMPLIED    -- post has a given type (default: "post")
    category    CDATA    #IMPLIED    -- post has a given category
    categories    CDATA    #IMPLIED    -- post has a one of given categories
    first    (0|1)    #IMPLIED    -- post is the first post from list (value : 1) or not (value : 0)
    odd    (0|1)    #IMPLIED    -- post is in an odd position (value : 1) or not (value : 0)
    even    (0|1)    #IMPLIED    -- post is in an even position (value : 1) or not (value : 0)
    extended    (0|1)    #IMPLIED    -- post has an excerpt (value : 1) or not (value : 0)
    selected    (0|1)    #IMPLIED    -- post is selected (value : 1) or not (value : 0)
    has_category    (0|1)    #IMPLIED    -- post has a category (value : 1) or not (value : 0)
    has_attachment    (0|1)    #IMPLIED    -- post has attachments (value : 1) or not (value : 0) (see Attachment plugin for code)
    comments_active    (0|1)    #IMPLIED    -- comments are active for this post (value : 1) or not (value : 0)
    pings_active    (0|1)    #IMPLIED    -- trackbacks are active for this post (value : 1) or not (value : 0)
    show_comments    (0|1)    #IMPLIED    -- there are comments for this post (value : 1) or not (value : 0)
    show_pings    (0|1)    #IMPLIED    -- there are trackbacks for this post (value : 1) or not (value : 0)
    republished    (0|1)    #IMPLIED    -- post has been updated since publication (value : 1) or not (value : 0)
    operator    (and|or)    #IMPLIED    -- combination of conditions, if more than 1 specifiec (default: and)
    url        CDATA    #IMPLIED    -- post has given url
    >
     */
    public function EntryIf($attr, $content)
    {
        $if          = new ArrayObject();
        $extended    = null;
        $hascategory = null;

        $operator = isset($attr['operator']) ? $this->getOperator($attr['operator']) : '&&';

        if (isset($attr['type'])) {
            $type = trim($attr['type']);
            $type = !empty($type) ? $type : 'post';
            $if[] = '$_ctx->posts->post_type == "' . addslashes($type) . '"';
        }

        if (isset($attr['url'])) {
            $url = trim($attr['url']);
            if (substr($url, 0, 1) == '!') {
                $url  = substr($url, 1);
                $if[] = '$_ctx->posts->post_url != "' . addslashes($url) . '"';
            } else {
                $if[] = '$_ctx->posts->post_url == "' . addslashes($url) . '"';
            }
        }

        if (isset($attr['category'])) {
            $category = addslashes(trim($attr['category']));
            if (substr($category, 0, 1) == '!') {
                $category = substr($category, 1);
                $if[]     = '($_ctx->posts->cat_url != "' . $category . '")';
            } else {
                $if[] = '($_ctx->posts->cat_url == "' . $category . '")';
            }
        }

        if (isset($attr['categories'])) {
            $categories = explode(',', addslashes(trim($attr['categories'])));
            if (is_array($categories) && count($categories)) {
                foreach ($categories as $category) {
                    if (substr($category, 0, 1) == '!') {
                        $category = substr($category, 1);
                        $if[]     = '($_ctx->posts->cat_url != "' . $category . '")';
                    } else {
                        $if[] = '($_ctx->posts->cat_url == "' . $category . '")';
                    }
                }
            }
        }

        if (isset($attr['first'])) {
            $sign = (boolean) $attr['first'] ? '=' : '!';
            $if[] = '$_ctx->posts->index() ' . $sign . '= 0';
        }

        if (isset($attr['odd'])) {
            $sign = (boolean) $attr['odd'] ? '=' : '!';
            $if[] = '($_ctx->posts->index()+1)%2 ' . $sign . '= 1';
        }

        if (isset($attr['extended'])) {
            $sign = (boolean) $attr['extended'] ? '' : '!';
            $if[] = $sign . '$_ctx->posts->isExtended()';
        }

        if (isset($attr['selected'])) {
            $sign = (boolean) $attr['selected'] ? '' : '!';
            $if[] = $sign . '(boolean)$_ctx->posts->post_selected';
        }

        if (isset($attr['has_category'])) {
            $sign = (boolean) $attr['has_category'] ? '' : '!';
            $if[] = $sign . '$_ctx->posts->cat_id';
        }

        if (isset($attr['comments_active'])) {
            $sign = (boolean) $attr['comments_active'] ? '' : '!';
            $if[] = $sign . '$_ctx->posts->commentsActive()';
        }

        if (isset($attr['pings_active'])) {
            $sign = (boolean) $attr['pings_active'] ? '' : '!';
            $if[] = $sign . '$_ctx->posts->trackbacksActive()';
        }

        if (isset($attr['has_comment'])) {
            $sign = (boolean) $attr['has_comment'] ? '' : '!';
            $if[] = $sign . '$_ctx->posts->hasComments()';
        }

        if (isset($attr['has_ping'])) {
            $sign = (boolean) $attr['has_ping'] ? '' : '!';
            $if[] = $sign . '$_ctx->posts->hasTrackbacks()';
        }

        if (isset($attr['show_comments'])) {
            if ((boolean) $attr['show_comments']) {
                $if[] = '($_ctx->posts->hasComments() || $_ctx->posts->commentsActive())';
            } else {
                $if[] = '(!$_ctx->posts->hasComments() && !$_ctx->posts->commentsActive())';
            }
        }

        if (isset($attr['show_pings'])) {
            if ((boolean) $attr['show_pings']) {
                $if[] = '($_ctx->posts->hasTrackbacks() || $_ctx->posts->trackbacksActive())';
            } else {
                $if[] = '(!$_ctx->posts->hasTrackbacks() && !$_ctx->posts->trackbacksActive())';
            }
        }

        if (isset($attr['republished'])) {
            $sign = (boolean) $attr['republished'] ? '' : '!';
            $if[] = $sign . '(boolean)$_ctx->posts->isRepublished()';
        }

        $this->core->callBehavior('tplIfConditions', 'EntryIf', $attr, $content, $if);

        if (count($if) != 0) {
            return '<?php if(' . implode(' ' . $operator . ' ', (array) $if) . ') : ?>' . $content . '<?php endif; ?>';
        } else {
            return $content;
        }
    }

    /*dtd
    <!ELEMENT tpl:EntryIfFirst - O -- displays value if entry is the first one -->
    <!ATTLIST tpl:EntryIfFirst
    return    CDATA    #IMPLIED    -- value to display in case of success (default: first)
    >
     */
    public function EntryIfFirst($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'first';
        $ret = html::escapeHTML($ret);

        return
        '<?php if ($_ctx->posts->index() == 0) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:EntryIfOdd - O -- displays value if entry is in an odd position -->
    <!ATTLIST tpl:EntryIfOdd
    return    CDATA    #IMPLIED    -- value to display in case of success (default: odd)
    >
     */
    public function EntryIfOdd($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'odd';
        $ret = html::escapeHTML($ret);

        return
        '<?php if (($_ctx->posts->index()+1)%2 == 1) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:EntryIfSelected - O -- displays value if entry is selected -->
    <!ATTLIST tpl:EntryIfSelected
    return    CDATA    #IMPLIED    -- value to display in case of success (default: selected)
    >
     */
    public function EntryIfSelected($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'selected';
        $ret = html::escapeHTML($ret);

        return
        '<?php if ($_ctx->posts->post_selected) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:EntryContent -  -- Entry content -->
    <!ATTLIST tpl:EntryContent
    absolute_urls    CDATA    #IMPLIED -- transforms local URLs to absolute one
    full            (1|0)    #IMPLIED -- returns full content with excerpt
    >
     */
    public function EntryContent($attr)
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        $f = $this->getFilters($attr);

        if (!empty($attr['full'])) {
            return '<?php echo ' . sprintf($f,
                '$_ctx->posts->getExcerpt(' . $urls . ')." ".$_ctx->posts->getContent(' . $urls . ')') . '; ?>';
        } else {
            return '<?php echo ' . sprintf($f, '$_ctx->posts->getContent(' . $urls . ')') . '; ?>';
        }
    }

    /*dtd
    <!ELEMENT tpl:EntryIfContentCut - - -- Test if Entry content has been cut -->
    <!ATTLIST tpl:EntryIfContentCut
    absolute_urls    CDATA    #IMPLIED -- transforms local URLs to absolute one
    full            (1|0)    #IMPLIED -- test with full content and excerpt
    >
     */
    public function EntryIfContentCut($attr, $content)
    {
        if (empty($attr['cut_string']) || !empty($attr['full'])) {
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

        return '<?php if (strlen(' . sprintf($full, '$_ctx->posts->getContent(' . $urls . ')') . ') > ' .
        'strlen(' . sprintf($short, '$_ctx->posts->getContent(' . $urls . ')') . ')) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryExcerpt - O -- Entry excerpt -->
    <!ATTLIST tpl:EntryExcerpt
    absolute_urls    CDATA    #IMPLIED -- transforms local URLs to absolute one
    >
     */
    public function EntryExcerpt($attr)
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->getExcerpt(' . $urls . ')') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAuthorCommonName - O -- Entry author common name -->
     */
    public function EntryAuthorCommonName($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->getAuthorCN()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAuthorDisplayName - O -- Entry author display name -->
     */
    public function EntryAuthorDisplayName($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->user_displayname') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAuthorID - O -- Entry author ID -->
     */
    public function EntryAuthorID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->user_id') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAuthorEmail - O -- Entry author email -->
    <!ATTLIST tpl:EntryAuthorEmail
    spam_protected    (0|1)    #IMPLIED    -- protect email from spam (default: 1)
    >
     */
    public function EntryAuthorEmail($attr)
    {
        $p = 'true';
        if (isset($attr['spam_protected']) && !$attr['spam_protected']) {
            $p = 'false';
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "\$_ctx->posts->getAuthorEmail(" . $p . ")") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAuthorEmailMD5 - O -- Entry author email MD5 sum -->
    >
     */
    public function EntryAuthorEmailMD5($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, 'md5($_ctx->posts->getAuthorEmail(false))') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAuthorLink - O -- Entry author link -->
     */
    public function EntryAuthorLink($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->getAuthorLink()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAuthorURL - O -- Entry author URL -->
     */
    public function EntryAuthorURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->user_url') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryBasename - O -- Entry short URL (relative to /post) -->
     */
    public function EntryBasename($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->post_url') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryCategory - O -- Entry category (full name) -->
     */
    public function EntryCategory($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->cat_title') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryCategoryDescription - O -- Entry category description -->
     */
    public function EntryCategoryDescription($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->cat_desc') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryCategoriesBreadcrumb - - -- Current entry parents loop (without last one) -->
     */
    public function EntryCategoriesBreadcrumb($attr, $content)
    {
        return
            "<?php\n" .
            '$_ctx->categories = $core->blog->getCategoryParents($_ctx->posts->cat_id);' . "\n" .
            'while ($_ctx->categories->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->categories = null; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryCategoryID - O -- Entry category ID -->
     */
    public function EntryCategoryID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->cat_id') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryCategoryURL - O -- Entry category URL -->
     */
    public function EntryCategoryURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->getCategoryURL()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryCategoryShortURL - O -- Entry category short URL (relative URL, from /category/) -->
     */
    public function EntryCategoryShortURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->cat_url') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryFeedID - O -- Entry feed ID -->
     */
    public function EntryFeedID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->getFeedID()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryFirstImage - O -- Extracts entry first image if exists -->
    <!ATTLIST tpl:EntryAuthorEmail
    size            (sq|t|s|m|o)    #IMPLIED    -- Image size to extract
    class        CDATA        #IMPLIED    -- Class to add on image tag
    with_category    (1|0)        #IMPLIED    -- Search in entry category description if present (default 0)
    no_tag    (1|0)    #IMPLIED    -- Return image URL without HTML tag (default 0)
    content_only    (1|0)        #IMPLIED    -- Search in content entry only, not in excerpt (default 0)
    cat_only    (1|0)        #IMPLIED    -- Search in category description only (default 0)
    >
     */
    public function EntryFirstImage($attr)
    {
        $size          = !empty($attr['size']) ? $attr['size'] : '';
        $class         = !empty($attr['class']) ? $attr['class'] : '';
        $with_category = !empty($attr['with_category']) ? 1 : 0;
        $no_tag        = !empty($attr['no_tag']) ? 1 : 0;
        $content_only  = !empty($attr['content_only']) ? 1 : 0;
        $cat_only      = !empty($attr['cat_only']) ? 1 : 0;

        return "<?php echo context::EntryFirstImageHelper('" . addslashes($size) . "'," . $with_category . ",'" . addslashes($class) . "'," .
            $no_tag . "," . $content_only . "," . $cat_only . "); ?>";
    }

    /*dtd
    <!ELEMENT tpl:EntryID - O -- Entry ID -->
     */
    public function EntryID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->post_id') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryLang - O --  Entry language or blog lang if not defined -->
     */
    public function EntryLang($attr)
    {
        $f = $this->getFilters($attr);
        return
        '<?php if ($_ctx->posts->post_lang) { ' .
        'echo ' . sprintf($f, '$_ctx->posts->post_lang') . '; ' .
        '} else {' .
        'echo ' . sprintf($f, '$core->blog->settings->system->lang') . '; ' .
            '} ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryNext - - -- Next entry block -->
    <!ATTLIST tpl:EntryNext
    restrict_to_category    (0|1)    #IMPLIED    -- find next post in the same category (default: 0)
    restrict_to_lang        (0|1)    #IMPLIED    -- find next post in the same language (default: 0)
    >
     */
    public function EntryNext($attr, $content)
    {
        $restrict_to_category = !empty($attr['restrict_to_category']) ? '1' : '0';
        $restrict_to_lang     = !empty($attr['restrict_to_lang']) ? '1' : '0';

        return
            '<?php $next_post = $core->blog->getNextPost($_ctx->posts,1,' . $restrict_to_category . ',' . $restrict_to_lang . '); ?>' . "\n" .
            '<?php if ($next_post !== null) : ?>' .

            '<?php $_ctx->posts = $next_post; unset($next_post);' . "\n" .
            'while ($_ctx->posts->fetch()) : ?>' .
            $content .
            '<?php endwhile; $_ctx->posts = null; ?>' .
            "<?php endif; ?>\n";
    }

    /*dtd
    <!ELEMENT tpl:EntryPrevious - - -- Previous entry block -->
    <!ATTLIST tpl:EntryPrevious
    restrict_to_category    (0|1)    #IMPLIED    -- find previous post in the same category (default: 0)
    restrict_to_lang        (0|1)    #IMPLIED    -- find next post in the same language (default: 0)
    >
     */
    public function EntryPrevious($attr, $content)
    {
        $restrict_to_category = !empty($attr['restrict_to_category']) ? '1' : '0';
        $restrict_to_lang     = !empty($attr['restrict_to_lang']) ? '1' : '0';

        return
            '<?php $prev_post = $core->blog->getNextPost($_ctx->posts,-1,' . $restrict_to_category . ',' . $restrict_to_lang . '); ?>' . "\n" .
            '<?php if ($prev_post !== null) : ?>' .

            '<?php $_ctx->posts = $prev_post; unset($prev_post);' . "\n" .
            'while ($_ctx->posts->fetch()) : ?>' .
            $content .
            '<?php endwhile; $_ctx->posts = null; ?>' .
            "<?php endif; ?>\n";
    }

    /*dtd
    <!ELEMENT tpl:EntryTitle - O -- Entry title -->
     */
    public function EntryTitle($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->post_title') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryURL - O -- Entry URL -->
     */
    public function EntryURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->posts->getURL()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryDate - O -- Entry date -->
    <!ATTLIST tpl:EntryDate
    format    CDATA    #IMPLIED    -- date format (encoded in dc:str by default if iso8601 or rfc822 not specified)
    iso8601    CDATA    #IMPLIED    -- if set, tells that date format is ISO 8601
    rfc822    CDATA    #IMPLIED    -- if set, tells that date format is RFC 822
    upddt    CDATA    #IMPLIED    -- if set, uses the post update time
    creadt    CDATA    #IMPLIED    -- if set, uses the post creation time
    >
     */
    public function EntryDate($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);
        $type    = (!empty($attr['creadt']) ? 'creadt' : '');
        $type    = (!empty($attr['upddt']) ? 'upddt' : $type);

        $f = $this->getFilters($attr);

        if ($rfc822) {
            return '<?php echo ' . sprintf($f, "\$_ctx->posts->getRFC822Date('" . $type . "')") . '; ?>';
        } elseif ($iso8601) {
            return '<?php echo ' . sprintf($f, "\$_ctx->posts->getISO8601Date('" . $type . "')") . '; ?>';
        } else {
            return '<?php echo ' . sprintf($f, "\$_ctx->posts->getDate('" . $format . "','" . $type . "')") . '; ?>';
        }
    }

    /*dtd
    <!ELEMENT tpl:EntryTime - O -- Entry date -->
    <!ATTLIST tpl:EntryTime
    format    CDATA    #IMPLIED    -- time format
    upddt    CDATA    #IMPLIED    -- if set, uses the post update time
    creadt    CDATA    #IMPLIED    -- if set, uses the post creation time
    >
     */
    public function EntryTime($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $type = (!empty($attr['creadt']) ? 'creadt' : '');
        $type = (!empty($attr['upddt']) ? 'upddt' : $type);

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "\$_ctx->posts->getTime('" . $format . "','" . $type . "')") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:EntriesHeader - - -- First entries result container -->
     */
    public function EntriesHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->posts->isStart()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:EntriesFooter - - -- Last entries result container -->
     */
    public function EntriesFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->posts->isEnd()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:EntryCommentCount - O -- Number of comments for entry -->
    <!ATTLIST tpl:EntryCommentCount
    none        CDATA    #IMPLIED    -- text to display for "no comments" (default: no comments)
    one        CDATA    #IMPLIED    -- text to display for "one comment" (default: one comment)
    more        CDATA    #IMPLIED    -- text to display for "more comments" (default: %s comments, %s is replaced by the number of comment)
    count_all    CDATA    #IMPLIED    -- count comments and trackbacks
    >
     */
    public function EntryCommentCount($attr)
    {
        if (empty($attr['count_all'])) {
            $operation = '$_ctx->posts->nb_comment';
        } else {
            $operation = '($_ctx->posts->nb_comment + $_ctx->posts->nb_trackback)';
        }

        return $this->displayCounter(
            $operation,
            array(
                'none' => 'no comments',
                'one'  => 'one comment',
                'more' => '%d comments'
            ),
            $attr,
            false
        );
    }

    /*dtd
    <!ELEMENT tpl:EntryPingCount - O -- Number of trackbacks for entry -->
    <!ATTLIST tpl:EntryPingCount
    none    CDATA    #IMPLIED    -- text to display for "no pings" (default: no pings)
    one    CDATA    #IMPLIED    -- text to display for "one ping" (default: one ping)
    more    CDATA    #IMPLIED    -- text to display for "more pings" (default: %s trackbacks, %s is replaced by the number of pings)
    >
     */
    public function EntryPingCount($attr)
    {
        return $this->displayCounter(
            '$_ctx->posts->nb_trackback',
            array(
                'none' => 'no trackbacks',
                'one'  => 'one trackback',
                'more' => '%d trackbacks'
            ),
            $attr,
            false
        );
    }

    /*dtd
    <!ELEMENT tpl:EntryPingData - O -- Display trackback RDF information -->
     */
    public function EntryPingData($attr)
    {
        $format = !empty($attr['format']) && $attr['format'] == 'xml' ? 'xml' : 'html';
        return "<?php if (\$_ctx->posts->trackbacksActive()) { echo \$_ctx->posts->getTrackbackData('" . $format . "'); } ?>\n";
    }

    /*dtd
    <!ELEMENT tpl:EntryPingLink - O -- Entry trackback link -->
     */
    public function EntryPingLink($attr)
    {
        return "<?php if (\$_ctx->posts->trackbacksActive()) { echo \$_ctx->posts->getTrackbackLink(); } ?>\n";
    }

    /* Languages -------------------------------------- */
    /*dtd
    <!ELEMENT tpl:Languages - - -- Languages loop -->
    <!ATTLIST tpl:Languages
    lang    CDATA    #IMPLIED    -- restrict loop on given lang
    order    (desc|asc)    #IMPLIED    -- languages ordering (default: desc)
    >
     */
    public function Languages($attr, $content)
    {
        $p = "if (!isset(\$params)) \$params = array();\n";

        if (isset($attr['lang'])) {
            $p = "\$params['lang'] = '" . addslashes($attr['lang']) . "';\n";
        }

        $order = 'desc';
        if (isset($attr['order']) && preg_match('/^(desc|asc)$/i', $attr['order'])) {
            $p .= "\$params['order'] = '" . $attr['order'] . "';\n ";
        }

        $res = "<?php\n";
        $res .= $p;
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "Languages", "method" => "blog::getLangs"),
            $attr, $content);
        $res .= '$_ctx->langs = $core->blog->getLangs($params); unset($params);' . "\n";
        $res .= "?>\n";

        $res .=
            '<?php if ($_ctx->langs->count() > 1) : ' .
            'while ($_ctx->langs->fetch()) : ?>' . $content .
            '<?php endwhile; $_ctx->langs = null; endif; ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:LanguagesHeader - - -- First languages result container -->
     */
    public function LanguagesHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->langs->isStart()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:LanguagesFooter - - -- Last languages result container -->
     */
    public function LanguagesFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->langs->isEnd()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:LanguageCode - O -- Language code -->
     */
    public function LanguageCode($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->langs->post_lang') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:LanguageIfCurrent - - -- tests if post language is current language -->
     */
    public function LanguageIfCurrent($attr, $content)
    {
        return
            "<?php if (\$_ctx->cur_lang == \$_ctx->langs->post_lang) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:LanguageURL - O -- Language URL -->
     */
    public function LanguageURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$core->blog->url.$core->url->getURLFor("lang",' .
            '$_ctx->langs->post_lang)') . '; ?>';
    }

    /* Pagination ------------------------------------- */
    /*dtd
    <!ELEMENT tpl:Pagination - - -- Pagination container -->
    <!ATTLIST tpl:Pagination
    no_context    (0|1)    #IMPLIED    -- override test on posts count vs number of posts per page
    >
     */
    public function Pagination($attr, $content)
    {
        $p = "<?php\n";
        $p .= '$params = $_ctx->post_params;' . "\n";
        $p .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "Pagination", "method" => "blog::getPosts"),
            $attr, $content);
        $p .= '$_ctx->pagination = $core->blog->getPosts($params,true); unset($params);' . "\n";
        $p .= "?>\n";

        if (isset($attr['no_context']) && $attr['no_context']) {
            return $p . $content;
        }

        return
            $p .
            '<?php if ($_ctx->pagination->f(0) > $_ctx->posts->count()) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PaginationCounter - O -- Number of pages -->
     */
    public function PaginationCounter($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "context::PaginationNbPages()") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PaginationCurrent - O -- current page -->
     */
    public function PaginationCurrent($attr)
    {
        $offset = 0;
        if (isset($attr['offset'])) {
            $offset = (integer) $attr['offset'];
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "context::PaginationPosition(" . $offset . ")") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PaginationIf - - -- pages tests -->
    <!ATTLIST tpl:PaginationIf
    start    (0|1)    #IMPLIED    -- test if we are at first page (value : 1) or not (value : 0)
    end    (0|1)    #IMPLIED    -- test if we are at last page (value : 1) or not (value : 0)
    >
     */
    public function PaginationIf($attr, $content)
    {
        $if = array();

        if (isset($attr['start'])) {
            $sign = (boolean) $attr['start'] ? '' : '!';
            $if[] = $sign . 'context::PaginationStart()';
        }

        if (isset($attr['end'])) {
            $sign = (boolean) $attr['end'] ? '' : '!';
            $if[] = $sign . 'context::PaginationEnd()';
        }

        $this->core->callBehavior('tplIfConditions', 'PaginationIf', $attr, $content, $if);

        if (count($if) != 0) {
            return '<?php if(' . implode(' && ', (array) $if) . ') : ?>' . $content . '<?php endif; ?>';
        } else {
            return $content;
        }
    }

    /*dtd
    <!ELEMENT tpl:PaginationURL - O -- link to previoux/next page -->
    <!ATTLIST tpl:PaginationURL
    offset    CDATA    #IMPLIED    -- page offset (negative for previous pages), default: 0
    >
     */
    public function PaginationURL($attr)
    {
        $offset = 0;
        if (isset($attr['offset'])) {
            $offset = (integer) $attr['offset'];
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "context::PaginationURL(" . $offset . ")") . '; ?>';
    }

    /* Comments --------------------------------------- */
    /*dtd
    <!ELEMENT tpl:Comments - - -- Comments container -->
    <!ATTLIST tpl:Comments
    with_pings    (0|1)    #IMPLIED    -- include trackbacks in request
    lastn    CDATA    #IMPLIED    -- restrict the number of entries
    no_context (1|0)        #IMPLIED  -- Override context information
    sortby    (title|selected|author|date|id)    #IMPLIED    -- specify comments sort criteria (default : date) (multiple comma-separated sortby can be specified. Use "?asc" or "?desc" as suffix to provide an order for each sorby)
    order    (desc|asc)    #IMPLIED    -- result ordering (default: asc)
    age        CDATA    #IMPLIED    -- retrieve comments by maximum age (ex: -2 days, last month, last week)
    >
     */
    public function Comments($attr, $content)
    {
        $p = "";
        if (empty($attr['with_pings'])) {
            $p .= "\$params['comment_trackback'] = false;\n";
        }

        $lastn = 0;
        if (isset($attr['lastn'])) {
            $lastn = abs((integer) $attr['lastn']) + 0;
        }

        if ($lastn > 0) {
            $p .= "\$params['limit'] = " . $lastn . ";\n";
        } else {
            $p .= "if (\$_ctx->nb_comment_per_page !== null) { \$params['limit'] = \$_ctx->nb_comment_per_page; }\n";
        }

        if (empty($attr['no_context'])) {
            $p .=
                "if (\$_ctx->posts !== null) { " .
                "\$params['post_id'] = \$_ctx->posts->post_id; " .
                "\$core->blog->withoutPassword(false);\n" .
                "}\n";
            $p .=
                'if ($_ctx->exists("categories")) { ' .
                "\$params['cat_id'] = \$_ctx->categories->cat_id; " .
                "}\n";

            $p .=
                'if ($_ctx->exists("langs")) { ' .
                "\$params['sql'] = \"AND P.post_lang = '\".\$core->blog->con->escape(\$_ctx->langs->post_lang).\"' \"; " .
                "}\n";
        }

        if (!isset($attr['order'])) {
            $attr['order'] = 'asc';
        }

        $p .= "\$params['order'] = '" . $this->getSortByStr($attr, 'comment') . "';\n";

        if (isset($attr['no_content']) && $attr['no_content']) {
            $p .= "\$params['no_content'] = true;\n";
        }

        if (isset($attr['age'])) {
            $age = $this->getAge($attr);
            $p .= !empty($age) ? "@\$params['sql'] .= ' AND P.post_dt > \'" . $age . "\'';\n" : '';
        }

        $res = "<?php\n";
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "Comments", "method" => "blog::getComments"),
            $attr, $content);
        $res .= $p;
        $res .= '$_ctx->comments = $core->blog->getComments($params); unset($params);' . "\n";
        $res .= "if (\$_ctx->posts !== null) { \$core->blog->withoutPassword(true);}\n";

        if (!empty($attr['with_pings'])) {
            $res .= '$_ctx->pings = $_ctx->comments;' . "\n";
        }

        $res .= "?>\n";

        $res .=
            '<?php while ($_ctx->comments->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->comments = null; ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:CommentAuthor - O -- Comment author -->
     */
    public function CommentAuthor($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "\$_ctx->comments->comment_author") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentAuthorDomain - O -- Comment author website domain -->
     */
    public function CommentAuthorDomain($attr)
    {
        return '<?php echo preg_replace("#^http(?:s?)://(.+?)/.*$#msu",\'$1\',$_ctx->comments->comment_site); ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentAuthorLink - O -- Comment author link -->
     */
    public function CommentAuthorLink($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comments->getAuthorLink()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentAuthorMailMD5 - O -- Comment author email MD5 sum -->
     */
    public function CommentAuthorMailMD5($attr)
    {
        return '<?php echo md5($_ctx->comments->comment_email) ; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentAuthorURL - O -- Comment author URL -->
     */
    public function CommentAuthorURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comments->getAuthorURL()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentContent - O --  Comment content -->
    <!ATTLIST tpl:CommentContent
    absolute_urls    (0|1)    #IMPLIED    -- convert URLS to absolute urls
    >
     */
    public function CommentContent($attr)
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comments->getContent(' . $urls . ')') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentDate - O -- Comment date -->
    <!ATTLIST tpl:CommentDate
    format    CDATA    #IMPLIED    -- date format (encoded in dc:str by default if iso8601 or rfc822 not specified)
    iso8601    CDATA    #IMPLIED    -- if set, tells that date format is ISO 8601
    rfc822    CDATA    #IMPLIED    -- if set, tells that date format is RFC 822
    upddt    CDATA    #IMPLIED    -- if set, uses the comment update time
    >
     */
    public function CommentDate($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);
        $type    = (!empty($attr['upddt']) ? 'upddt' : '');

        $f = $this->getFilters($attr);

        if ($rfc822) {
            return '<?php echo ' . sprintf($f, "\$_ctx->comments->getRFC822Date('" . $type . "')") . '; ?>';
        } elseif ($iso8601) {
            return '<?php echo ' . sprintf($f, "\$_ctx->comments->getISO8601Date('" . $type . "')") . '; ?>';
        } else {
            return '<?php echo ' . sprintf($f, "\$_ctx->comments->getDate('" . $format . "','" . $type . "')") . '; ?>';
        }
    }

    /*dtd
    <!ELEMENT tpl:CommentTime - O -- Comment date -->
    <!ATTLIST tpl:CommentTime
    format    CDATA    #IMPLIED    -- time format
    upddt    CDATA    #IMPLIED    -- if set, uses the comment update time
    >
     */
    public function CommentTime($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }
        $type = (!empty($attr['upddt']) ? 'upddt' : '');

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "\$_ctx->comments->getTime('" . $format . "','" . $type . "')") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentEmail - O -- Comment author email -->
    <!ATTLIST tpl:CommentEmail
    spam_protected    (0|1)    #IMPLIED    -- protect email from spam (default: 1)
    >
     */
    public function CommentEmail($attr)
    {
        $p = 'true';
        if (isset($attr['spam_protected']) && !$attr['spam_protected']) {
            $p = 'false';
        }

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "\$_ctx->comments->getEmail(" . $p . ")") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentEntryTitle - O -- Title of the comment entry -->
     */
    public function CommentEntryTitle($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comments->post_title') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentFeedID - O -- Comment feed ID -->
     */
    public function CommentFeedID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comments->getFeedID()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentID - O -- Comment ID -->
     */
    public function CommentID($attr)
    {
        return '<?php echo $_ctx->comments->comment_id; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentIf - - -- test container for comments -->
    <!ATTLIST tpl:CommentIf
    is_ping    (0|1)    #IMPLIED    -- test if comment is a trackback (value : 1) or not (value : 0)
    >
     */
    public function CommentIf($attr, $content)
    {
        $if      = array();
        $is_ping = null;

        if (isset($attr['is_ping'])) {
            $sign = (boolean) $attr['is_ping'] ? '' : '!';
            $if[] = $sign . '$_ctx->comments->comment_trackback';
        }

        $this->core->callBehavior('tplIfConditions', 'CommentIf', $attr, $content, $if);

        if (count($if) != 0) {
            return '<?php if(' . implode(' && ', (array) $if) . ') : ?>' . $content . '<?php endif; ?>';
        } else {
            return $content;
        }
    }

    /*dtd
    <!ELEMENT tpl:CommentIfFirst - O -- displays value if comment is the first one -->
    <!ATTLIST tpl:CommentIfFirst
    return    CDATA    #IMPLIED    -- value to display in case of success (default: first)
    >
     */
    public function CommentIfFirst($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'first';
        $ret = html::escapeHTML($ret);

        return
        '<?php if ($_ctx->comments->index() == 0) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:CommentIfMe - O -- displays value if comment is the from the entry author -->
    <!ATTLIST tpl:CommentIfMe
    return    CDATA    #IMPLIED    -- value to display in case of success (default: me)
    >
     */
    public function CommentIfMe($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'me';
        $ret = html::escapeHTML($ret);

        return
        '<?php if ($_ctx->comments->isMe()) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:CommentIfOdd - O -- displays value if comment is  at an odd position -->
    <!ATTLIST tpl:CommentIfOdd
    return    CDATA    #IMPLIED    -- value to display in case of success (default: odd)
    >
     */
    public function CommentIfOdd($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'odd';
        $ret = html::escapeHTML($ret);

        return
        '<?php if (($_ctx->comments->index()+1)%2) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:CommentIP - O -- Comment author IP -->
     */
    public function CommentIP($attr)
    {
        return '<?php echo $_ctx->comments->comment_ip; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentOrderNumber - O -- Comment order in page -->
     */
    public function CommentOrderNumber($attr)
    {
        return '<?php echo $_ctx->comments->index()+1; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentsFooter - - -- Last comments result container -->
     */
    public function CommentsFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->comments->isEnd()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:CommentsHeader - - -- First comments result container -->
     */
    public function CommentsHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->comments->isStart()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:CommentPostURL - O -- Comment Entry URL -->
     */
    public function CommentPostURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comments->getPostURL()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:IfCommentAuthorEmail - - -- Container displayed if comment author email is set -->
     */
    public function IfCommentAuthorEmail($attr, $content)
    {
        return
            "<?php if (\$_ctx->comments->comment_email) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:CommentHelp - 0 -- Comment syntax mini help -->
     */
    public function CommentHelp($attr, $content)
    {
        return
            "<?php if (\$core->blog->settings->system->wiki_comments) {\n" .
            "  echo __('Comments can be formatted using a simple wiki syntax.');\n" .
            "} else {\n" .
            "  echo __('HTML code is displayed as text and web addresses are automatically converted.');\n" .
            "} ?>";
    }

    /* Comment preview -------------------------------- */
    /*dtd
    <!ELEMENT tpl:IfCommentPreviewOptional - - -- Container displayed if comment preview is optional or currently previewed -->
     */
    public function IfCommentPreviewOptional($attr, $content)
    {
        return
            '<?php if ($core->blog->settings->system->comment_preview_optional || ($_ctx->comment_preview !== null && $_ctx->comment_preview["preview"])) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:IfCommentPreview - - -- Container displayed if comment is being previewed -->
     */
    public function IfCommentPreview($attr, $content)
    {
        return
            '<?php if ($_ctx->comment_preview !== null && $_ctx->comment_preview["preview"]) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentPreviewName - O -- Author name for the previewed comment -->
     */
    public function CommentPreviewName($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comment_preview["name"]') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentPreviewEmail - O -- Author email for the previewed comment -->
     */
    public function CommentPreviewEmail($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comment_preview["mail"]') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentPreviewSite - O -- Author site for the previewed comment -->
     */
    public function CommentPreviewSite($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->comment_preview["site"]') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentPreviewContent - O -- Content of the previewed comment -->
    <!ATTLIST tpl:CommentPreviewContent
    raw    (0|1)    #IMPLIED    -- display comment in raw content
    >
     */
    public function CommentPreviewContent($attr)
    {
        $f = $this->getFilters($attr);

        if (!empty($attr['raw'])) {
            $co = '$_ctx->comment_preview["rawcontent"]';
        } else {
            $co = '$_ctx->comment_preview["content"]';
        }

        return '<?php echo ' . sprintf($f, $co) . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:CommentPreviewCheckRemember - O -- checkbox attribute for "remember me" (same value as before preview) -->
     */
    public function CommentPreviewCheckRemember($attr)
    {
        return
            "<?php if (\$_ctx->comment_preview['remember']) { echo ' checked=\"checked\"'; } ?>";
    }

    /* Trackbacks ------------------------------------- */
    /*dtd
    <!ELEMENT tpl:PingBlogName - O -- Trackback blog name -->
     */
    public function PingBlogName($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->pings->comment_author') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingContent - O -- Trackback content -->
     */
    public function PingContent($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->pings->getTrackbackContent()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingDate - O -- Trackback date -->
    <!ATTLIST tpl:PingDate
    format    CDATA    #IMPLIED    -- date format (encoded in dc:str by default if iso8601 or rfc822 not specified)
    iso8601    CDATA    #IMPLIED    -- if set, tells that date format is ISO 8601
    rfc822    CDATA    #IMPLIED    -- if set, tells that date format is RFC 822
    upddt    CDATA    #IMPLIED    -- if set, uses the comment update time
    >
     */
    public function PingDate($attr, $type = '')
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);
        $type    = (!empty($attr['upddt']) ? 'upddt' : '');

        $f = $this->getFilters($attr);

        if ($rfc822) {
            return '<?php echo ' . sprintf($f, "\$_ctx->pings->getRFC822Date('" . $type . "')") . '; ?>';
        } elseif ($iso8601) {
            return '<?php echo ' . sprintf($f, "\$_ctx->pings->getISO8601Date('" . $type . "')") . '; ?>';
        } else {
            return '<?php echo ' . sprintf($f, "\$_ctx->pings->getDate('" . $format . "','" . $type . "')") . '; ?>';
        }
    }

    /*dtd
    <!ELEMENT tpl:PingTime - O -- Trackback date -->
    <!ATTLIST tpl:PingTime
    format    CDATA    #IMPLIED    -- time format
    upddt    CDATA    #IMPLIED    -- if set, uses the comment update time
    >
     */
    public function PingTime($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }
        $type = (!empty($attr['upddt']) ? 'upddt' : '');

        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, "\$_ctx->pings->getTime('" . $format . "','" . $type . "')") . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingEntryTitle - O -- Trackback entry title -->
     */
    public function PingEntryTitle($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->pings->post_title') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingFeedID - O -- Trackback feed ID -->
     */
    public function PingFeedID($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->pings->getFeedID()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingID - O -- Trackback ID -->
     */
    public function PingID($attr)
    {
        return '<?php echo $_ctx->pings->comment_id; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingIfFirst - O -- displays value if trackback is the first one -->
    <!ATTLIST tpl:PingIfFirst
    return    CDATA    #IMPLIED    -- value to display in case of success (default: first)
    >
     */
    public function PingIfFirst($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'first';
        $ret = html::escapeHTML($ret);

        return
        '<?php if ($_ctx->pings->index() == 0) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:PingIfOdd - O -- displays value if trackback is  at an odd position -->
    <!ATTLIST tpl:PingIfOdd
    return    CDATA    #IMPLIED    -- value to display in case of success (default: odd)
    >
     */
    public function PingIfOdd($attr)
    {
        $ret = isset($attr['return']) ? $attr['return'] : 'odd';
        $ret = html::escapeHTML($ret);

        return
        '<?php if (($_ctx->pings->index()+1)%2) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    /*dtd
    <!ELEMENT tpl:PingIP - O -- Trackback author IP -->
     */
    public function PingIP($attr)
    {
        return '<?php echo $_ctx->pings->comment_ip; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingNoFollow - O -- displays 'rel="nofollow"' if set in blog -->
     */
    public function PingNoFollow($attr)
    {
        return
            '<?php if($core->blog->settings->system->comments_nofollow) { ' .
            'echo \' rel="nofollow"\';' .
            '} ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingOrderNumber - O -- Trackback order in page -->
     */
    public function PingOrderNumber($attr)
    {
        return '<?php echo $_ctx->pings->index()+1; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingPostURL - O -- Trackback Entry URL -->
     */
    public function PingPostURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->pings->getPostURL()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:Pings - - -- Trackbacks container -->
    <!ATTLIST tpl:Pings
    with_pings    (0|1)    #IMPLIED    -- include trackbacks in request
    lastn    CDATA        #IMPLIED    -- restrict the number of entries
    no_context (1|0)        #IMPLIED  -- Override context information
    order    (desc|asc)    #IMPLIED    -- result ordering (default: asc)
    >
     */
    public function Pings($attr, $content)
    {
        $p =
            "if (\$_ctx->posts !== null) { " .
            "\$params['post_id'] = \$_ctx->posts->post_id; " .
            "\$core->blog->withoutPassword(false);\n" .
            "}\n";

        $p .= "\$params['comment_trackback'] = true;\n";

        $lastn = 0;
        if (isset($attr['lastn'])) {
            $lastn = abs((integer) $attr['lastn']) + 0;
        }

        if ($lastn > 0) {
            $p .= "\$params['limit'] = " . $lastn . ";\n";
        } else {
            $p .= "if (\$_ctx->nb_comment_per_page !== null) { \$params['limit'] = \$_ctx->nb_comment_per_page; }\n";
        }

        if (empty($attr['no_context'])) {
            $p .=
                'if ($_ctx->exists("categories")) { ' .
                "\$params['cat_id'] = \$_ctx->categories->cat_id; " .
                "}\n";

            $p .=
                'if ($_ctx->exists("langs")) { ' .
                "\$params['sql'] = \"AND P.post_lang = '\".\$core->blog->con->escape(\$_ctx->langs->post_lang).\"' \"; " .
                "}\n";
        }

        $order = 'asc';
        if (isset($attr['order']) && preg_match('/^(desc|asc)$/i', $attr['order'])) {
            $order = $attr['order'];
        }

        $p .= "\$params['order'] = 'comment_dt " . $order . "';\n";

        if (isset($attr['no_content']) && $attr['no_content']) {
            $p .= "\$params['no_content'] = true;\n";
        }

        $res = "<?php\n";
        $res .= $p;
        $res .= $this->core->callBehavior("templatePrepareParams",
            array("tag" => "Pings", "method" => "blog::getComments"),
            $attr, $content);
        $res .= '$_ctx->pings = $core->blog->getComments($params); unset($params);' . "\n";
        $res .= "if (\$_ctx->posts !== null) { \$core->blog->withoutPassword(true);}\n";
        $res .= "?>\n";

        $res .=
            '<?php while ($_ctx->pings->fetch()) : ?>' . $content . '<?php endwhile; $_ctx->pings = null; ?>';

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:PingsFooter - - -- Last trackbacks result container -->
     */
    public function PingsFooter($attr, $content)
    {
        return
            "<?php if (\$_ctx->pings->isEnd()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:PingsHeader - - -- First trackbacks result container -->
     */
    public function PingsHeader($attr, $content)
    {
        return
            "<?php if (\$_ctx->pings->isStart()) : ?>" .
            $content .
            "<?php endif; ?>";
    }

    /*dtd
    <!ELEMENT tpl:PingTitle - O -- Trackback title -->
     */
    public function PingTitle($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->pings->getTrackbackTitle()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:PingAuthorURL - O -- Trackback author URL -->
     */
    public function PingAuthorURL($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, '$_ctx->pings->getAuthorURL()') . '; ?>';
    }

    # System
    /*dtd
    <!ELEMENT tpl:SysBehavior - O -- Call a given behavior -->
    <!ATTLIST tpl:SysBehavior
    behavior    CDATA    #IMPLIED    -- behavior to call
    >
     */
    public function SysBehavior($attr, $raw)
    {
        if (!isset($attr['behavior'])) {
            return;
        }

        $b = addslashes($attr['behavior']);
        return
            '<?php if ($core->hasBehavior(\'' . $b . '\')) { ' .
            '$core->callBehavior(\'' . $b . '\',$core,$_ctx);' .
            '} ?>';
    }

    /*dtd
    <!ELEMENT tpl:SysIf - - -- System settings tester container -->
    <!ATTLIST tpl:SysIf
    categories        (0|1)    #IMPLIED    -- test if categories are set in current context (value : 1) or not (value : 0)
    posts            (0|1)    #IMPLIED    -- test if posts are set in current context (value : 1) or not (value : 0)
    blog_lang            CDATA    #IMPLIED    -- tests if blog language is the one given in parameter
    current_tpl        CDATA    #IMPLIED    -- tests if current template is the one given in paramater
    current_mode        CDATA    #IMPLIED    -- tests if current URL mode is the one given in parameter
    has_tpl            CDATA     #IMPLIED  -- tests if a named template exists
    has_tag            CDATA     #IMPLIED  -- tests if a named template block or value exists
    blog_id            CDATA     #IMPLIED  -- tests if current blog ID is the one given in parameter
    comments_active    (0|1)    #IMPLIED    -- test if comments are enabled blog-wide
    pings_active        (0|1)    #IMPLIED    -- test if trackbacks are enabled blog-wide
    wiki_comments        (0|1)    #IMPLIED    -- test if wiki syntax is enabled for comments
    operator            (and|or)    #IMPLIED    -- combination of conditions, if more than 1 specifiec (default: and)
    >
     */
    public function SysIf($attr, $content)
    {
        $if      = new ArrayObject();
        $is_ping = null;

        $operator = isset($attr['operator']) ? $this->getOperator($attr['operator']) : '&&';

        if (isset($attr['categories'])) {
            $sign = (boolean) $attr['categories'] ? '!' : '=';
            $if[] = '$_ctx->categories ' . $sign . '== null';
        }

        if (isset($attr['posts'])) {
            $sign = (boolean) $attr['posts'] ? '!' : '=';
            $if[] = '$_ctx->posts ' . $sign . '== null';
        }

        if (isset($attr['blog_lang'])) {
            $sign = '=';
            if (substr($attr['blog_lang'], 0, 1) == '!') {
                $sign              = '!';
                $attr['blog_lang'] = substr($attr['blog_lang'], 1);
            }
            $if[] = "\$core->blog->settings->system->lang " . $sign . "= '" . addslashes($attr['blog_lang']) . "'";
        }

        if (isset($attr['current_tpl'])) {
            $sign = '=';
            if (substr($attr['current_tpl'], 0, 1) == '!') {
                $sign                = '!';
                $attr['current_tpl'] = substr($attr['current_tpl'], 1);
            }
            $if[] = "\$_ctx->current_tpl " . $sign . "= '" . addslashes($attr['current_tpl']) . "'";
        }

        if (isset($attr['current_mode'])) {
            $sign = '=';
            if (substr($attr['current_mode'], 0, 1) == '!') {
                $sign                 = '!';
                $attr['current_mode'] = substr($attr['current_mode'], 1);
            }
            $if[] = "\$core->url->type " . $sign . "= '" . addslashes($attr['current_mode']) . "'";
        }

        if (isset($attr['has_tpl'])) {
            $sign = '';
            if (substr($attr['has_tpl'], 0, 1) == '!') {
                $sign            = '!';
                $attr['has_tpl'] = substr($attr['has_tpl'], 1);
            }
            $if[] = $sign . "\$core->tpl->getFilePath('" . addslashes($attr['has_tpl']) . "') !== false";
        }

        if (isset($attr['has_tag'])) {
            $sign = 'true';
            if (substr($attr['has_tag'], 0, 1) == '!') {
                $sign            = 'false';
                $attr['has_tag'] = substr($attr['has_tag'], 1);
            }
            $if[] = "\$core->tpl->tagExists('" . addslashes($attr['has_tag']) . "') === " . $sign;
        }

        if (isset($attr['blog_id'])) {
            $sign = '';
            if (substr($attr['blog_id'], 0, 1) == '!') {
                $sign            = '!';
                $attr['blog_id'] = substr($attr['blog_id'], 1);
            }
            $if[] = $sign . "(\$core->blog->id == '" . addslashes($attr['blog_id']) . "')";
        }

        if (isset($attr['comments_active'])) {
            $sign = (boolean) $attr['comments_active'] ? '' : '!';
            $if[] = $sign . '$core->blog->settings->system->allow_comments';
        }

        if (isset($attr['pings_active'])) {
            $sign = (boolean) $attr['pings_active'] ? '' : '!';
            $if[] = $sign . '$core->blog->settings->system->allow_trackbacks';
        }

        if (isset($attr['wiki_comments'])) {
            $sign = (boolean) $attr['wiki_comments'] ? '' : '!';
            $if[] = $sign . '$core->blog->settings->system->wiki_comments';
        }

        if (isset($attr['search_count']) &&
            preg_match('/^((=|!|&gt;|&lt;)=|(&gt;|&lt;))\s*[0-9]+$/', trim($attr['search_count']))) {
            $if[] = '(isset($_search_count) && $_search_count ' . html::decodeEntities($attr['search_count']) . ')';
        }

        $this->core->callBehavior('tplIfConditions', 'SysIf', $attr, $content, $if);

        if (count($if) != 0) {
            return '<?php if(' . implode(' ' . $operator . ' ', (array) $if) . ') : ?>' . $content . '<?php endif; ?>';
        } else {
            return $content;
        }
    }

    /*dtd
    <!ELEMENT tpl:SysIfCommentPublished - - -- Container displayed if comment has been published -->
     */
    public function SysIfCommentPublished($attr, $content)
    {
        return
            '<?php if (!empty($_GET[\'pub\'])) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:SysIfCommentPending - - -- Container displayed if comment is pending after submission -->
     */
    public function SysIfCommentPending($attr, $content)
    {
        return
            '<?php if (isset($_GET[\'pub\']) && $_GET[\'pub\'] == 0) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:SysFeedSubtitle - O -- Feed subtitle -->
     */
    public function SysFeedSubtitle($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php if ($_ctx->feed_subtitle !== null) { echo ' . sprintf($f, '$_ctx->feed_subtitle') . ';} ?>';
    }

    /*dtd
    <!ELEMENT tpl:SysIfFormError - O -- Container displayed if an error has been detected after form submission -->
     */
    public function SysIfFormError($attr, $content)
    {
        return
            '<?php if ($_ctx->form_error !== null) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:SysFormError - O -- Form error -->
     */
    public function SysFormError($attr)
    {
        return
            '<?php if ($_ctx->form_error !== null) { echo $_ctx->form_error; } ?>';
    }

    public function SysPoweredBy($attr)
    {
        return
            '<?php printf(__("Powered by %s"),"<a href=\"http://dotclear.org/\">Dotclear</a>"); ?>';
    }

    public function SysSearchString($attr)
    {
        $s = isset($attr['string']) ? $attr['string'] : '%1$s';

        $f = $this->getFilters($attr);
        return '<?php if (isset($_search)) { echo sprintf(__(\'' . $s . '\'),' . sprintf($f, '$_search') . ',$_search_count);} ?>';
    }

    public function SysSelfURI($attr)
    {
        $f = $this->getFilters($attr);
        return '<?php echo ' . sprintf($f, 'http::getSelfURI()') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:else - O -- else: statement -->
     */
    public function GenericElse($attr)
    {
        return '<?php else: ?>';
    }
}
