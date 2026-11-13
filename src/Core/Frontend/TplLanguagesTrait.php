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

trait TplLanguagesTrait
{
    /**
     * tpl:Languages [attributes] : Languages loop (tpl block)
     *
     * attributes:
     *
     *      - lang        string      Restrict loop on given lang
     *      - order       (desc|asc)  Languages ordering (default: desc)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function Languages(ArrayObject $attr, string $content): string
    {
        $params = "if (!isset(\$params)) \$params = [];\n";

        if (isset($attr['lang']) && is_string($attr['lang'])) {
            $params = "\$params['lang'] = '" . addslashes($attr['lang']) . "';\n";
        }

        if (isset($attr['order']) && is_string($attr['order']) && (bool) preg_match('/^(desc|asc)$/i', $attr['order'])) {
            $params .= "\$params['order'] = '" . $attr['order'] . "';\n ";
        }

        $res = "<?php\n";
        $res .= $params;
        # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
        $res .= App::behavior()->callBehavior(
            'templatePrepareParams',
            ['tag' => 'Languages', 'method' => 'blog::getLangs'],
            $attr,
            $content
        );
        $res .= 'App::frontend()->context()->langs = App::blog()->getLangs($params); unset($params);' . "\n";
        $res .= "?>\n";

        return $res . ('<?php if (App::frontend()->context()->langs->count() > 1) : ' . 'while (App::frontend()->context()->langs->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->langs = null; endif; ?>');
    }

    /**
     * tpl:LanguagesHeader : First languages result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function LanguagesHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->langs->isStart()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:LanguagesFooter : Last languages result container (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function LanguagesFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->langs->isEnd()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:LanguageCode [attributes] : Display language code (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function LanguageCode(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->langs->post_lang') . ' ?>';
    }

    /**
     * tpl:LanguageIfCurrent : Includes content if post language is current language (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function LanguageIfCurrent(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->cur_lang == App::frontend()->context()->langs->post_lang) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:LanguageURL [attributes] : Display language URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function LanguageURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::blog()->url().App::url()->getURLFor("lang",App::frontend()->context()->langs->post_lang)') . ' ?>';
    }

    /**
     * tpl:FeedLanguage [attributes] : Display feed language (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function FeedLanguage(ArrayObject $attr): string
    {
        $filters = $this->getFilters($attr);

        return
        '<?php if (App::frontend()->context()->exists("cur_lang")) ' . "\n" .
        '   { echo ' . sprintf($filters, 'App::frontend()->context()->cur_lang') . '; }' . "\n" .
        'elseif (App::frontend()->context()->exists("posts") && App::frontend()->context()->posts->exists("post_lang")) ' . "\n" .
        '   { echo ' . sprintf($filters, 'App::frontend()->context()->posts->post_lang') . '; }' . "\n" .
        'else ' . "\n" .
        '   { echo ' . sprintf($filters, 'App::blog()->settings()->get("system")->getStr("lang", false)') . '; } ?>';
    }
}
