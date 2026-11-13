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
use Dotclear\Helper\Html\Template\Template;

class Tpl extends Template
{
    /**
     * Template methods traits
     */
    use TplArchivesTrait;
    use TplBlogTrait;
    use TplCategoriesTrait;
    use TplCommentsTrait;
    use TplEntriesTrait;
    use TplL10nTrait;
    use TplLanguagesTrait;
    use TplLoopTrait;
    use TplPaginationTrait;
    use TplPingsTrait;
    use TplSystemTrait;

    /**
     * Current tag
     */
    private ?string $current_tag = null;

    /**
     * Constructs a new instance.
     *
     * @param      string  $cache_dir  The cache dir
     * @param      string  $self_name  The self name (used in compiled template code)
     */
    public function __construct(string $cache_dir, string $self_name)
    {
        parent::__construct($cache_dir, $self_name, App::config()->debugMode());

        $this->remove_php = !App::blog()->settings()->get('system')->getBool('tpl_allow_php', false);
        $this->use_cache  = App::blog()->settings()->get('system')->getBool('tpl_use_cache', false);

        // Transitional tags
        $this->addValue('EntryTrackbackCount', $this->EntryPingCount(...));
        $this->addValue('EntryTrackbackData', $this->EntryPingData(...));
        $this->addValue('EntryTrackbackLink', $this->EntryPingLink(...));

        // l10n
        $this->addValue('lang', $this->l10n(...));

        // Loops test tags
        $this->addBlock('LoopPosition', $this->LoopPosition(...));
        $this->addValue('LoopIndex', $this->LoopIndex(...));
        $this->addValue('LoopCount', $this->LoopCount(...));

        // Archives
        $this->addBlock('Archives', $this->Archives(...));
        $this->addBlock('ArchivesHeader', $this->ArchivesHeader(...));
        $this->addBlock('ArchivesFooter', $this->ArchivesFooter(...));
        $this->addBlock('ArchivesYearHeader', $this->ArchivesYearHeader(...));
        $this->addBlock('ArchivesYearFooter', $this->ArchivesYearFooter(...));
        $this->addValue('ArchiveDate', $this->ArchiveDate(...));
        $this->addBlock('ArchiveNext', $this->ArchiveNext(...));
        $this->addBlock('ArchivePrevious', $this->ArchivePrevious(...));
        $this->addValue('ArchiveEntriesCount', $this->ArchiveEntriesCount(...));
        $this->addValue('ArchiveURL', $this->ArchiveURL(...));

        // Blog
        $this->addValue('BlogArchiveURL', $this->BlogArchiveURL(...));
        $this->addValue('BlogCopyrightNotice', $this->BlogCopyrightNotice(...));
        $this->addValue('BlogDescription', $this->BlogDescription(...));
        $this->addValue('BlogEditor', $this->BlogEditor(...));
        $this->addValue('BlogFeedID', $this->BlogFeedID(...));
        $this->addValue('BlogFeedURL', $this->BlogFeedURL(...));
        $this->addValue('BlogRSDURL', $this->BlogRSDURL(...));
        $this->addValue('BlogName', $this->BlogName(...));
        $this->addValue('BlogLanguage', $this->BlogLanguage(...));
        $this->addValue('BlogLanguageDirection', $this->BlogLanguageDirection(...));
        $this->addValue('BlogLanguageURL', $this->BlogLanguageURL(...));
        $this->addValue('BlogThemeURL', $this->BlogThemeURL(...));
        $this->addValue('BlogParentThemeURL', $this->BlogParentThemeURL(...));
        $this->addValue('BlogUpdateDate', $this->BlogUpdateDate(...));
        $this->addValue('BlogID', $this->BlogID(...));
        $this->addValue('BlogURL', $this->BlogURL(...));
        $this->addValue('BlogXMLRPCURL', $this->BlogXMLRPCURL(...));
        $this->addValue('BlogWebmentionURL', $this->BlogWebmentionURL(...));
        $this->addValue('BlogPublicURL', $this->BlogPublicURL(...));
        $this->addValue('BlogQmarkURL', $this->BlogQmarkURL(...));
        $this->addValue('BlogMetaRobots', $this->BlogMetaRobots(...));
        $this->addValue('BlogJsJQuery', $this->BlogJsJQuery(...));
        $this->addValue('BlogPostsURL', $this->BlogPostsURL(...));
        $this->addBlock('IfBlogStaticEntryURL', $this->IfBlogStaticEntryURL(...));
        $this->addValue('BlogStaticEntryURL', $this->BlogStaticEntryURL(...));
        $this->addValue('BlogNbEntriesFirstPage', $this->BlogNbEntriesFirstPage(...));
        $this->addValue('BlogNbEntriesPerPage', $this->BlogNbEntriesPerPage(...));

        // Categories
        $this->addBlock('Categories', $this->Categories(...));
        $this->addBlock('CategoriesHeader', $this->CategoriesHeader(...));
        $this->addBlock('CategoriesFooter', $this->CategoriesFooter(...));
        $this->addBlock('CategoryIf', $this->CategoryIf(...));
        $this->addBlock('CategoryFirstChildren', $this->CategoryFirstChildren(...));
        $this->addBlock('CategoryParents', $this->CategoryParents(...));
        $this->addValue('CategoryFeedURL', $this->CategoryFeedURL(...));
        $this->addValue('CategoryID', $this->CategoryID(...));
        $this->addValue('CategoryURL', $this->CategoryURL(...));
        $this->addValue('CategoryShortURL', $this->CategoryShortURL(...));
        $this->addValue('CategoryDescription', $this->CategoryDescription(...));
        $this->addValue('CategoryTitle', $this->CategoryTitle(...));
        $this->addValue('CategoryEntriesCount', $this->CategoryEntriesCount(...));

        // Comments
        $this->addBlock('Comments', $this->Comments(...));
        $this->addValue('CommentAuthor', $this->CommentAuthor(...));
        $this->addValue('CommentAuthorDomain', $this->CommentAuthorDomain(...));
        $this->addValue('CommentAuthorLink', $this->CommentAuthorLink(...));
        $this->addValue('CommentAuthorMailMD5', $this->CommentAuthorMailMD5(...));
        $this->addValue('CommentAuthorURL', $this->CommentAuthorURL(...));
        $this->addValue('CommentContent', $this->CommentContent(...));
        $this->addValue('CommentDate', $this->CommentDate(...));
        $this->addValue('CommentTime', $this->CommentTime(...));
        $this->addValue('CommentEmail', $this->CommentEmail(...));
        $this->addValue('CommentEntryTitle', $this->CommentEntryTitle(...));
        $this->addValue('CommentFeedID', $this->CommentFeedID(...));
        $this->addValue('CommentID', $this->CommentID(...));
        $this->addBlock('CommentIf', $this->CommentIf(...));
        $this->addValue('CommentIfEven', $this->CommentIfEven(...));
        $this->addValue('CommentIfFirst', $this->CommentIfFirst(...));
        $this->addValue('CommentIfMe', $this->CommentIfMe(...));
        $this->addValue('CommentIfUs', $this->CommentIfUs(...));
        $this->addValue('CommentIfOdd', $this->CommentIfOdd(...));
        $this->addValue('CommentIP', $this->CommentIP(...));
        $this->addValue('CommentOrderNumber', $this->CommentOrderNumber(...));
        $this->addBlock('CommentsFooter', $this->CommentsFooter(...));
        $this->addBlock('CommentsHeader', $this->CommentsHeader(...));
        $this->addValue('CommentPostURL', $this->CommentPostURL(...));
        $this->addBlock('IfCommentAuthorEmail', $this->IfCommentAuthorEmail(...));
        $this->addValue('CommentHelp', $this->CommentHelp(...));

        // Comment preview
        $this->addBlock('IfCommentPreview', $this->IfCommentPreview(...));
        $this->addBlock('IfCommentPreviewOptional', $this->IfCommentPreviewOptional(...));
        $this->addValue('CommentPreviewName', $this->CommentPreviewName(...));
        $this->addValue('CommentPreviewEmail', $this->CommentPreviewEmail(...));
        $this->addValue('CommentPreviewSite', $this->CommentPreviewSite(...));
        $this->addValue('CommentPreviewContent', $this->CommentPreviewContent(...));
        $this->addValue('CommentPreviewCheckRemember', $this->CommentPreviewCheckRemember(...));

        // Entries
        $this->addBlock('DateFooter', $this->DateFooter(...));
        $this->addBlock('DateHeader', $this->DateHeader(...));
        $this->addBlock('Entries', $this->Entries(...));
        $this->addBlock('EntriesFooter', $this->EntriesFooter(...));
        $this->addBlock('EntriesHeader', $this->EntriesHeader(...));
        $this->addValue('EntryAuthorCommonName', $this->EntryAuthorCommonName(...));
        $this->addValue('EntryAuthorDisplayName', $this->EntryAuthorDisplayName(...));
        $this->addValue('EntryAuthorEmail', $this->EntryAuthorEmail(...));
        $this->addValue('EntryAuthorEmailMD5', $this->EntryAuthorEmailMD5(...));
        $this->addValue('EntryAuthorID', $this->EntryAuthorID(...));
        $this->addValue('EntryAuthorLink', $this->EntryAuthorLink(...));
        $this->addValue('EntryAuthorURL', $this->EntryAuthorURL(...));
        $this->addValue('EntryBasename', $this->EntryBasename(...));
        $this->addValue('EntryCategory', $this->EntryCategory(...));
        $this->addValue('EntryCategoryDescription', $this->EntryCategoryDescription(...));
        $this->addBlock('EntryCategoriesBreadcrumb', $this->EntryCategoriesBreadcrumb(...));
        $this->addValue('EntryCategoryID', $this->EntryCategoryID(...));
        $this->addValue('EntryCategoryURL', $this->EntryCategoryURL(...));
        $this->addValue('EntryCategoryShortURL', $this->EntryCategoryShortURL(...));
        $this->addValue('EntryCommentCount', $this->EntryCommentCount(...));
        $this->addValue('EntryContent', $this->EntryContent(...));
        $this->addValue('EntryDate', $this->EntryDate(...));
        $this->addValue('EntryExcerpt', $this->EntryExcerpt(...));
        $this->addValue('EntryFeedID', $this->EntryFeedID(...));
        $this->addValue('EntryFirstImage', $this->EntryFirstImage(...));
        $this->addBlock('IfEntryFirstImage', $this->IfEntryFirstImage(...));
        $this->addValue('EntryID', $this->EntryID(...));
        $this->addBlock('EntryIf', $this->EntryIf(...));
        $this->addBlock('EntryIfContentCut', $this->EntryIfContentCut(...));
        $this->addValue('EntryIfEven', $this->EntryIfEven(...));
        $this->addValue('EntryIfFirst', $this->EntryIfFirst(...));
        $this->addValue('EntryIfOdd', $this->EntryIfOdd(...));
        $this->addValue('EntryIfSelected', $this->EntryIfSelected(...));
        $this->addValue('EntryLang', $this->EntryLang(...));
        $this->addBlock('EntryNext', $this->EntryNext(...));
        $this->addValue('EntryPingCount', $this->EntryPingCount(...));
        $this->addValue('EntryPingData', $this->EntryPingData(...));
        $this->addValue('EntryPingLink', $this->EntryPingLink(...));
        $this->addBlock('EntryPrevious', $this->EntryPrevious(...));
        $this->addValue('EntryTitle', $this->EntryTitle(...));
        $this->addValue('EntryTime', $this->EntryTime(...));
        $this->addValue('EntryURL', $this->EntryURL(...));

        // Languages
        $this->addBlock('Languages', $this->Languages(...));
        $this->addBlock('LanguagesHeader', $this->LanguagesHeader(...));
        $this->addBlock('LanguagesFooter', $this->LanguagesFooter(...));
        $this->addValue('LanguageCode', $this->LanguageCode(...));
        $this->addBlock('LanguageIfCurrent', $this->LanguageIfCurrent(...));
        $this->addValue('LanguageURL', $this->LanguageURL(...));
        $this->addValue('FeedLanguage', $this->FeedLanguage(...));

        // Pagination
        $this->addBlock('Pagination', $this->Pagination(...));
        $this->addValue('PaginationCounter', $this->PaginationCounter(...));
        $this->addValue('PaginationCurrent', $this->PaginationCurrent(...));
        $this->addBlock('PaginationIf', $this->PaginationIf(...));
        $this->addValue('PaginationURL', $this->PaginationURL(...));

        // Trackbacks
        $this->addValue('PingBlogName', $this->PingBlogName(...));
        $this->addValue('PingContent', $this->PingContent(...));
        $this->addValue('PingDate', $this->PingDate(...));
        $this->addValue('PingEntryTitle', $this->PingEntryTitle(...));
        $this->addValue('PingFeedID', $this->PingFeedID(...));
        $this->addValue('PingID', $this->PingID(...));
        $this->addValue('PingIfEven', $this->PingIfEven(...));
        $this->addValue('PingIfFirst', $this->PingIfFirst(...));
        $this->addValue('PingIfOdd', $this->PingIfOdd(...));
        $this->addValue('PingIP', $this->PingIP(...));
        $this->addValue('PingNoFollow', $this->PingNoFollow(...));
        $this->addValue('PingOrderNumber', $this->PingOrderNumber(...));
        $this->addValue('PingPostURL', $this->PingPostURL(...));
        $this->addBlock('Pings', $this->Pings(...));
        $this->addBlock('PingsFooter', $this->PingsFooter(...));
        $this->addBlock('PingsHeader', $this->PingsHeader(...));
        $this->addValue('PingTime', $this->PingTime(...));
        $this->addValue('PingTitle', $this->PingTitle(...));
        $this->addValue('PingAuthorURL', $this->PingAuthorURL(...));

        // System
        $this->addValue('SysBehavior', $this->SysBehavior(...));
        $this->addBlock('SysIf', $this->SysIf(...));
        $this->addBlock('SysIfCommentPublished', $this->SysIfCommentPublished(...));
        $this->addBlock('SysIfCommentPending', $this->SysIfCommentPending(...));
        $this->addBlock('SysIfFormError', $this->SysIfFormError(...));
        $this->addValue('SysFeedSubtitle', $this->SysFeedSubtitle(...));
        $this->addValue('SysFormError', $this->SysFormError(...));
        $this->addValue('SysPoweredBy', $this->SysPoweredBy(...));
        $this->addValue('SysSearchString', $this->SysSearchString(...));
        $this->addValue('SysSelfURI', $this->SysSelfURI(...));

        // Generic
        $this->addValue('else', $this->GenericElse(...));
    }

