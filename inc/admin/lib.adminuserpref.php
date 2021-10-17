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
 * @brief Admin user preference library
 *
 * Dotclear utility class that provides reuseable user preference
 * across all admin page with lists and filters
 */
class adminUserPref
{
    /** @var dcCore core instance */
    public static $core;

    /** @var arrayObject columns preferences */
    protected static $cols = null;

    /** @var arrayObject sorts filters preferences*/
    protected static $sorts = null;

    public static function getDefaultColumns()
    {
        return ['posts' => [__('Posts'), [
            'date'       => [true, __('Date')],
            'category'   => [true, __('Category')],
            'author'     => [true, __('Author')],
            'comments'   => [true, __('Comments')],
            'trackbacks' => [true, __('Trackbacks')]
        ]]];
    }

    public static function getUserColumns($type = null, $columns = null)
    {
        # Get default colums (admin lists)
        $cols = self::getDefaultColumns();
        $cols = new arrayObject($cols);

        # --BEHAVIOR-- adminColumnsLists
        self::$core->callBehavior('adminColumnsLists', self::$core, $cols);

        # Load user settings
        $cols_user = @self::$core->auth->user_prefs->interface->cols;
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

    public static function getDefaultFilters()
    {
        $users = [null, null, null, null, null];
        if (self::$core->auth->isSuperAdmin()) {
            $users = [
                __('Users'),
                dcAdminCombos::getUsersSortbyCombo(),
                'user_id',
                'asc',
                [__('users per page'), 30]
            ] ;
        }

        return [
            'posts' => [
                __('Posts'),
                dcAdminCombos::getPostsSortbyCombo(),
                'post_dt',
                'desc',
                [__('entries per page'), 30]
            ],
            'comments' => [
                __('Comments'),
                dcAdminCombos::getCommentsSortbyCombo(),
                'comment_dt',
                'desc',
                [__('comments per page'), 30]
            ],
            'blogs' => [
                __('Blogs'),
                dcAdminCombos::getBlogsSortbyCombo(),
                'blog_upddt',
                'desc',
                [__('blogs per page'), 30]
            ],
            'users' => $users,
            'media' => [
                __('Media manager'),
                [
                    __('Name') => 'name',
                    __('Date') => 'date',
                    __('Size') => 'size'
                ],
                'name',
                'asc',
                [__('media per page'), 30]
            ],
            'search' => [
                __('Search'),
                null,
                null,
                null,
                [__('results per page'), 20]
            ]
        ];
    }

    /**
     * Get sorts filters users preference for a given type
     *
     * @param       string      $type   The filter list type
     * @return      mixed               Filters or typed filter or field value(s)
     */
    public static function getUserFilters($type = null, $option = null)
    {
        if (self::$sorts === null) {
            $sorts = self::getDefaultFilters();
            $sorts = new arrayObject($sorts);

            # --BEHAVIOR-- adminFiltersLists
            self::$core->callBehavior('adminFiltersLists', self::$core, $sorts);

            if (self::$core->auth->user_prefs->interface === null) {
                self::$core->auth->user_prefs->addWorkspace('interface');
            }
            $sorts_user = @self::$core->auth->user_prefs->interface->sorts;
            if (is_array($sorts_user)) {
                foreach ($sorts_user as $stype => $sdata) {
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
                return abs((integer) self::$sorts[$type][4][1]);
            }
        }

        return null;
    }
}
/*
 * Store current dcCore instance
 */
adminUserPref::$core = $GLOBALS['core'];
