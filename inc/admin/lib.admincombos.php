<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}
/**
@brief Admin combo library

Dotclear utility class that provides reuseable combos across all admin

 */
class dcAdminCombos
{

    /** @var dcCore dcCore instance */
    public static $core;

    /**
    Returns an hierarchical categories combo from a category record

    @param    categories        <b>record</b>        the category record
    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getCategoriesCombo($categories, $include_empty = true, $use_url = false)
    {
        $categories_combo = array();
        if ($include_empty) {
            $categories_combo = array(new formSelectOption(__('(No cat)'), ''));
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
    Returns available post status combo

    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getPostStatusesCombo()
    {
        $status_combo = array();
        foreach (self::$core->blog->getAllPostStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }
        return $status_combo;
    }

    /**
    Returns an users combo from a users record

    @param    users        <b>record</b>        the users record
    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getUsersCombo($users)
    {
        $users_combo = array();
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
    Returns an date combo from a date record

    @param    dates        <b>record</b>        the dates record
    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getDatesCombo($dates)
    {
        $dt_m_combo = array();
        while ($dates->fetch()) {
            $dt_m_combo[dt::str('%B %Y', $dates->ts())] = $dates->year() . $dates->month();
        }
        return $dt_m_combo;
    }

    /**
    Returns an lang combo from a lang record

    @param    langs        <b>record</b>        the langs record
    @param    with_available    <b>boolean</b>    if false, only list items from record
    if true, also list available languages
    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getLangsCombo($langs, $with_available = false)
    {
        $all_langs = l10n::getISOcodes(0, 1);
        if ($with_available) {
            $langs_combo = array('' => '', __('Most used') => array(), __('Available') => l10n::getISOcodes(1, 1));
            while ($langs->fetch()) {
                if (isset($all_langs[$langs->post_lang])) {
                    $langs_combo[__('Most used')][$all_langs[$langs->post_lang]] = $langs->post_lang;
                    unset($langs_combo[__('Available')][$all_langs[$langs->post_lang]]);
                } else {
                    $langs_combo[__('Most used')][$langs->post_lang] = $langs->post_lang;
                }
            }
        } else {
            $langs_combo = array();
            while ($langs->fetch()) {
                $lang_name               = isset($all_langs[$langs->post_lang]) ? $all_langs[$langs->post_lang] : $langs->post_lang;
                $langs_combo[$lang_name] = $langs->post_lang;
            }
        }
        unset($all_langs);
        return $langs_combo;
    }

    /**
    Returns a combo containing all available and installed languages for administration pages

    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getAdminLangsCombo()
    {
        $lang_combo = array();
        $langs      = l10n::getISOcodes(1, 1);
        foreach ($langs as $k => $v) {
            $lang_avail   = $v == 'en' || is_dir(DC_L10N_ROOT . '/' . $v);
            $lang_combo[] = new formSelectOption($k, $v, $lang_avail ? 'avail10n' : '');
        }
        return $lang_combo;
    }

    /**
    Returns a combo containing all available editors in admin

    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getEditorsCombo()
    {
        $editors_combo = array();

        foreach (self::$core->getEditors() as $v) {
            $editors_combo[$v] = $v;
        }

        return $editors_combo;
    }

    /**
    Returns a combo containing all available formaters by editor in admin

    @param    editor_id    <b>string</b>    Editor id (dcLegacyEditor, dcCKEditor, ...)
    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getFormatersCombo($editor_id = '')
    {
        $formaters_combo = array();

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
    Returns a combo containing available blog statuses

    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getBlogStatusesCombo()
    {
        $status_combo = array();
        foreach (self::$core->getAllBlogStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }
        return $status_combo;
    }

    /**
    Returns a combo containing available comment statuses

    @return    <b>array</b> the combo box (form::combo -compatible format)
     */
    public static function getCommentStatusescombo()
    {
        $status_combo = array();
        foreach (self::$core->blog->getAllCommentStatus() as $k => $v) {
            $status_combo[$v] = (string) $k;
        }
        return $status_combo;
    }
}
dcAdminCombos::$core = $GLOBALS['core'];
