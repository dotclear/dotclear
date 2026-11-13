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
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Helper\Network\Http;

trait TplSystemTrait
{
    /**
     * tpl:SysBehavior [attributes] : Call a given behavior (tpl value)
     *
     * attributes:
     * * behavior        string      Behavior to call
     *
     * @todo    Remove old dcCore from tpl::SysBehavior returned call parameters
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function SysBehavior(ArrayObject $attr): string
    {
        if (!isset($attr['behavior']) || !is_string($attr['behavior'])) {
            return '';
        }

        $behavior = addslashes($attr['behavior']);

        return
            '<?php if (App::behavior()->hasBehavior(\'' . $behavior . '\')) { ' .
            'App::behavior()->callBehavior(\'' . $behavior . '\',App::config()->modern()?null:dcCore::app(),App::frontend()->context());' .
            '} ?>';
    }

    /**
     * tpl:SysIf [attributes] : Includes content depending on system test (tpl block)
     *
     * attributes:
     *
     *      - categories      (0|1)                   Categories are set in current context (if 1) or not (if 0)
     *      - posts           (0|1)                   Posts are set in current context (if 1) or not (if 0)
     *      - blog_lang       string                  Blog language is the one given in parameter, see note 1
     *      - current_tpl     string                  Current template is the one given in paramater, see note 1
     *      - current_mode    string                  Current URL mode is the one given in parameter, see note 1
     *      - has_tpl         string                  Named template exists, see note 1
     *      - has_tag         string                  Named template block or value exists, see note 1
     *      - blog_id         string                  Current blog ID is the one given in parameter, see note 1
     *      - comments_active (0|1)                   Comments are enabled blog-wide
     *      - pings_active    (0|1)                   Trackbacks are enabled blog-wide
     *      - wiki_comments   (0|1)                   Wiki syntax is enabled for comments
     *      - search_count    (=|!|>=|<=|>|<) int     Search count valids condition
     *      - jquery_needed   (0|1)                   jQuery javascript library is requested (if 1) or not (if 0)
     *      - legacy_needed   (0|1)                   Legacy javascript library is requested (if 1) or not (if 0)
     *      - operator        (and|or)                Combination of conditions, if more than 1 specifiec (default: and)
     *
     * Notes:
     *
     *  1) Prefix with a ! to reverse test
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function SysIf(ArrayObject $attr, string $content): string
    {
        /**
         * @var ArrayObject<array-key, string> $if
         */
        $if = new ArrayObject();

        $operator = isset($attr['operator']) && is_string($attr['operator']) ? static::getOperator($attr['operator']) : '&&';

        if (isset($attr['categories'])) {
            $sign = (bool) $attr['categories'] ? '!' : '=';
            $if->append('App::frontend()->context()->categories ' . $sign . '== null');
        }

        if (isset($attr['posts'])) {
            $sign = (bool) $attr['posts'] ? '!' : '=';
            $if->append('App::frontend()->context()->posts ' . $sign . '== null');
        }

        if (isset($attr['blog_lang']) && is_string($attr['blog_lang'])) {
            $sign = '=';
            if (str_starts_with($attr['blog_lang'], '!')) {
                $sign              = '!';
                $attr['blog_lang'] = substr($attr['blog_lang'], 1);
            }

            $if->append('App::blog()->settings()->get("system")->getStr("lang", false) ' . $sign . "= '" . addslashes($attr['blog_lang']) . "'");
        }

        if (isset($attr['current_tpl']) && is_string($attr['current_tpl'])) {
            $sign = '=';
            if (str_starts_with($attr['current_tpl'], '!')) {
                $sign                = '!';
                $attr['current_tpl'] = substr($attr['current_tpl'], 1);
            }

            $if->append('App::frontend()->context()->current_tpl ' . $sign . "= '" . addslashes($attr['current_tpl']) . "'");
        }

        if (isset($attr['current_mode']) && is_string($attr['current_mode'])) {
            $sign = '=';
            if (str_starts_with($attr['current_mode'], '!')) {
                $sign                 = '!';
                $attr['current_mode'] = substr($attr['current_mode'], 1);
            }

            $if->append('App::url()->getType() ' . $sign . "= '" . addslashes($attr['current_mode']) . "'");
        }

        if (isset($attr['has_tpl']) && is_string($attr['has_tpl'])) {
            $sign = '';
            if (str_starts_with($attr['has_tpl'], '!')) {
                $sign            = '!';
                $attr['has_tpl'] = substr($attr['has_tpl'], 1);
            }

            $if->append($sign . "App::frontend()->template()->getFilePath('" . addslashes($attr['has_tpl']) . "') !== false");
        }

