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

trait TplCommentsTrait
{
    /**
     * tpl:Comments [attributes] : Comments container (tpl block)
     *
     * attributes:
     *
     *      - with_pings  (0|1)       Include trackbacks
     *      - lastn       int         Restrict the number of comments
     *      - no_context  (0|1)       Override context information
     *      - sortby      (title|selected|author|date|id)    Specify comments sort criteria (default: date), see note 1
     *      - order       (desc|asc)  Result ordering (default: asc)
     *      - age         string      Retrieve comments by maximum age (ex: -2 days, last month, last week)
     *      - no_content  (0|1)       Do not include comments' content
     *
     * Notes:
     *
     *  1) Multiple comma-separated sortby can be specified. Use "?asc" or "?desc" as suffix to provide an order for each sorby
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function Comments(ArrayObject $attr, string $content): string
    {
        $params = "if (!isset(\$params) || !isset(\$params['sql'])) { \$params['sql'] = ''; }\n";

        if (empty($attr['with_pings'])) {
            $params .= "\$params['comment_trackback'] = false;\n";
        }

        $lastn = 0;
        if (isset($attr['lastn']) && is_numeric($attr['lastn'])) {
            $lastn = abs((int) $attr['lastn']);
        }

        if ($lastn > 0) {
            $params .= "\$params['limit'] = " . $lastn . ";\n";
        } else {
            $params .= "if (App::frontend()->context()->nb_comment_per_page !== null) { \$params['limit'] = App::frontend()->context()->nb_comment_per_page; }\n";
        }

        if (empty($attr['no_context'])) {
            $params .= 'if (App::frontend()->context()->posts !== null) { ' .
                "\$params['post_id'] = App::frontend()->context()->posts->post_id; " .
                "App::blog()->withoutPassword(false);\n" .
                "}\n";

            $params .= 'if (App::frontend()->context()->exists("categories")) { ' .
                "\$params['cat_id'] = App::frontend()->context()->categories->cat_id.(App::blog()->settings()->get('system')->getBool('inc_subcats', false) ? ' ?sub' : '');" .
                "}\n";

            $params .= 'if (App::frontend()->context()->exists("langs")) { ' .
                "\$params['sql'] = \"AND P.post_lang = '\".App::blog()->con->escapeStr(App::frontend()->context()->langs->post_lang).\"' \"; " .
                "}\n";
        }

        $attr['order'] ??= 'asc';

        $params .= "\$params['order'] = '" . $this->getSortByStr($attr, 'comment') . "';\n";

        if (isset($attr['no_content']) && (bool) $attr['no_content']) {
            $params .= "\$params['no_content'] = true;\n";
        }

        if (isset($attr['age'])) {
            $age = static::getAge($attr);
            $params .= $age === '' ? '' : "\$params['sql'] .= ' AND P.post_dt > \'" . $age . "\'';\n";
        }

        $res = "<?php\n";
        # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
        $res .= App::behavior()->callBehavior(
            'templatePrepareParams',
            ['tag' => 'Comments', 'method' => 'blog::getComments'],
            $attr,
            $content
        );
        $res .= $params;
        $res .= 'App::frontend()->context()->comments = App::blog()->getComments($params); unset($params);' . "\n";
        $res .= "if (App::frontend()->context()->posts !== null) { App::blog()->withoutPassword(true);}\n";

        if (!empty($attr['with_pings'])) {
            $res .= 'App::frontend()->context()->pings = App::frontend()->context()->comments;' . "\n";
        }

        $res .= "?>\n";

        return $res . '<?php while (App::frontend()->context()->comments->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->comments = null; ?>';
    }

    /**
     * tpl:CommentAuthor [attributes] : Comment author (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentAuthor(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->comment_author') . ' ?>';
    }

    /**
     * tpl:CommentAuthorDomain : Comment author website domain (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentAuthorDomain(ArrayObject $attr): string
    {
        return '<?= preg_replace("#^http(?:s?)://(.+?)/.*$#msu",\'$1\',(string) App::frontend()->context()->comments->comment_site) ?>';
    }

    /**
     * tpl:CommentAuthorLink [attributes] : Comment author link (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentAuthorLink(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->getAuthorLink()') . ' ?>';
    }

    /**
     * tpl:CommentAuthorMailMD5 : Comment author mail MD5 sum (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentAuthorMailMD5(ArrayObject $attr): string
    {
        return '<?= md5(App::frontend()->context()->comments->comment_email)  ?>';
    }

    /**
     * tpl:CommentAuthorURL [attributes] : Comment author URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentAuthorURL($attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->getAuthorURL()') . ' ?>';
    }

    /**
     * tpl:CommentContent [attributes] : Comment content (tpl value)
     *
     * attributes:
     *
     *      - absolute_urls   (10)        Convert URLs to absolutes URLs
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentContent(ArrayObject $attr): string
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->getContent(' . $urls . ')') . ' ?>';
    }

    /**
     * tpl:CommentDate [attributes] : Displays comment date (tpl value)
     *
     * attributes:
     *
     *      - format      string      Date format (see Date::str() by default if iso8601 or rfc822 not specified)
     *      - iso8601     (1|0)       If set, display date in ISO 8601 format
     *      - rfc822      (1|0)       If set, display date in RFC 822 format
     *      - upddt       (1|0)       If set, uses the comment update time
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentDate(ArrayObject $attr): string
    {
        $format = '';
        if (!empty($attr['format']) && is_string($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);
        $type    = (empty($attr['upddt']) ? '' : 'upddt');

        $filters = $this->getFilters($attr);

        if ($rfc822) {
            return '<?= ' . sprintf($filters, "App::frontend()->context()->comments->getRFC822Date('" . $type . "')") . ' ?>';
        }

        if ($iso8601) {
            return '<?= ' . sprintf($filters, "App::frontend()->context()->comments->getISO8601Date('" . $type . "')") . ' ?>';
        }

        return '<?= ' . sprintf($filters, "App::frontend()->context()->comments->getDate('" . $format . "','" . $type . "')") . ' ?>';
    }

    /**
     * tpl:CommentTime [attributes] : Displays comment date (tpl value)
     *
     * attributes:
     *
     *      - format      string      Time format
     *      - upddt       (1|0)       If set, uses the comment update time
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentTime(ArrayObject $attr): string
    {
        $format = '';
        if (!empty($attr['format']) && is_string($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $type = (empty($attr['upddt']) ? '' : 'upddt');

        return '<?= ' . sprintf($this->getFilters($attr), "App::frontend()->context()->comments->getTime('" . $format . "','" . $type . "')") . ' ?>';
    }

    /**
     * tpl:CommentEmail [attributes] : Displays author email (tpl value)
     *
     * attributes:
     *
     *      - spam_protected       (1|0)      Protect email from spam (default: 1)
     *      - any filters                     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentEmail(ArrayObject $attr): string
    {
        $protect = 'true';
        if (isset($attr['spam_protected']) && !(bool) $attr['spam_protected']) {
            $protect = 'false';
        }

        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->getEmail(' . $protect . ')') . ' ?>';
    }

    /**
     * tpl:CommentEntryTitle [attributes] : Displays title of the comment entry (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentEntryTitle(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->post_title') . ' ?>';
    }

    /**
     * tpl:CommentFeedID [attributes] : Displays comment feed ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentFeedID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->getFeedID()') . ' ?>';
    }

    /**
     * tpl:CommentID : Displays comment ID (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentID(ArrayObject $attr): string
    {
        return '<?= App::frontend()->context()->comments->comment_id ?>';
    }

    /**
     * tpl:CommentIf [attributes] : Includes content depending on comment test (tpl block)
     *
     * attributes:
     *
     *      - is_ping     (0|1)       Tracckback (if 1) or not (if 0)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CommentIf(ArrayObject $attr, string $content): string
    {
        /**
         * @var ArrayObject<array-key, string> $if
         */
        $if = new ArrayObject();

