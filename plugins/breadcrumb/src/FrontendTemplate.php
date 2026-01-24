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
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;

/**
 * @brief   The module frontend template.
 * @ingroup breadcrumb
 */
class FrontendTemplate
{
    /**
     * tpl:Breadcrumb [attributes] : Displays the breadcrumb (tpl value).
     *
     * attributes:
     *
     *      - separator   string      Breadcrumb element separator
     *
     * @param   ArrayObject<string, mixed>     $attr   The attributes
     */
    public static function breadcrumb(ArrayObject $attr): string
    {
        $separator = is_string($separator = $attr['separator'] ?? '') ? $separator : '';

        return '<?= ' . self::class . '::displayBreadcrumb(' . "'" . addslashes($separator) . "'" . ') ?>';
    }

    /**
     * Return the breadcrumb.
     *
     * @param   string  $separator  The separator
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
        if (App::behavior()->callBehavior('publicBreadcrumbExtended', App::url()->getType()) !== '') {
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
                            $langs = App::lang()->getISOcodes();
                            $lang  = is_string($lang = App::frontend()->context()->cur_lang) ? $lang : '';
                            if ($lang !== '' && isset($langs[$lang])) {
                                $lang = $langs[$lang];
                            }
                            $breadcrumb .= $separator . $lang;
                        }
                    }

                    break;

                case 'default-page':
                    // Home or blog page`(page 2 to n)
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (App::blog()->settings()->system->static_home) {
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('posts') . '">' . __('Blog') . '</a>';
                    } elseif (App::frontend()->context()->cur_lang) {
                        $langs = App::lang()->getISOcodes();
                        $lang  = is_string($lang = App::frontend()->context()->cur_lang) ? $lang : '';
                        if ($lang !== '' && isset($langs[$lang])) {
                            $lang = $langs[$lang];
                        }
                        $breadcrumb .= $separator . $lang;
                    }
                    $breadcrumb .= $separator . sprintf(__('page %d'), $page);

                    break;

                case 'category':
                    // Category
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $categories = App::frontend()->context()->categories instanceof MetaRecord ? App::frontend()->context()->categories : null;
                    if ($categories instanceof MetaRecord) {
                        $cat_id             = is_numeric($cat_id = $categories->cat_id) ? (int) $cat_id : 0;
                        $categories_parents = App::blog()->getCategoryParents($cat_id);
                        while ($categories_parents->fetch()) {
                            $cat_url   = is_string($cat_url = $categories_parents->cat_url) ? $cat_url : '';
                            $cat_title = is_string($cat_title = $categories_parents->cat_title) ? $cat_title : '';
                            $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $cat_url) . '">' . $cat_title . '</a>';
                        }

                        $cat_title = is_string($cat_title = $categories->cat_title) ? $cat_title : '';
                        if ($page === 0) {
                            $breadcrumb .= $separator . $cat_title;
                        } else {
                            $cat_url = is_string($cat_url = $categories->cat_url) ? $cat_url : '';
                            $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $cat_url) . '">' . $cat_title . '</a>';
                            $breadcrumb .= $separator . sprintf(__('page %d'), $page);
                        }
                    }

                    break;

                case 'post':
                    // Post
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $posts      = App::frontend()->context()->posts instanceof MetaRecord ? App::frontend()->context()->posts : null;
                    if ($posts instanceof MetaRecord) {
                        $cat_id = is_numeric($cat_id = $posts->cat_id) ? (int) $cat_id : 0;
                        if ($cat_id !== 0) {
                            // Parents cats of post's cat
                            $categories = App::blog()->getCategoryParents($cat_id);
                            while ($categories->fetch()) {
                                $cat_title = is_string($cat_title = $categories->cat_title) ? $cat_title : '';
                                $cat_url   = is_string($cat_url = $categories->cat_url) ? $cat_url : '';
                                $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $cat_url) . '">' . $cat_title . '</a>';
                            }
                            // Post's cat
                            $categories = App::blog()->getCategory($cat_id);
                            $cat_title  = is_string($cat_title = $categories->cat_title) ? $cat_title : '';
                            $cat_url    = is_string($cat_url = $categories->cat_url) ? $cat_url : '';
                            $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('category', $cat_url) . '">' . $cat_title . '</a>';
                        }
                        $post_title = is_string($post_title = $posts->post_title) ? $post_title : '';
                        $breadcrumb .= $separator . $post_title;
                    }

                    break;

                case 'lang':
                    // Lang
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $langs      = App::lang()->getISOcodes();
                    $lang       = is_string($lang = App::frontend()->context()->cur_lang) ? $lang : '';
                    if ($lang !== '' && isset($langs[$lang])) {
                        $lang = $langs[$lang];
                    }
                    $breadcrumb .= $separator . $lang;

                    break;

                case 'archive':
                    // Archives
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if (App::frontend()->context()->archives instanceof MetaRecord) {
                        // Month archive
                        $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('archive') . '">' . __('Archives') . '</a>';

                        $dt = is_string($dt = App::frontend()->context()->archives->dt) ? $dt : '';
                        $breadcrumb .= $separator . Date::dt2str('%B %Y', $dt);
                    } else {
                        // Global archives
                        $breadcrumb .= $separator . __('Archives');
                    }

                    break;

                case 'pages':
                    // Page
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    $posts      = App::frontend()->context()->posts instanceof MetaRecord ? App::frontend()->context()->posts : null;
                    if ($posts instanceof MetaRecord) {
                        $post_title = is_string($post_title = $posts->post_title) ? $post_title : '';
                        $breadcrumb .= $separator . $post_title;
                    }

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
                    $meta = App::frontend()->context()->meta instanceof MetaRecord ? App::frontend()->context()->meta : null;
                    if ($meta instanceof MetaRecord) {
                        $meta_id = is_string($meta_id = $meta->meta_id) ? $meta_id : '';
                        if ($page === 0) {
                            $breadcrumb .= $separator . $meta_id;
                        } else {
                            $breadcrumb .= $separator . '<a href="' . $blogUrl . App::url()->getURLFor('tag', rawurlencode($meta_id)) . '">' . $meta_id . '</a>';
                            $breadcrumb .= $separator . sprintf(__('page %d'), $page);
                        }
                    }

                    break;

                case 'search':
                    // Search
                    $breadcrumb = '<a id="bc-home" href="' . $blogUrl . '">' . __('Home') . '</a>';
                    if ($page === 0) {
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
                    if ($special !== '') {
                        $breadcrumb .= $separator . $special;
                    }

                    break;
            }
        }

        return sprintf($format, $breadcrumb);
    }
}
