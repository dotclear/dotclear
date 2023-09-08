<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Admin combo library
 *
 * Dotclear utility class that provides reuseable combos across all admin
 * form::combo -compatible format
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcCore;
use dcUtils;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;

class Combos
{
    /**
     * Returns an hierarchical categories combo from a category record
     *
     * @param      MetaRecord   $categories     The categories
     * @param      bool         $include_empty  Includes empty categories
     * @param      bool         $use_url        Use url or ID
     *
     * @return     array   The categories combo.
     */
    public static function getCategoriesCombo(MetaRecord $categories, bool $include_empty = true, bool $use_url = false): array
    {
        $categories_combo = [];
        if ($include_empty) {
            $categories_combo = [new Option(__('(No cat)'), '')];
        }
        while ($categories->fetch()) {
            $option = new Option(
                str_repeat('&nbsp;', ($categories->level - 1) * 4) .
                Html::escapeHTML($categories->cat_title) . ' (' . $categories->nb_post . ')',
                ($use_url ? $categories->cat_url : (string) $categories->cat_id)
            );
            if ($categories->level - 1) {
                $option->class('sub-option' . ($categories->level - 1));
            }
            $categories_combo[] = $option;
        }

        return $categories_combo;
    }

    /**
     * Returns available post status combo.
     *
     * @return     array  The post statuses combo.
     */
    public static function getPostStatusesCombo(): array
    {
        $status_combo = [];
        foreach (dcCore::app()->blog->getAllPostStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }

        return $status_combo;
    }

    /**
     * Returns an users combo from a users record.
     *
     * @param      MetaRecord  $users  The users
     *
     * @return     array   The users combo.
     */
    public static function getUsersCombo(MetaRecord $users): array
    {
        $users_combo = [];
        while ($users->fetch()) {
            $user_cn = dcUtils::getUserCN(
                $users->user_id,
                $users->user_name,
                $users->user_firstname,
                $users->user_displayname
            );

            if ($user_cn != $users->user_id) {
                $user_cn .= ' (' . $users->user_id . ')';
            }

            $users_combo[$user_cn] = $users->user_id;
        }

        return $users_combo;
    }

    /**
     * Gets the dates combo.
     *
     * @param      MetaRecord  $dates  The dates
     *
     * @return     array   The dates combo.
     */
    public static function getDatesCombo(MetaRecord $dates): array
    {
        $dt_m_combo = [];
        while ($dates->fetch()) {
            $dt_m_combo[Date::str('%B %Y', $dates->ts())] = $dates->year() . $dates->month();
        }

        return $dt_m_combo;
    }

    /**
     * Gets the langs combo.
     *
     * @param      MetaRecord  $langs           The langs
     * @param      bool      $with_available  If false, only list items from record if true, also list available languages
     *
     * @return     array   The langs combo.
     */
    public static function getLangsCombo(MetaRecord $langs, bool $with_available = false): array
    {
        $all_langs = L10n::getISOcodes(false, true);
        if ($with_available) {
            $langs_combo = ['' => '', __('Most used') => [], __('Available') => L10n::getISOcodes(true, true)];
            while ($langs->fetch()) {
                if (isset($all_langs[$langs->post_lang])) {
                    $langs_combo[__('Most used')][$all_langs[$langs->post_lang]] = $langs->post_lang;
                    unset($langs_combo[__('Available')][$all_langs[$langs->post_lang]]);
                } else {
                    $langs_combo[__('Most used')][$langs->post_lang] = $langs->post_lang;
                }
            }
        } else {
            $langs_combo = [];
            while ($langs->fetch()) {
                $lang_name               = $all_langs[$langs->post_lang] ?? $langs->post_lang;
                $langs_combo[$lang_name] = $langs->post_lang;
            }
        }
        unset($all_langs);

        return $langs_combo;
    }

    /**
     * Returns a combo containing all available and installed languages for administration pages.
     *
     * @return     array  The admin langs combo.
     */
    public static function getAdminLangsCombo(): array
    {
        $lang_combo = [];
        $langs      = L10n::getISOcodes(true, true);
        foreach ($langs as $k => $v) {
            $lang_avail = $v == 'en' || is_dir(DC_L10N_ROOT . '/' . $v);
            $option     = new Option($k, $v);
            $option->lang($v);
            if ($lang_avail) {
                $option->class('avail10n');
            }
            $lang_combo[] = $option;
        }

        return $lang_combo;
    }

