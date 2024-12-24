<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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

        return '<?= ' . self::class . '::displayBreadcrumb(' . "'" . addslashes((string) $separator) . "'" . ') ?>';
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
        $breadcrumb = '';
        $format     = My::settings()->breadcrumb_alone ? '%s' : '<p id="breadcrumb">%s</p>';

        # Check if breadcrumb enabled for the current blog
        if (!My::settings()->breadcrumb_enabled) {
            return $breadcrumb;
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

            $breadcrumb = $special ?: '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
        } else {
            switch (App::url()->getType()) {
                case 'static':
                    // Static home
                    $breadcrumb = '<span id="bc-home">' . __('Home') . '</span>';

                    break;

                case 'default':
                    if (App::blog()->settings()->system->static_home) {
                        // Static home and on (1st) blog page
                        $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                        $breadcrumb .= $separator . __('Blog');
                    } else {
                        // Home (first page only)
                        $breadcrumb = '<span id="bc-home">' . __('Home') . '</span>';
                        if (App::frontend()->context()->cur_lang) {
                            $langs = L10n::getISOCodes();
                            $breadcrumb .= $separator . ($langs[App::frontend()->context()->cur_lang] ?? App::frontend()->context()->cur_lang);
                        }
                    }

                    break;

                case 'default-page':
                    // Home or blog page`(page 2 to n)
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (App::blog()->settings()->system->static_home) {
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('posts') . '">' . __('Blog') . '</a>';
                    } else {
                        if (App::frontend()->context()->cur_lang) {
                            $langs = L10n::getISOCodes();
                            $breadcrumb .= $separator . ($langs[App::frontend()->context()->cur_lang] ?? App::frontend()->context()->cur_lang);
                        }
                    }
                    $breadcrumb .= $separator . sprintf(__('page %d'), $page);

                    break;

                case 'category':
                    // Category
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $categories = App::blog()->getCategoryParents((int) App::frontend()->context()->categories->cat_id);
                    while ($categories->fetch()) {
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                    }
                    if ($page == 0) {
                        $breadcrumb .= $separator . App::frontend()->context()->categories->cat_title;
                    } else {
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', App::frontend()->context()->categories->cat_url) . '">' . App::frontend()->context()->categories->cat_title . '</a>';
                        $breadcrumb .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case 'post':
                    // Post
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (App::frontend()->context()->posts->cat_id) {
                        // Parents cats of post's cat
                        $categories = App::blog()->getCategoryParents((int) App::frontend()->context()->posts->cat_id);
                        while ($categories->fetch()) {
                            $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                        }
                        // Post's cat
                        $categories = App::blog()->getCategory((int) App::frontend()->context()->posts->cat_id);
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                    }
                    $breadcrumb .= $separator . App::frontend()->context()->posts->post_title;

                    break;

                case 'lang':
                    // Lang
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $langs      = L10n::getISOCodes();
                    $breadcrumb .= $separator . ($langs[App::frontend()->context()->cur_lang] ?? App::frontend()->context()->cur_lang);

                    break;

                case 'archive':
                    // Archives
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (!App::frontend()->context()->archives) {
                        // Global archives
                        $breadcrumb .= $separator . __('Archives');
                    } else {
                        // Month archive
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('archive') . '">' . __('Archives') . '</a>';
                        $breadcrumb .= $separator . Date::dt2str('%B %Y', App::frontend()->context()->archives->dt);
                    }

                    break;

                case 'pages':
                    // Page
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $breadcrumb .= $separator . App::frontend()->context()->posts->post_title;

                    break;

                case 'tags':
                    // All tags
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $breadcrumb .= $separator . __('All tags');

                    break;

                case 'tag':
                    // Tag
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('tags') . '">' . __('All tags') . '</a>';
                    if ($page == 0) {
                        $breadcrumb .= $separator . App::frontend()->context()->meta->meta_id;
                    } else {
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('tag', rawurlencode(App::frontend()->context()->meta->meta_id)) . '">' . App::frontend()->context()->meta->meta_id . '</a>';
                        $breadcrumb .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case 'search':
                    // Search
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if ($page == 0) {
                        $breadcrumb .= $separator . __('Search:') . ' ' . App::frontend()->search;
                    } else {
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . (str_contains($blogUrl, '?') ? '' : '?') . 'q=' . rawurlencode((string) App::frontend()->search) . '">' . __('Search:') . ' ' . App::frontend()->search . '</a>';
                        $breadcrumb .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case '404':
                    // 404
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $breadcrumb .= $separator . __('404');

                    break;

                default:
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    # --BEHAVIOR-- publicBreadcrumb -- string, string
                    # Should specific breadcrumb if any, will be added after home page url
                    $special = App::behavior()->callBehavior('publicBreadcrumb', App::url()->getType(), $separator);
                    if ($special) {
                        $breadcrumb .= $separator . $special;
                    }

                    break;
            }
        }

        return sprintf($format, $breadcrumb);
    }
}