    /**
     * Add on the fly custom header to all files.
     *
     * Add namespace to use short class calls
     *
     * @return     string  The custom content
     */
    protected function addFileHeader(): string
    {
        return "<?php\nuse Dotclear\App;\n?>\n";
    }

    /**
     * Gets the template file content.
     *
     * @param      string  $________    The template filename
     * @param      bool    $globals     Declare non superglobals in $GLOBALS as global PHP variables?
     *
     * Note that if App::config()->modern() is true, non superglobals in $GLOBALS will not be declared as global PHP variables
     *
     * @return     string  The data.
     */
    public function getData(string $________, bool $globals = true): string
    {
        # --BEHAVIOR-- tplBeforeData --
        if (App::behavior()->hasBehavior('tplBeforeData') || App::behavior()->hasBehavior('tplBeforeDataV2')) {
            self::$_r = App::behavior()->callBehavior('tplBeforeDataV2');
            if (self::$_r !== '') {
                return self::$_r;
            }
        }

        parent::getData($________, App::config()->modern() ? false : $globals);

        # --BEHAVIOR-- tplAfterData -- string
        if (App::behavior()->hasBehavior('tplAfterData') || App::behavior()->hasBehavior('tplAfterDataV2')) {
            App::behavior()->callBehavior('tplAfterDataV2', self::$_r);
        }

        return (string) self::$_r;
    }