        if (isset($attr['has_tag']) && is_string($attr['has_tag'])) {
            $sign = 'true';
            if (str_starts_with($attr['has_tag'], '!')) {
                $sign            = 'false';
                $attr['has_tag'] = substr($attr['has_tag'], 1);
            }

            $if->append("App::frontend()->template()->tagExists('" . addslashes($attr['has_tag']) . "') === " . $sign);
        }

        if (isset($attr['blog_id']) && is_string($attr['blog_id'])) {
            $sign = '';
            if (str_starts_with($attr['blog_id'], '!')) {
                $sign            = '!';
                $attr['blog_id'] = substr($attr['blog_id'], 1);
            }

            $if->append($sign . "(App::blog()->id() == '" . addslashes($attr['blog_id']) . "')");
        }

        if (isset($attr['comments_active'])) {
            $sign = (bool) $attr['comments_active'] ? '' : '!';
            $if->append($sign . 'App::blog()->settings()->get("system")->getBool("allow_comments", false)');
        }

        if (isset($attr['pings_active'])) {
            $sign = (bool) $attr['pings_active'] ? '' : '!';
            $if->append($sign . 'App::blog()->settings()->get("system")->getBool("allow_trackbacks", false)');
        }

        if (isset($attr['wiki_comments'])) {
            $sign = (bool) $attr['wiki_comments'] ? '' : '!';
            $if->append($sign . 'App::blog()->settings()->get("system")->getBool("wiki_comments", false)');
        }

        if (isset($attr['search_count'])
            && is_string($attr['search_count'])
            && (bool) preg_match('/^((=|!|&gt;|&lt;)=|(&gt;|&lt;))\s*\d+$/', trim($attr['search_count']))
        ) {
            $if->append('(isset(App::frontend()->search_count) && App::frontend()->search_count ' . Html::decodeEntities($attr['search_count']) . ')');
        }

        if (isset($attr['jquery_needed'])) {
            $sign = (bool) $attr['jquery_needed'] ? '' : '!';
            $if->append($sign . 'App::blog()->settings()->get("system")->getBool("jquery_needed", false)');
        }

        if (isset($attr['legacy_needed'])) {
            $sign = (bool) $attr['legacy_needed'] ? '' : '!';
            $if->append($sign . 'App::blog()->settings()->get("system")->getBool("legacy_needed", false)');
        }

        # --BEHAVIOR-- templatePrepareParams -- string, ArrayObject, array<int,string>
        App::behavior()->callBehavior('tplIfConditions', 'SysIf', $attr, $content, $if);

        if (count($if) > 0) {
            return '<?php if(' . implode(' ' . $operator . ' ', $if->getArrayCopy()) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /**
     * tpl:SysIfCommentPublished : Includes content if comment has been published (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function SysIfCommentPublished(ArrayObject $attr, string $content): string
    {
        return '<?php if (!empty($_GET[\'pub\'])) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:SysIfCommentPending : Includes content if comment is pending after submission (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function SysIfCommentPending(ArrayObject $attr, string $content): string
    {
        return '<?php if (isset($_GET[\'pub\']) && $_GET[\'pub\'] == 0) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:SysFeedSubtitle [attributes] : Displays feed subtitle (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function SysFeedSubtitle(ArrayObject $attr): string
    {
        return '<?php if (App::frontend()->context()->feed_subtitle !== null) { echo ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->feed_subtitle') . ';} ?>';
    }

    /**
     * tpl:SysIfFormError : Includes content if an error has been detected after form submission (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function SysIfFormError(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->form_error !== null) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:SysFormError : Displays form error (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function SysFormError(ArrayObject $attr): string
    {
        return '<?php if (App::frontend()->context()->form_error !== null) { echo App::frontend()->context()->form_error; } ?>';
    }

    /**
     * tpl:SysPoweredBy : Displays localized powered by (tpl value)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function SysPoweredBy(ArrayObject $attr): string
    {
        return '<?php printf(__("Powered by %s"),"<a href=\"https://dotclear.org/\">Dotclear</a>"); ?>';
    }

    /**
     * tpl:SysSearchString [attributes] : Displays search string if any (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function SysSearchString(ArrayObject $attr): string
    {
        $string = isset($attr['string']) && is_string($string = $attr['string']) ? $string : '%1$s';

        return '<?php if (isset(App::frontend()->search)) { echo sprintf(__(\'' . $string . '\'),' . sprintf($this->getFilters($attr), 'App::frontend()->search') . ',App::frontend()->search_count);} ?>';
    }

    /**
     * tpl:SysSelfURI [attributes] : Displays self URI (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function SysSelfURI(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), Http::class . '::getSelfURI()') . ' ?>';
    }

    /**
     * tpl:else : Displays else: statement (tpl value)
     *
     * May be used inside a tpl:If… block
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function GenericElse(ArrayObject $attr): string
    {
        return '<?php else: ?>';
    }
}
