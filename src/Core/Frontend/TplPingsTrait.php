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

trait TplPingsTrait
{
    /**
     * tpl:PingBlogName [attributes] : Displays trackback blog name (tpl value)
     *
     * attributes:
     *
     *      - any filters         See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingBlogName(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->pings->comment_author') . ' ?>';
    }

    /**
     * tpl:PingContent [attributes] : Displays trackback content (tpl value)
     *
     * attributes:
     *
     *      - any filters         See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingContent(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->pings->getTrackbackContent()') . ' ?>';
    }

    /**
     * tpl:PingDate [attributes] : Displays trackback date (tpl value)
     *
     * attributes:
     *
     *      - format      string      Date format (see Date::str() by default if iso8601 or rfc822 not specified)
     *      - iso8601     (1|0)       If set, display date in ISO 8601 format
     *      - rfc822      (1|0)       If set, display date in RFC 822 format
     *      - upddt       (1|0)       If set, uses the ping update time
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingDate(ArrayObject $attr): string
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
            return '<?= ' . sprintf($filters, "App::frontend()->context()->pings->getRFC822Date('" . $type . "')") . ' ?>';
        }

        if ($iso8601) {
            return '<?= ' . sprintf($filters, "App::frontend()->context()->pings->getISO8601Date('" . $type . "')") . ' ?>';
        }

        return '<?= ' . sprintf($filters, "App::frontend()->context()->pings->getDate('" . $format . "','" . $type . "')") . ' ?>';
    }

    /**
     * tpl:PingTime [attributes] : Displays trackback date (tpl value)
     *
     * attributes:
     *
     *      - format      string      Time format
     *      - upddt       (1|0)       If set, uses the ping update time
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingTime(ArrayObject $attr): string
    {
        $format = '';
        if (!empty($attr['format']) && is_string($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $type = (empty($attr['upddt']) ? '' : 'upddt');

        return '<?= ' . sprintf($this->getFilters($attr), "App::frontend()->context()->pings->getTime('" . $format . "','" . $type . "')") . ' ?>';
    }

    /**
     * tpl:PingEntryTitle [attributes] : Displays trackback entry title (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingEntryTitle(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->pings->post_title') . ' ?>';
    }

    /**
     * tpl:PingFeedID [attributes] : Displays trackback feed ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingFeedID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->pings->getFeedID()') . ' ?>';
    }

    /**
     * tpl:PingID : Displays ping ID (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingID(ArrayObject $attr): string
    {
        return '<?= App::frontend()->context()->pings->comment_id ?>';
    }

    /**
     * tpl:PingIfFirst [attributes] : Displays value if trackback is the first one (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: first)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingIfFirst(ArrayObject $attr): string
    {
        $ret = isset($attr['return']) && is_string($ret = $attr['return']) ? $ret : 'first';
        $ret = Html::escapeHTML($ret);

        return '<?php if (App::frontend()->context()->pings->index() == 0) { echo \'' . addslashes($ret) . "'; } ?>";
    }

    /**
     * tpl:PingIfOdd [attributes] : Displays value if trackback is at an odd position (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: odd)
     *      - even        string      Value to display if it is not the case (default: <empty>)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingIfOdd(ArrayObject $attr): string
    {
        $odd = isset($attr['return']) && is_string($odd = $attr['return']) ? $odd : 'odd';
        $odd = Html::escapeHTML($odd);

        $even = isset($attr['even']) && is_string($even = $attr['even']) ? $even : '';
        $even = Html::escapeHTML($even);

        return '<?= ((App::frontend()->context()->pings->index()+1)%2 ? "' . addslashes($odd) . '" : "' . addslashes($even) . '") ?>';
    }

    /**
     * tpl:PingIfEven [attributes] : Displays value if trackback is at an even position (tpl value)
     *
     * attributes:
     *
     *      - return      string      Value to display if it is the case (default: even)
     *      - odd         string      Value to display if it is not the case (default: <empty>)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingIfEven(ArrayObject $attr): string
    {
        $even = isset($attr['even']) && is_string($even = $attr['even']) ? $even : 'even';
        $even = Html::escapeHTML($even);

        $odd = isset($attr['return']) && is_string($odd = $attr['return']) ? $odd : '';
        $odd = Html::escapeHTML($odd);

        return '<?= ((App::frontend()->context()->pings->index()+1)%2+1 ? "' . addslashes($even) . '" : "' . addslashes($odd) . '") ?>';
    }

    /**
     * tpl:PingIP : Displays ping author IP (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingIP(ArrayObject $attr): string
    {
        return '<?= App::frontend()->context()->pings->comment_ip ?>';
    }

    /**
     * tpl:PingNoFollow : Displays 'rel="nofollow"' if set in blog (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingNoFollow(ArrayObject $attr): string
    {
        return '<?php if (App::blog()->settings()->get("system")->getBool("comments_nofollow", false)) { echo \' rel="nofollow"\'; } ?>';
    }

    /**
     * tpl:PingOrderNumber : Displays trackback order in page, 1 based (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingOrderNumber(ArrayObject $attr): string
    {
        return '<?= App::frontend()->context()->pings->index()+1 ?>';
    }

    /**
     * tpl:PingPostURL [attributes] : Displays trackback entry URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingPostURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->pings->getPostURL()') . ' ?>';
    }

    /**
     * tpl:Pings [attributes] : Pings container (tpl block)
     *
     * attributes:
     *
     *      - lastn       int         Restrict the number of pings
     *      - no_context  (0|1)       Override context information
     *      - order       (desc|asc)  Result ordering (default: asc)
     *      - no_content  (0|1)       Do not include pings' content
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function Pings(ArrayObject $attr, string $content): string
    {
        $params = 'if (App::frontend()->context()->posts !== null) { ' .
            "\$params['post_id'] = App::frontend()->context()->posts->post_id; " .
            "App::blog()->withoutPassword(false);\n" .
            "}\n";

        $params .= "\$params['comment_trackback'] = true;\n";

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
            $params .= 'if (App::frontend()->context()->exists("categories")) { ' .
                "\$params['cat_id'] = App::frontend()->context()->categories->cat_id; " .
                "}\n";

            $params .= 'if (App::frontend()->context()->exists("langs")) { ' .
                "\$params['sql'] = \"AND P.post_lang = '\".App::blog()->con->escapeStr(App::frontend()->context()->langs->post_lang).\"' \"; " .
                "}\n";
        }

        $order = 'asc';
        if (isset($attr['order']) && is_string($attr['order']) && (bool) preg_match('/^(desc|asc)$/i', $attr['order'])) {
            $order = $attr['order'];
        }

        $params .= "\$params['order'] = 'comment_dt " . $order . "';\n";

        if (isset($attr['no_content']) && (bool) $attr['no_content']) {
            $params .= "\$params['no_content'] = true;\n";
        }

        $res = "<?php\n";
        $res .= $params;
        # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
        $res .= App::behavior()->callBehavior(
            'templatePrepareParams',
            ['tag' => 'Pings', 'method' => 'blog::getComments'],
            $attr,
            $content
        );
        $res .= 'App::frontend()->context()->pings = App::blog()->getComments($params); unset($params);' . "\n";
        $res .= "if (App::frontend()->context()->posts !== null) { App::blog()->withoutPassword(true);}\n";
        $res .= "?>\n";

        return $res . ('<?php while (App::frontend()->context()->pings->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->pings = null; ?>');
    }

    /**
     * tpl:PingsHeader : First pings result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function PingsHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->pings->isStart()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:PingsFooter : Last pings result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function PingsFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->pings->isEnd()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:PingTitle [attributes] : Displays trackback title (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingTitle(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->pings->getTrackbackTitle()') . ' ?>';
    }

    /**
     * tpl:PingAuthorURL [attributes] : Displays trackback author URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function PingAuthorURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->pings->getAuthorURL()') . ' ?>';
    }
}