    /**
     * Compile block node
     *
     * @param      string                                           $tag      The tag
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $attr     The attributes
     * @param      string                                           $content  The content
     */
    public function compileBlockNode(string $tag, $attr, string $content): string
    {
        $this->current_tag = $tag;
        $attr              = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

        # --BEHAVIOR-- templateBeforeBlock -- string, ArrayObject
        $res = App::behavior()->callBehavior('templateBeforeBlockV2', $this->current_tag, $attr);

        # --BEHAVIOR-- templateInsideBlock -- string, ArrayObject, array<int,string>
        App::behavior()->callBehavior('templateInsideBlockV2', $this->current_tag, $attr, [&$content]);

        $res .= parent::compileBlockNode($this->current_tag, $attr, $content);

        # --BEHAVIOR-- templateAfterBlock -- string, ArrayObject
        $res .= App::behavior()->callBehavior('templateAfterBlockV2', $this->current_tag, $attr);

        return $res;
    }

    /**
     * Compile value node
     *
     * @param      string                                           $tag       The tag
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $attr      The attributes
     * @param      string                                           $str_attr  The attributes (one string form)
     */
    public function compileValueNode(string $tag, $attr, string $str_attr): string
    {
        $this->current_tag = $tag;
        $attr              = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

        # --BEHAVIOR-- templateBeforeValue -- string, ArrayObject
        $res = App::behavior()->callBehavior('templateBeforeValueV2', $this->current_tag, $attr);

        $res .= parent::compileValueNode($this->current_tag, $attr, $str_attr);

        # --BEHAVIOR-- templateAfterValue -- string, ArrayObject
        $res .= App::behavior()->callBehavior('templateAfterValueV2', $this->current_tag, $attr);

        return $res;
    }