    /**
     * Returns a combo containing all available editors in admin.
     *
     * @return     array  The editors combo.
     */
    public static function getEditorsCombo(): array
    {
        $editors_combo = [];

        foreach (dcCore::app()->getEditors() as $v) {
            $editors_combo[$v] = $v;
        }

        return $editors_combo;
    }

    /**
     * Returns a combo containing all available formaters by editor in admin.
     *
     * @param      string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     *
     * @return     array   The formaters combo.
     */
    public static function getFormatersCombo(string $editor_id = ''): array
    {
        $formaters_combo = [];

        if (!empty($editor_id)) {
            foreach (dcCore::app()->getFormaters($editor_id) as $formater) {
                $formaters_combo[$formater] = $formater;
            }
        } else {
            foreach (dcCore::app()->getFormaters() as $editor => $formaters) {
                foreach ($formaters as $formater) {
                    $formaters_combo[$editor][$formater] = $formater;
                }
            }
        }

        return $formaters_combo;
    }

    /**
     * Gets the blog statuses combo.
     *
     * @return     array  The blog statuses combo.
     */
    public static function getBlogStatusesCombo(): array
    {
        $status_combo = [];
        foreach (dcCore::app()->getAllBlogStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }

        return $status_combo;
    }

    /**
     * Gets the comment statuses combo.
     *
     * @return     array  The comment statuses combo.
     */
    public static function getCommentStatusesCombo(): array
    {
        $status_combo = [];
        foreach (dcCore::app()->blog->getAllCommentStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }

        return $status_combo;
    }

    public static function getOrderCombo(): array
    {
        return [
            __('Descending') => 'desc',
            __('Ascending')  => 'asc',
        ];
    }

    public static function getPostsSortbyCombo(): array
    {
        $sortby_combo = [
            __('Date')                 => 'post_dt',
            __('Title')                => 'post_title',
            __('Category')             => 'cat_title',
            __('Author')               => 'user_id',
            __('Status')               => 'post_status',
            __('Selected')             => 'post_selected',
            __('Number of comments')   => 'nb_comment',
            __('Number of trackbacks') => 'nb_trackback',
        ];
        # --BEHAVIOR-- adminPostsSortbyCombo -- array<int,array<string,string>>
        dcCore::app()->callBehavior('adminPostsSortbyCombo', [& $sortby_combo]);

        return $sortby_combo;
    }

    public static function getCommentsSortbyCombo(): array
    {
        $sortby_combo = [
            __('Date')        => 'comment_dt',
            __('Entry title') => 'post_title',
            __('Entry date')  => 'post_dt',
            __('Author')      => 'comment_author',
            __('Status')      => 'comment_status',
            __('Spam filter') => 'comment_spam_filter',
        ];

        // IP are available only for super-admin and admin
        $show_ip = dcCore::app()->auth->check(
            dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]),
            dcCore::app()->blog->id
        );
        if ($show_ip) {
            $sortby_combo[__('IP')] = 'comment_ip';
        }

        # --BEHAVIOR-- adminCommentsSortbyCombo -- array<int,array<string,string>>
        dcCore::app()->callBehavior('adminCommentsSortbyCombo', [& $sortby_combo]);

        return $sortby_combo;
    }

    public static function getBlogsSortbyCombo(): array
    {
        $sortby_combo = [
            __('Last update') => 'blog_upddt',
            __('Blog name')   => 'UPPER(blog_name)',
            __('Blog ID')     => 'B.blog_id',
            __('Status')      => 'blog_status',
        ];
        # --BEHAVIOR-- adminBlogsSortbyCombo -- array<int,array<string,string>>
        dcCore::app()->callBehavior('adminBlogsSortbyCombo', [& $sortby_combo]);

        return $sortby_combo;
    }

    public static function getUsersSortbyCombo(): array
    {
        $sortby_combo = [];
        if (dcCore::app()->auth->isSuperAdmin()) {
            $sortby_combo = [
                __('Username')          => 'user_id',
                __('Last Name')         => 'user_name',
                __('First Name')        => 'user_firstname',
                __('Display name')      => 'user_displayname',
                __('Number of entries') => 'nb_post',
            ];
            # --BEHAVIOR-- adminUsersSortbyCombo -- array<int,array<string,string>>
            dcCore::app()->callBehavior('adminUsersSortbyCombo', [& $sortby_combo]);
        }

        return $sortby_combo;
    }
}
