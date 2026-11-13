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

trait TplArchivesTrait
{
    /**
     * tpl:Archives [attributes] : Archives dates loop (tpl block)
     *
     * attributes:
     *
     *      - type           (day|month|year)        Get days, months or years, default to "month"
     *      - category       category URL            Get dates of given category
     *      - no_context     (1|0)                   Override context information
     *      - order          (asc|desc)              Sort asc or desc
     *      - post_type      type                    Get dates of given type of entries, default to "post"
     *      - post_status    level                   Filter on the given comma separated entries status levels
     *      - post_lang      lang                    Filter on the given language
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function Archives(ArrayObject $attr, string $content): string
    {
        $params = "if (!isset(\$params)) \$params = [];\n" . "\$params['type'] = 'month';\n";

        if (isset($attr['type']) && is_string($attr['type'])) {
            $params .= "\$params['type'] = '" . addslashes($attr['type']) . "';\n";
        }

        if (isset($attr['category']) && is_string($attr['category'])) {
            $params .= "\$params['cat_url'] = '" . addslashes($attr['category']) . "';\n";
        }

        if (isset($attr['post_type']) && is_string($attr['post_type'])) {
            $params .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (!empty($attr['post_status']) && is_string($attr['post_status'])) {
            $params .= "\$params['post_status'] = preg_split('/\s*,\s*/','" . addslashes($attr['post_status']) . "',-1,PREG_SPLIT_NO_EMPTY);\n";
        }

        if (isset($attr['post_lang']) && is_string($attr['post_lang'])) {
            $params .= "\$params['post_lang'] = '" . addslashes($attr['post_lang']) . "';\n";
        }

        if (empty($attr['no_context']) && !isset($attr['category'])) {
            $params .= 'if (App::frontend()->context()->exists("categories")) { ' . "\$params['cat_id'] = App::frontend()->context()->categories->cat_id; " . "}\n";
        }

        if (isset($attr['order']) && is_string($attr['order']) && (bool) preg_match('/^(desc|asc)$/i', $attr['order'])) {
            $params .= "\$params['order'] = '" . $attr['order'] . "';\n ";
        }

        $res = "<?php\n" .
            $params .
            # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
             App::behavior()->callBehavior(
                 'templatePrepareParams',
                 ['tag' => 'Archives', 'method' => 'blog::getDates'],
                 $attr,
                 $content
             ) .
            'App::frontend()->context()->archives = App::blog()->getDates($params); unset($params);' . "\n" .
            "?>\n";

        return $res . ('<?php while (App::frontend()->context()->archives->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->archives = null; ?>');
    }

    /**
     * tpl:ArchivesHeader : Display content on first archive element (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function ArchivesHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->archives->isStart()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:ArchivesFooter : Display content on last archive element (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function ArchivesFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->archives->isEnd()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:ArchivesYearHeader : Display content on first archive element of year (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function ArchivesYearHeader(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->archives->yearHeader()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:ArchivesYearFooter : Display content on last archive element of year (tpl block)
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function ArchivesYearFooter(ArrayObject $attr, string $content): string
    {
        return '<?php if (App::frontend()->context()->archives->yearFooter()) : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tpl:ArchivesDate [attributes] : Display archive element date (tpl value)
     *
     * attributes:
     *
     *      - format          Date format (default %B %Y)
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function ArchiveDate(ArrayObject $attr): string
    {
        $format = '%B %Y';
        if (!empty($attr['format']) && is_string($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        return '<?= ' . sprintf($this->getFilters($attr), Date::class . "::dt2str('" . $format . "',App::frontend()->context()->archives->dt)") . ' ?>';
    }

    /**
     * tpl:ArchivesEntriesCount [attributes] : Display archive number of entries (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function ArchiveEntriesCount(ArrayObject $attr): string
    {
        return $this->displayCounter(
            sprintf($this->getFilters($attr), 'App::frontend()->context()->archives->nb_post'),
            [
                'none' => 'no archive',
                'one'  => 'one archive',
                'more' => '%d archives',
            ],
            $attr,
            true
        );
    }

    /**
     * tpl:ArchiveNext [attributes] : Archives next entries (tpl block)
     *
     * attributes:
     *
     *      - type           (day|month|year)        Get days, months or years, default to "month"
     *      - post_type      type                    Get dates of given type of entries, default to "post"
     *      - post_status    level                   Filter on the given comma separated entries status levels
     *      - post_lang      lang                    Filter on the given language
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function ArchiveNext(ArrayObject $attr, string $content): string
    {
        $params = "if (!isset(\$params)) \$params = [];\n";
        $params .= "\$params['type'] = 'month';\n";
        if (isset($attr['type']) && is_string($attr['type'])) {
            $params .= "\$params['type'] = '" . addslashes($attr['type']) . "';\n";
        }

        if (isset($attr['post_type']) && is_string($attr['post_type'])) {
            $params .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (!empty($attr['post_status']) && is_string($attr['post_status'])) {
            $params .= "\$params['post_status'] = preg_split('/\s*,\s*/','" . addslashes($attr['post_status']) . "',-1,PREG_SPLIT_NO_EMPTY);\n";
        }

        if (isset($attr['post_lang']) && is_string($attr['post_lang'])) {
            $params .= "\$params['post_lang'] = '" . addslashes($attr['post_lang']) . "';\n";
        }

        $params .= "\$params['next'] = App::frontend()->context()->archives->dt;";

        $res = "<?php\n";
        $res .= $params;
        # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
        $res .= App::behavior()->callBehavior(
            'templatePrepareParams',
            ['tag' => 'ArchiveNext', 'method' => 'blog::getDates'],
            $attr,
            $content
        );
        $res .= 'App::frontend()->context()->archives = App::blog()->getDates($params); unset($params);' . "\n";
        $res .= "?>\n";

        return $res . ('<?php while (App::frontend()->context()->archives->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->archives = null; ?>');
    }

    /**
     * tpl:ArchivePrevious [attributes] : Archives previous entries (tpl block)
     *
     * attributes:
     *
     *      - type           (day|month|year)        Get days, months or years, default to "month"
     *      - post_type      type                    Get dates of given type of entries, default to "post"
     *      - post_status    level                   Filter on the given comma separated entries status levels
     *      - post_lang      lang                    Filter on the given language
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     * @param      string                        $content  The content
     */
    public function ArchivePrevious(ArrayObject $attr, string $content): string
    {
        $params = 'if (!isset($params)) $params = [];';
        $params .= "\$params['type'] = 'month';\n";
        if (isset($attr['type']) && is_string($attr['type'])) {
            $params .= "\$params['type'] = '" . addslashes($attr['type']) . "';\n";
        }

        if (isset($attr['post_type']) && is_string($attr['post_type'])) {
            $params .= "\$params['post_type'] = '" . addslashes($attr['post_type']) . "';\n";
        }

        if (!empty($attr['post_status']) && is_string($attr['post_status'])) {
            $params .= "\$params['post_status'] = preg_split('/\s*,\s*/','" . addslashes($attr['post_status']) . "',-1,PREG_SPLIT_NO_EMPTY);\n";
        }

        if (isset($attr['post_lang']) && is_string($attr['post_lang'])) {
            $params .= "\$params['post_lang'] = '" . addslashes($attr['post_lang']) . "';\n";
        }

        $params .= "\$params['previous'] = App::frontend()->context()->archives->dt;";

        $res = "<?php\n";
        # --BEHAVIOR-- templatePrepareParams -- string, array<string,string>, ArrayObject, string
        $res .= App::behavior()->callBehavior(
            'templatePrepareParams',
            ['tag' => 'ArchivePrevious', 'method' => 'blog::getDates'],
            $attr,
            $content
        );
        $res .= $params;
        $res .= 'App::frontend()->context()->archives = App::blog()->getDates($params); unset($params);' . "\n";
        $res .= "?>\n";

        return $res . ('<?php while (App::frontend()->context()->archives->fetch()) : ?>' . $content . '<?php endwhile; App::frontend()->context()->archives = null; ?>');
    }

    /**
     * tpl:ArchivesURL [attributes] : Display archive result URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public function ArchiveURL(ArrayObject $attr): string
    {
        return '<?= ' . sprintf($this->getFilters($attr), 'App::frontend()->context()->archives->url()') . ' ?>';
    }
}
