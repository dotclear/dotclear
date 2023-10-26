<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\breadcrumb;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\L10n;

/**
 * @brief   The module frontend template.
 * @ingroup breadcrumb
 */
class FrontendTemplate
{
    /**
     * tpl:Breadcrumb [attributes] : Displays the blogroll (tpl value).
     *
     * attributes:
     *
     *      - separator   string      Breadcrumb element separator
     *
     * @param   ArrayObject<string, mixed>     $attr   The attributes
     *
     * @return  string
     */
    public static function breadcrumb(ArrayObject $attr): string
    {
        $separator = $attr['separator'] ?? '';

        return '<?php echo ' . self::class . '::displayBreadcrumb(' . "'" . addslashes($separator) . "'" . '); ?>';
    }

    /**
     * Return the breadcrumb.
     *
     * @param   string  $separator  The separator
     *
     * @return  string
     */
    public static function displayBreadcrumb(string $separator = ''): string
    {
        $ret = '';

        # Check if breadcrumb enabled for the current blog
        if (!My::settings()->breadcrumb_enabled) {
            return $ret;
        }

        if ($separator === '') {
            $separator = ' &rsaquo; ';
        }

        // Get current page if set
        $page = App::frontend()->getPageNumber();

        // Get blog URL
        $blogUrl = App::blog()->url();

        // Test if complete breadcrumb will be provided
        # --BEHAVIOR-- publicBreadcrumbExtended -- string
        if (App::behavior()->callBehavior('publicBreadcrumbExtended', App::url()->getType())) {
            # --BEHAVIOR-- publicBreadcrumb -- string, string
            $special = App::behavior()->callBehavior('publicBreadcrumb', App::url()->getType(), $separator);

            $ret = $special ?: '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
        } else {
            switch (App::url()->getType()) {
                case 'static':
                    // Static home
                    $ret = '<span id="bc-home">' . __('Home') . '</span>';

                    break;

                case 'default':
                    if (App::blog()->settings()->system->static_home) {
                        // Static home and on (1st) blog page
                        $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                        $ret .= $separator . __('Blog');
                    } else {
                        // Home (first page only)
                        $ret = '<span id="bc-home">' . __('Home') . '</span>';
                        if (App::frontend()->context()->cur_lang) {
                            $langs = L10n::getISOCodes();
                            $ret .= $separator . ($langs[App::frontend()->context()->cur_lang] ?? App::frontend()->context()->cur_lang);
                        }
                    }

                    break;

                case 'default-page':
                    // Home or blog page`(page 2 to n)
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (App::blog()->settings()->system->static_home) {
                        $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('posts') . '">' . __('Blog') . '</a>';
                    } else {
                        if (App::frontend()->context()->cur_lang) {
                            $langs = L10n::getISOCodes();
                            $ret .= $separator . ($langs[App::frontend()->context()->cur_lang] ?? App::frontend()->context()->cur_lang);
                        }
                    }
                    $ret .= $separator . sprintf(__('page %d'), $page);

                    break;

                case 'category':
                    // Category
                    $ret        = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $categories = App::blog()->getCategoryParents((int) App::frontend()->context()->categories->cat_id);
                    while ($categories->fetch()) {
                        $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                    }
                    if ($page == 0) {
                        $ret .= $separator . App::frontend()->context()->categories->cat_title;
                    } else {
                        $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', App::frontend()->context()->categories->cat_url) . '">' . App::frontend()->context()->categories->cat_title . '</a>';
                        $ret .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case 'post':
                    // Post
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (App::frontend()->context()->posts->cat_id) {
                        // Parents cats of post's cat
                        $categories = App::blog()->getCategoryParents((int) App::frontend()->context()->posts->cat_id);
                        while ($categories->fetch()) {
                            $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                        }
                        // Post's cat
                        $categories = App::blog()->getCategory((int) App::frontend()->context()->posts->cat_id);
                        $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                    }
                    $ret .= $separator . App::frontend()->context()->posts->post_title;

                    break;

                case 'lang':
                    // Lang
                    $ret   = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $langs = L10n::getISOCodes();
                    $ret .= $separator . ($langs[App::frontend()->context()->cur_lang] ?? App::frontend()->context()->cur_lang);

                    break;

                case 'archive':
                    // Archives
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (!App::frontend()->context()->archives) {
                        // Global archives
                        $ret .= $separator . __('Archives');
                    } else {
                        // Month archive
                        $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('archive') . '">' . __('Archives') . '</a>';
                        $ret .= $separator . Date::dt2str('%B %Y', App::frontend()->context()->archives->dt);
                    }

                    break;

                case 'pages':
                    // Page
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $ret .= $separator . App::frontend()->context()->posts->post_title;

                    break;

                case 'tags':
                    // All tags
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $ret .= $separator . __('All tags');

                    break;

                case 'tag':
                    // Tag
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('tags') . '">' . __('All tags') . '</a>';
                    if ($page == 0) {
                        $ret .= $separator . App::frontend()->context()->meta->meta_id;
                    } else {
                        $ret .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('tag', rawurlencode(App::frontend()->context()->meta->meta_id)) . '">' . App::frontend()->context()->meta->meta_id . '</a>';
                        $ret .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case 'search':
                    // Search
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if ($page == 0) {
                        $ret .= $separator . __('Search:') . ' ' . App::frontend()->search;
                    } else {
                        $ret .= $separator . '<a href="' . $blogUrl . (str_contains($blogUrl, '?') ? '' : '?') . 'q=' . rawurlencode((string) App::frontend()->search) . '">' . __('Search:') . ' ' . App::frontend()->search . '</a>';
                        $ret .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case '404':
                    // 404
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $ret .= $separator . __('404');

                    break;

                default:
                    $ret = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    # --BEHAVIOR-- publicBreadcrumb -- string, string
                    # Should specific breadcrumb if any, will be added after home page url
                    $special = App::behavior()->callBehavior('publicBreadcrumb', App::url()->getType(), $separator);
                    if ($special) {
                        $ret .= $separator . $special;
                    }

                    break;
            }
        }

        # Encapsulate breadcrumb in <p>…</p>
        if (!My::settings()->breadcrumb_alone) {
            $ret = '<p id="breadcrumb">' . $ret . '</p>';
        }

        return $ret;
    }
}
