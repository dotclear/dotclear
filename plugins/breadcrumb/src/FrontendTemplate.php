<?php
/**
 * @brief breadcrumb, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\breadcrumb;

use ArrayObject;
use Dotclear\Core\Core;
use Dotclear\Helper\Date;
use Dotclear\Helper\L10n;

class FrontendTemplate
{
    /**
     * tpl:Breadcrumb [attributes] : Displays the blogroll (tpl value)
     *
     * attributes:
     *
     *      - separator   string      Breadcrumb element separator
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function breadcrumb(ArrayObject $attr): string
    {
        $separator = $attr['separator'] ?? '';

        return '<?php echo ' . self::class . '::displayBreadcrumb(' . "'" . addslashes($separator) . "'" . '); ?>';
    }

    /**
     * Return the breadcrumb
     *
     * @param      string  $separator  The separator
     *
     * @return     string
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
        $page = (int) Core::frontend()->getPageNumber();

        // Test if complete breadcrumb will be provided
        # --BEHAVIOR-- publicBreadcrumbExtended -- string
        if (Core::behavior()->callBehavior('publicBreadcrumbExtended', Core::url()->type)) {
            # --BEHAVIOR-- publicBreadcrumb -- string, string
            $special = Core::behavior()->callBehavior('publicBreadcrumb', Core::url()->type, $separator);

            $ret = $special ?: '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
        } else {
            switch (Core::url()->type) {
                case 'static':
                    // Static home
                    $ret = '<span id="bc-home">' . __('Home') . '</span>';

                    break;

                case 'default':
                    if (Core::blog()->settings->system->static_home) {
                        // Static home and on (1st) blog page
                        $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                        $ret .= $separator . __('Blog');
                    } else {
                        // Home (first page only)
                        $ret = '<span id="bc-home">' . __('Home') . '</span>';
                        if (Core::frontend()->ctx->cur_lang) {
                            $langs = L10n::getISOCodes();
                            $ret .= $separator . ($langs[Core::frontend()->ctx->cur_lang] ?? Core::frontend()->ctx->cur_lang);
                        }
                    }

                    break;

                case 'default-page':
                    // Home or blog page`(page 2 to n)
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    if (Core::blog()->settings->system->static_home) {
                        $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('posts') . '">' . __('Blog') . '</a>';
                    } else {
                        if (Core::frontend()->ctx->cur_lang) {
                            $langs = L10n::getISOCodes();
                            $ret .= $separator . ($langs[Core::frontend()->ctx->cur_lang] ?? Core::frontend()->ctx->cur_lang);
                        }
                    }
                    $ret .= $separator . sprintf(__('page %d'), $page);

                    break;

                case 'category':
                    // Category
                    $ret        = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    $categories = Core::blog()->getCategoryParents((int) Core::frontend()->ctx->categories->cat_id);
                    while ($categories->fetch()) {
                        $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                    }
                    if ($page == 0) {
                        $ret .= $separator . Core::frontend()->ctx->categories->cat_title;
                    } else {
                        $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('category', Core::frontend()->ctx->categories->cat_url) . '">' . Core::frontend()->ctx->categories->cat_title . '</a>';
                        $ret .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case 'post':
                    // Post
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    if (Core::frontend()->ctx->posts->cat_id) {
                        // Parents cats of post's cat
                        $categories = Core::blog()->getCategoryParents((int) Core::frontend()->ctx->posts->cat_id);
                        while ($categories->fetch()) {
                            $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                        }
                        // Post's cat
                        $categories = Core::blog()->getCategory((int) Core::frontend()->ctx->posts->cat_id);
                        $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('category', $categories->cat_url) . '">' . $categories->cat_title . '</a>';
                    }
                    $ret .= $separator . Core::frontend()->ctx->posts->post_title;

                    break;

                case 'lang':
                    // Lang
                    $ret   = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    $langs = L10n::getISOCodes();
                    $ret .= $separator . ($langs[Core::frontend()->ctx->cur_lang] ?? Core::frontend()->ctx->cur_lang);

                    break;

                case 'archive':
                    // Archives
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    if (!Core::frontend()->ctx->archives) {
                        // Global archives
                        $ret .= $separator . __('Archives');
                    } else {
                        // Month archive
                        $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('archive') . '">' . __('Archives') . '</a>';
                        $ret .= $separator . Date::dt2str('%B %Y', Core::frontend()->ctx->archives->dt);
                    }

                    break;

                case 'pages':
                    // Page
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    $ret .= $separator . Core::frontend()->ctx->posts->post_title;

                    break;

                case 'tags':
                    // All tags
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    $ret .= $separator . __('All tags');

                    break;

                case 'tag':
                    // Tag
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('tags') . '">' . __('All tags') . '</a>';
                    if ($page == 0) {
                        $ret .= $separator . Core::frontend()->ctx->meta->meta_id;
                    } else {
                        $ret .= $separator . '<a href="' . Core::blog()->url . Core::url()->getURLFor('tag', rawurlencode(Core::frontend()->ctx->meta->meta_id)) . '">' . Core::frontend()->ctx->meta->meta_id . '</a>';
                        $ret .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case 'search':
                    // Search
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    if ($page == 0) {
                        $ret .= $separator . __('Search:') . ' ' . Core::frontend()->search;
                    } else {
                        $ret .= $separator . '<a href="' . Core::blog()->url . '?q=' . rawurlencode(Core::frontend()->search) . '">' . __('Search:') . ' ' . Core::frontend()->search . '</a>';
                        $ret .= $separator . sprintf(__('page %d'), $page);
                    }

                    break;

                case '404':
                    // 404
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    $ret .= $separator . __('404');

                    break;

                default:
                    $ret = '<a id="bc-home" href="' . Core::blog()->url . '">' . __('Home') . '</a>';
                    # --BEHAVIOR-- publicBreadcrumb -- string, string
                    # Should specific breadcrumb if any, will be added after home page url
                    $special = Core::behavior()->callBehavior('publicBreadcrumb', Core::url()->type, $separator);
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