    /**
     * Gets the current tag.
     *
     * @since 2.34
     */
    public function getCurrentTag(): string
    {
        return (string) $this->current_tag;
    }

    /**
     * Get the filters parameters to use in PHP code.
     *
     * @since 2.34
     *
     * @param      array<string, mixed>|ArrayObject<string, mixed>   $attr     The attributes
     * @param      array<string, mixed>|ArrayObject<string, mixed>   $default  The default filters
     *
     * @return     array<int|string, mixed>  The filters' parameters.
     */
    public function getFiltersParams($attr, $default = []): array
    {
        $def    = $default instanceof ArrayObject ? $default->getArrayCopy() : $default;
        $params = array_merge(
            [
                0             => null,  // Will receive the string to filter
                'encode_xml'  => 0,
                'encode_html' => 0,
                'cut_string'  => 0,
                'lower_case'  => 0,
                'upper_case'  => 0,
                'encode_url'  => 0,
                'remove_html' => 0,
                'capitalize'  => 0,
                'strip_tags'  => 0,
            ],
            $def
        );

        foreach ($attr as $filter => $value) {
            // attributes names must follow this rule
            $filter = preg_filter('/\w/', '$0', $filter);

            if ((bool) $filter && is_string($value)) {
                // addslashes protect var_export, str_replace protect sprintf;
                $params[$filter] = str_replace('%', '%%', addslashes($value));
            }
        }

        return $params;
    }