        if (isset($attr['is_ping'])) {
            $sign = (bool) $attr['is_ping'] ? '' : '!';
            $if->append($sign . 'App::frontend()->context()->comments->comment_trackback');
        }

        # --BEHAVIOR-- templatePrepareParams -- string, ArrayObject, array<int,string>
        App::behavior()->callBehavior('tplIfConditions', 'CommentIf', $attr, $content, $if);

        if (count($if) > 0) {
            return '<?php if(' . implode(' && ', $if->getArrayCopy()) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /**
     * tpl:CommentIfFirst [attributes] : Displays value if comment is the first one (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: first)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentIfFirst(ArrayObject $attr): string
    {
        $ret = isset($attr['return']) && is_string($ret = $attr['return']) ? $ret : 'first';
        $ret = Html::escapeHTML($ret);

        return '<?php if (App::frontend()->context()->comments->index() == 0) { echo \'' . addslashes($ret) . "'; } ?>";
    }

    /**
     * tpl:CommentIfMe [attributes] : Displays value if comment is from the entry author (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: me)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentIfMe(ArrayObject $attr): string
    {
        $ret = isset($attr['return']) && is_string($ret = $attr['return']) ? $ret : 'me';
        $ret = Html::escapeHTML($ret);

        return '<?php if (App::frontend()->context()->comments->isMe()) { echo \'' . addslashes($ret) . "'; } ?>";
    }

    /**
     * tpl:CommentIfUs [attributes] : Displays value if comment is from one of the blog authors (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: me)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentIfUs(ArrayObject $attr): string
    {
        $ret = isset($attr['return']) && is_string($ret = $attr['return']) ? $ret : 'us';
        $ret = Html::escapeHTML($ret);

        return '<?php if (App::frontend()->context()->comments->isUs()) { echo \'' . addslashes($ret) . "'; } ?>";
    }

    /**
     * tpl:CommentIfOdd [attributes] : Displays value if comment is at an odd position (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: odd)
     *      - even        string      Value to display if it is not the case (default: <empty>)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentIfOdd(ArrayObject $attr): string
    {
        $odd = isset($attr['return']) && is_string($odd = $attr['return']) ? $odd : 'odd';
        $odd = Html::escapeHTML($odd);

        $even = isset($attr['even']) && is_string($even = $attr['even']) ? $even : '';
        $even = Html::escapeHTML($even);

        return '<?= ((App::frontend()->context()->comments->index()+1)%2 ? "' . addslashes($odd) . '" : "' . addslashes($even) . '") ?>';
    }

    /**
     * tpl:CommentIfEven [attributes] : Displays value if comment is at an even position (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: even)
     *      - odd         string      Value to display if it is not the case (default: <empty>)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentIfEven(ArrayObject $attr): string
    {
        $even = isset($attr['return']) && is_string($even = $attr['return']) ? $even : 'even';
        $even = Html::escapeHTML($even);

        $odd = isset($attr['odd']) && is_string($odd = $attr['odd']) ? $odd : '';
        $odd = Html::escapeHTML($odd);

        return '<?= ((App::frontend()->context()->comments->index()+1)%2+1 ? "' . addslashes($even) . '" : "' . addslashes($odd) . '") ?>';
    }

    /**
     * tpl:CommentIP : Displays comment IP (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentIP(ArrayObject $attr): string
    {
        return '<?= App::frontend()->context()->comments->comment_ip ?>';
    }

    /**
     * tpl:CommentOrderNumber : Displays comment order in page (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentOrderNumber(ArrayObject $attr): string
    {
        return '<?= App::frontend()->context()->comments->index()+1 ?>';
    }

    /**
     * tpl:CommentsHeader : First comments result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CommentsHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->comments->isStart()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:CommentsFooter : Last comments result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CommentsFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->comments->isEnd()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:CommentPostURL [attributes] : Displays comment entry URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentPostURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comments->getPostURL()') . ' ?>';
    }

    /**
     * tpl:IfCommentAuthorEmail : Includes content if comment author email is set (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function IfCommentAuthorEmail(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->comments->comment_email) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:CommentHelp : Includes syntax localized mini help (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function CommentHelp(ArrayObject $attr, string $content): string
    {
        return
        "<?php if (App::blog()->settings()->get('system')->getBool('wiki_comments', false)) {\n" .
        "  echo __('Comments can be formatted using a simple wiki syntax.');\n" .
        "} else {\n" .
        "  echo __('HTML code is displayed as text and web addresses are automatically converted.');\n" .
        '} ?>';
    }

    /**
     * tpl:IfCommentPreviewOptional : Includes content if comment preview is optional or currently previewed (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function IfCommentPreviewOptional(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::blog()->settings()->get("system")->getBool("comment_preview_optional", false) || (App::frontend()->context()->comment_preview !== null && App::frontend()->context()->comment_preview["preview"])) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:IfCommentPreview : Includes content if comment is being previewed (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function IfCommentPreview(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->comment_preview !== null && App::frontend()->context()->comment_preview["preview"]) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:CommentPreviewName [attributes] : Displays Author name for the previewed comment (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentPreviewName(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comment_preview["name"]') . ' ?>';
    }

    /**
     * tpl:CommentPreviewEmail [attributes] : Displays Author email for the previewed comment (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentPreviewEmail(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comment_preview["mail"]') . ' ?>';
    }

    /**
     * tpl:CommentPreviewSite [attributes] : Displays Author site for the previewed comment (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentPreviewSite(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->comment_preview["site"]') . ' ?>';
    }

    /**
     * tpl:CommentPreviewContent [attributes] : Displays content of the previewed comment (tpl value)
     *
     * attributes:
     *
     *      - raw         (1|0)   Display comment in raw content
     *      - any filters         See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentPreviewContent(ArrayObject $attr): string
    {
        if (!empty($attr['raw'])) {
            $content = 'App::frontend()->context()->comment_preview["rawcontent"]';
        } else {
            $content = 'App::frontend()->context()->comment_preview["content"]';
        }

        return '<?= ' . sprintf($this->getFilters($attr), $content) . ' ?>';
    }

    /**
     * tpl:CommentPreviewCheckRemember : checkbox attribute for "remember me" (same value as before preview) (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function CommentPreviewCheckRemember(ArrayObject $attr): string
    {
        return "<?php if (App::frontend()->context()->comment_preview['remember']) { echo ' checked=\"checked\"'; } ?>";
    }
}
