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

trait TplBlogTrait
{
    /**
     * tpl:BlogArchiveURL [attributes] : Display blog archives URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogArchiveURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url().App::url()->getURLFor("archive")') . ' ?>';
    }

    /**
     * tpl:BlogCopyrightNotice [attributes] : Display blog copyright notice (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogCopyrightNotice(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->settings()->get("system")->getStr("copyright_notice", false)') . ' ?>';
    }

    /**
     * tpl:BlogDescription [attributes] : Display blog description (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogDescription(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->desc()') . ' ?>';
    }

    /**
     * tpl:BlogEditor [attributes] : Display blog editor (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogEditor(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->settings()->get("system")->getStr("editor", false)') . ' ?>';
    }

    /**
     * tpl:BlogFeedID [attributes] : Display blog feed ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogFeedID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), '"urn:md5:".App::blog()->uid()') . ' ?>';
    }

    /**
     * tpl:BlogFeedURL [attributes] : Display blog feed URL (tpl value)
     *
     * attributes:
     *
     *      - type            (rss2|atom)     Feed type, default to "atom"
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogFeedURL(ArrayObject $attr): string
    {
        $type = isset($attr['type']) && is_string($type = $attr['type']) ? $type : 'atom';
        if (!in_array($type, ['rss2', 'atom'], true)) {
            $type = 'atom';
        }

        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url().App::url()->getURLFor("feed","' . $type . '")') . ' ?>';
    }

    /**
     * tpl:BlogName [attributes] : Display blog name (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogName(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->name()') . ' ?>';
    }

    /**
     * tpl:BlogLanguage [attributes] : Display blog language (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogLanguage(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->settings()->get("system")->getStr("lang", false)') . ' ?>';
    }

    /**
     * tpl:BlogLanguageDirection [attributes] : Display blog language direction (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogLanguageDirection(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::lang()->getLanguageTextDirection(App::blog()->settings()->get("system")->getStr("lang", false))') . ' ?>';
    }

    /**
     * tpl:BlogLanguageURL [attributes] : Display blog localized URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogLanguageURL(ArrayObject $attr): string
    {
        $filters = $this->getFilters($attr);

        return '<?php if (App::frontend()->context()->exists("cur_lang")) echo ' . sprintf($filters, 'App::blog()->url().App::url()->getURLFor("lang",App::frontend()->context()->cur_lang)') . '; else echo ' . sprintf($filters, 'App::blog()->url()') . '; ?>';
    }

    /**
     * tpl:BlogThemeURL [attributes] : Display blog's current theme URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogThemeURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf(
            $this->getFilters($attr),
            'App::blog()->getTF(\'\', false)'
        ) . ' ?>';
    }

    /**
     * tpl:BlogParentThemeURL [attributes] : Display blog's current theme parent URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     *
     * @deprecated since 2.35 Use BlogThemeURL() instead as it will now look for custom > theme > parent theme > template set
     */
    public function BlogParentThemeURL(ArrayObject $attr): string
    {
        App::deprecated()->set(self::class . '::BlogThemeURL()', '2.35');

        return '<?= ' . sprintf(
            $this->getFilters($attr),
            'App::blog()->getTF(\'\', false)'
        ) . ' ?>';
    }

    /**
     * tpl:BlogPublicURL [attributes] : Display blog's public directory URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogPublicURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->settings()->get("system")->getStr("public_url", false)') . ' ?>';
    }

    /**
     * tpl:BlogUpdateDate [attributes] : Display blog last update date (tpl value)
     *
     * attributes:
     *
     *      - format                  Use Date::str() (if iso8601 nor rfc822 were specified default to %Y-%m-%d %H:%M:%S)
     *      - iso8601         (1|0)   Use Date::iso8601()
     *      - rfc822          (1|0)   Use Date::rfc822()
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogUpdateDate(ArrayObject $attr): string
    {
        $format  = isset($attr['format']) && is_string($format = $attr['format']) ? addslashes($format) : '%Y-%m-%d %H:%M:%S';
        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);

        $filters = $this->getFilters($attr);

        if ($rfc822) {
            return '<?= ' . sprintf($filters, Date::class . '::rfc822(App::blog()->upddt(),App::blog()->settings()->get("system")->getStr("blog_timezone", false))') . ' ?>';
        }

        if ($iso8601) {
            return '<?= ' . sprintf($filters, Date::class . '::iso8601(App::blog()->upddt(),App::blog()->settings()->get("system")->getStr("blog_timezone", false))') . ' ?>';
        }

        return '<?= ' . sprintf($filters, Date::class . "::str('" . $format . "',App::blog()->upddt())") . ' ?>';
    }

    /**
     * tpl:BlogID [attributes] : Display blog ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogID(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->id()') . ' ?>';
    }

    /**
     * tpl:BlogRSDURL [attributes] : Display blog RSD URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     *
     * @deprecated  since 2.24, use another format instead !
     */
    public function BlogRSDURL(ArrayObject $attr): string
    {
        App::deprecated()->set('', '2.24');

        return '';
    }