    /**
     * Return the PHP code to filter a given value.
     *
     * @param      array<string, mixed>|ArrayObject<string, mixed>   $attr     The attributes
     * @param      array<string, mixed>|ArrayObject<string, mixed>   $default  The default filters
     *
     * @return     string  The filters.
     */
    public function getFilters($attr, $default = []): string
    {
        $params = $this->getFiltersParams($attr, $default);

        return Ctx::class . '::global_filters(%s,' . var_export($params, true) . ",'" . addslashes($this->getCurrentTag()) . "')";
    }

    /**
     * Gets the operator.
     *
     * - "or" (in any case) and "||"" are aliases
     * - "and" (in any case) and "&&"" are aliases
     *
     * @param      string  $op     The operation
     */
    public static function getOperator(string $op): string
    {
        return match (strtolower($op)) {
            'or', '||'  => '||',
            'and', '&&' => '&&',
            default     => '&&',
        };
    }

    /**
     * Gets the sort by field depending on given table.
     *
     * @param      ArrayObject<string, mixed>   $attr      The attributes
     * @param      string                       $table     The table
     */
    public function getSortByStr(ArrayObject $attr, string $table = ''): string
    {
        if ($table === '') {
            return '';
        }

        $res = [];

        $default_order = 'desc';

        /**
         * @var array<string, array<string, string>> $default_alias
         */
        $default_alias = [
            'post' => [
                'title'     => 'post_title',
                'selected'  => 'post_selected',
                'author'    => 'user_id',
                'date'      => 'post_dt',
                'id'        => 'post_id',
                'comment'   => 'nb_comment',
                'trackback' => 'nb_trackback',
            ],
            'comment' => [
                'author' => 'comment_author',
                'date'   => 'comment_dt',
                'id'     => 'comment_id',
            ],
        ];

        /**
         * @var ArrayObject<string, array<string, string>> $alias
         */
        $alias = new ArrayObject();

        # --BEHAVIOR-- templateCustomSortByAlias -- ArrayObject
        App::behavior()->callBehavior('templateCustomSortByAlias', $alias);

        $alias = $alias->getArrayCopy();

        foreach ($alias as $k => $v) {  // @phpstan-ignore foreach.emptyArray
            if (!is_array($v)) {            // @phpstan-ignore function.alreadyNarrowedType
                $alias[$k] = [];
            }

            $default_alias[$k] ??= [];  // @phpstan-ignore nullCoalesce.offset
            $default_alias[$k] = array_merge($default_alias[$k], $alias[$k]);
        }

        if (!array_key_exists($table, $default_alias)) {
            return implode(', ', $res);
        }

        if (isset($attr['order'])
            && is_string($attr['order'])
            && (bool) preg_match('/^(desc|asc)$/i', $attr['order'])
        ) {
            $default_order = $attr['order'];
        }

        if (isset($attr['sortby'])
            && is_string($attr['sortby'])
        ) {
            $sorts = explode(',', $attr['sortby']);
            foreach ($sorts as $sort) {
                $order = $default_order;
                if ((bool) preg_match('/([a-z]*)\s*\?(desc|asc)$/i', $sort, $matches)) {
                    $sort  = $matches[1];
                    $order = $matches[2];
                }

                if (array_key_exists($sort, $default_alias[$table])) {
                    $res[] = $default_alias[$table][$sort] . ' ' . $order;
                }
            }
        }

        if ($res === []) {
            $res[] = $default_alias[$table]['date'] . ' ' . $default_order;
        }

        return implode(', ', $res);
    }

