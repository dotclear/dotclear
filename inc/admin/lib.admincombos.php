<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}
/**
 * @brief Admin combo library
 *
 * Dotclear utility class that provides reuseable combos across all admin
 * form::combo -compatible format
 */
class dcAdminCombos
{
    /** @var dcCore dcCore instance */
    public static $core;

    /**
     * Returns an hierarchical categories combo from a category record
     *
     * @param      record  $categories     The categories
     * @param      bool    $include_empty  Includes empty categories
     * @param      bool    $use_url        Use url or ID
     *
     * @return     array   The categories combo.
     */
    public static function getCategoriesCombo($categories, $include_empty = true, $use_url = false)
    {
        $categories_combo = [];
        if ($include_empty) {
            $categories_combo = [new formSelectOption(__('(No cat)'), '')];
        }
        while ($categories->fetch()) {
            $categories_combo[] = new formSelectOption(
                str_repeat('&nbsp;', ($categories->level - 1) * 4) .
                html::escapeHTML($categories->cat_title) . ' (' . $categories->nb_post . ')',
                ($use_url ? $categories->cat_url : $categories->cat_id),
                ($categories->level - 1 ? 'sub-option' . ($categories->level - 1) : '')
            );
        }

        return $categories_combo;
    }

    /**
     * Returns available post status combo.
     *
     * @return     array  The post statuses combo.
     */
    public static function getPostStatusesCombo()
    {
        $status_combo = [];
        foreach (self::$core->blog->getAllPostStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }

        return $status_combo;
    }

    /**
     * Returns an users combo from a users record.
     *
     * @param      record  $users  The users
     *
     * @return     array   The users combo.
     */
    public static function getUsersCombo($users)
    {
        $users_combo = [];
        while ($users->fetch()) {
            $user_cn = dcUtils::getUserCN($users->user_id, $users->user_name,
                $users->user_firstname, $users->user_displayname);

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
     * @param      record  $dates  The dates
     *
     * @return     array   The dates combo.
     */
    public static function getDatesCombo($dates)
    {
        $dt_m_combo = [];
        while ($dates->fetch()) {
            $dt_m_combo[dt::str('%B %Y', $dates->ts())] = $dates->year() . $dates->month();
        }

        return $dt_m_combo;
    }

    /**
     * Gets the langs combo.
     *
     * @param      record  $langs           The langs
     * @param      bool    $with_available  If false, only list items from
     * record if true, also list available languages
     *
     * @return     array   The langs combo.
     */
    public static function getLangsCombo($langs, $with_available = false)
    {
        $all_langs = l10n::getISOcodes(false, true);
        if ($with_available) {
            $langs_combo = ['' => '', __('Most used') => [], __('Available') => l10n::getISOcodes(true, true)];
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
    public static function getAdminLangsCombo()
    {
        $lang_combo = [];
        $langs      = l10n::getISOcodes(true, true);
        foreach ($langs as $k => $v) {
            $lang_avail   = $v == 'en' || is_dir(DC_L10N_ROOT . '/' . $v);
            $lang_combo[] = new formSelectOption($k, $v, $lang_avail ? 'avail10n' : '');
        }

        return $lang_combo;
    }

    /**
     * Returns a combo containing all available editors in admin.
     *
     * @return     array  The editors combo.
     */
    public static function getEditorsCombo()
    {
        $editors_combo = [];

        foreach (self::$core->getEditors() as $v) {
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
    public static function getFormatersCombo($editor_id = '')
    {
        $formaters_combo = [];

        if (!empty($editor_id)) {
            foreach (self::$core->getFormaters($editor_id) as $formater) {
                $formaters_combo[$formater] = $formater;
            }
        } else {
            foreach (self::$core->getFormaters() as $editor => $formaters) {
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
    public static function getBlogStatusesCombo()
    {
        $status_combo = [];
        foreach (self::$core->getAllBlogStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }

        return $status_combo;
    }

    /**
     * Gets the comment statuses combo.
     *
     * @return     array  The comment statuses combo.
     */
    public static function getCommentStatusesCombo()
    {
        $status_combo = [];
        foreach (self::$core->blog->getAllCommentStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }

        return $status_combo;
    }
}
/*
 * Store current dcCore instance
 */
dcAdminCombos::$core = $GLOBALS['core'];