    /**
     * tpl:BlogXMLRPCURL [attributes] : Display blog XML-RPC URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogXMLRPCURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url().App::url()->getURLFor(\'xmlrpc\',App::blog()->id())') . ' ?>';
    }

    /**
     * tpl:BlogWebmentionURL [attributes] : Display blog Webmention endpoint URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogWebmentionURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url().App::url()->getURLFor(\'webmention\')') . ' ?>';
    }

    /**
     * tpl:BlogURL [attributes] : Display blog URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url()') . ' ?>';
    }

    /**
     * tpl:BlogQmarkURL [attributes] : Display blog URL including the question mark (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogQmarkURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->getQmarkURL()') . ' ?>';
    }

    /**
     * tpl:BlogMetaRobots [attributes] : Display blog robots policy (tpl value)
     *
     * attributes:
     *
     *      - robots          (INDEX|NOINDEX|FOLLOW|NOFOLLOW|ARCHIVE|NOARCHIVE)   will surcharge the blog's parameters
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogMetaRobots(ArrayObject $attr): string
    {
        $robots = isset($attr['robots']) && is_string($robots = $attr['robots']) ? addslashes($robots) : '';

        return '<?= ' . Ctx::class . "::robotsPolicy(App::blog()->settings()->get('system')->getStr('robots_policy', false),'" . $robots . "') ?>";
    }

    /**
     * tpl:BlogJsJQuery [attributes] : Include the jQuery javascript library (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogJsJQuery(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->getJsJQuery()') . ' ?>';
    }

    /**
     * tpl:BlogPostsURL [attributes] : Display the blog's posts URL (tpl value)
     *
     * Depends on blog's setting:
     *
     * - with a static home : URL of last posts
     * - without : URL of the blog
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogPostsURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), ('App::blog()->settings()->get("system")->getBool("static_home", false) ? App::blog()->url().App::url()->getURLFor("posts") : App::blog()->url()')) . ' ?>';
    }

    /**
     * tpl:IfBlogStaticEntryURL : Test if the blog has a static home entry (URL) (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function IfBlogStaticEntryURL(ArrayObject $attr, string $content): string
    {
        return "<?php if (App::blog()->settings()->get('system')->getStr('static_home_url', false) !== '') : ?>" . $content . '<?php endif; ?>';
    }

    /**
     * tpl:BlogStaticEntryURL [attributes] : Prepare the blog's static home URL entry (tpl value)
     *
     * Should be set before a tpl:Entries block to display the according entry (post, page, …)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogStaticEntryURL(ArrayObject $attr): string
    {
        $code = "\$params['post_type'] = array_keys(App::postTypes()->dump());\n" . "\$params['post_url'] = " . sprintf($this->getFilters($attr), 'urldecode(App::blog()->settings()->get("system")->getStr("static_home_url", false))') . ";\n";

        return "<?php\n" . $code . ' ?>';
    }

    /**
     * tpl:BlogNbEntriesFirstPage [attributes] : Display the number fo entries for home page (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogNbEntriesFirstPage(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), '(string) App::blog()->settings()->get("system")->getInt("nb_post_for_home", false)') . ' ?>';
    }

    /**
     * tpl:BlogNbEntriesPerPage [attributes] : Display the number fo entries per page (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function BlogNbEntriesPerPage(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), '(string) App::blog()->settings()->get("system")->getInt("nb_post_per_page", false)') . ' ?>';
    }
}