    /**
     * Gets the maximum date corresponding to a given age.
     *
     * @param      ArrayObject<string, mixed>  $attr   The attributes
     *
     * @return     string  The age.
     */
    public static function getAge(ArrayObject $attr): string
    {
        if (isset($attr['age'])
            && is_string($attr['age'])
            && (bool) preg_match('/^(\-\d+|last).*$/i', $attr['age'])
            && ($ts = strtotime($attr['age'])) !== false
        ) {
            return Date::str('%Y-%m-%d %H:%m:%S', $ts);
        }

        return '';
    }

    /**
     * Return PHP code to display a counter
     *
     * @param      string                       $variable               The variable
     * @param      array<string, mixed>         $values                 The values
     * @param      ArrayObject<string, mixed>   $attr                   The attributes
     * @param      bool                         $count_only_by_default  Display only counter value by default
     */
    public function displayCounter(string $variable, array $values, ArrayObject $attr, bool $count_only_by_default = false): string
    {
        if (isset($attr['count_only']) ? (bool) $attr['count_only'] : $count_only_by_default) {
            return '<?= ' . $variable . ' ?>';
        }

        /**
         * @var array<string, string>
         */
        $patterns = $values;
        array_walk($patterns, function (&$v, $k) use ($attr): void {
            if (isset($attr[$k]) && is_string($attr[$k])) {
                $v = addslashes($attr[$k]);
            }
        });

        return
        '<?php if (' . $variable . " == 0) {\n" .
        "  printf(__('" . $patterns['none'] . "')," . $variable . ");\n" .
        '} elseif (' . $variable . " == 1) {\n" .
        "  printf(__('" . $patterns['one'] . "')," . $variable . ");\n" .
        "} else {\n" .
        "  printf(__('" . $patterns['more'] . "')," . $variable . ");\n" .
        '} ?>';
    }
}
