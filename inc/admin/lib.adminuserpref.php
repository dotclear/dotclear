<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Admin user preference library
 *
 * Dotclear utility class that provides reuseable user preference
 * across all admin page with lists and filters
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class adminUserPref
{
    /**
     * Columns preferences
     *
     * @var arrayObject
     */
    protected static $cols = null;

    /**
     * Sorts filters preferences
     *
     * @var arrayObject
     */
    protected static $sorts = null;

    /**
     * Gets the default columns.
     *
     * @return     array  The default columns.
     */
    public static function getDefaultColumns(): array
    {
        return ['posts' => [__('Posts'), [
            'date'       => [true, __('Date')],
            'category'   => [true, __('Category')],
            'author'     => [true, __('Author')],
            'comments'   => [true, __('Comments')],
            'trackbacks' => [true, __('Trackbacks')],
        ]]];
    }

    /**
     * Gets the user columns.
     *
     * @param      null|string          $type     The type
     * @param      array|arrayObject    $columns  The columns
     *
     * @return     arrayObject  The user columns.
     */
    public static function getUserColumns(?string $type = null, $columns = null): ArrayObject
    {
        # Get default colums (admin lists)
        $cols = self::getDefaultColumns();
        $cols = new arrayObject($cols);

        # --BEHAVIOR-- adminColumnsLists
        dcCore::app()->callBehavior('adminColumnsListsV2', $cols);

        # Load user settings
        $cols_user = @dcCore::app()->auth->user_prefs->interface->cols;
        if (is_array($cols_user) || $cols_user instanceof ArrayObject) {
            foreach ($cols_user as $ct => $cv) {
                foreach ($cv as $cn => $cd) {
                    if (isset($cols[$ct][1][$cn])) {
                        $cols[$ct][1][$cn][0] = $cd;

                        # remove unselected columns if type is given
                        if (!$cd && !empty($type) && !empty($columns) && $ct == $type && isset($columns[$cn])) {
                            unset($columns[$cn]);
                        }
                    }
                }
            }
        }
        if ($columns !== null) {
            return $columns;
        }
        if ($type !== null) {
            return $cols[$type] ?? [];
        }

        return $cols;
    }

    /**
     * Gets the default filters.
     *
     * @return     array  The default filters.
     */
    public static function getDefaultFilters(): array
    {
        // Helper for nb of element per page, use setting if set and > 0, else use default value
        $nb_per_page = fn ($setting, $default = 30) => $setting ? ((int) $setting > 0 ? $setting : $default) : $default;

        $users = [null, null, null, null, null];
        if (dcCore::app()->auth->isSuperAdmin()) {
            $users = [
                __('Users'),
                dcAdminCombos::getUsersSortbyCombo(),
                'user_id',
                'asc',
                [__('users per page'), $nb_per_page(dcCore::app()->auth->user_prefs->interface->nb_users_per_page)],
            ] ;
        }

        return [
            'posts'    => [
                __('Posts'),
                dcAdminCombos::getPostsSortbyCombo(),
                'post_dt',
                'desc',
                [__('entries per page'), $nb_per_page(dcCore::app()->auth->user_prefs->interface->nb_posts_per_page)],
            ],
            'comments' => [
                __('Comments'),
                dcAdminCombos::getCommentsSortbyCombo(),
                'comment_dt',
                'desc',
                [__('comments per page'), $nb_per_page(dcCore::app()->auth->user_prefs->interface->nb_comments_per_page)],
            ],
            'blogs'    => [
                __('Blogs'),
                dcAdminCombos::getBlogsSortbyCombo(),
                'blog_upddt',
                'desc',
                [__('blogs per page'), $nb_per_page(dcCore::app()->auth->user_prefs->interface->nb_blogs_per_page)],
            ],
            'users'    => $users,
            'media'    => [
                __('Media manager'),
                [
                    __('Name') => 'name',
                    __('Date') => 'date',
                    __('Size') => 'size',
                ],
                'name',
                'asc',
                [__('media per page'), $nb_per_page(dcCore::app()->auth->user_prefs->interface->media_by_page)],
            ],
            'search'   => [
                __('Search'),
                null,
                null,
                null,
                [__('results per page'), $nb_per_page(dcCore::app()->auth->user_prefs->interface->nb_searchresults_per_page, 20)],
            ],
        ];
    }

    /**
     * Get sorts filters users preference for a given type
     *
     * @param      null|string  $type    The type
     * @param      null|string  $option  The option
     *
     * @return     mixed       Filters or typed filter or field value(s)
     */
    public static function getUserFilters(?string $type = null, ?string $option = null)
    {
        if (self::$sorts === null) {
            $sorts = self::getDefaultFilters();
            $sorts = new arrayObject($sorts);

            # --BEHAVIOR-- adminFiltersLists
            dcCore::app()->callBehavior('adminFiltersListsV2', $sorts);

            $sorts_user = dcCore::app()->auth->user_prefs->interface->sorts;
            if (is_array($sorts_user)) {
                foreach ($sorts_user as $stype => $sdata) {
                    if (!isset($sorts[$stype])) {
                        continue;
                    }
                    if (null !== $sorts[$stype][1] && in_array($sdata[0], $sorts[$stype][1])) {
                        $sorts[$stype][2] = $sdata[0];
                    }
                    if (null !== $sorts[$stype][3] && in_array($sdata[1], ['asc', 'desc'])) {
                        $sorts[$stype][3] = $sdata[1];
                    }
                    if (is_array($sorts[$stype][4]) && is_numeric($sdata[2]) && $sdata[2] > 0) {
                        $sorts[$stype][4][1] = abs($sdata[2]);
                    }
                }
            }
            self::$sorts = $sorts;
        }

        if (null === $type) {
            return self::$sorts;
        } elseif (isset(self::$sorts[$type])) {
            if (null === $option) {
                return self::$sorts[$type];
            }
            if ($option == 'sortby' && null !== self::$sorts[$type][2]) {
                return self::$sorts[$type][2];
            }
            if ($option == 'order' && null !== self::$sorts[$type][3]) {
                return self::$sorts[$type][3];
            }
            if ($option == 'nb' && is_array(self::$sorts[$type][4])) {
                return abs((int) self::$sorts[$type][4][1]);
            }
        }

        return null;
    }
}
