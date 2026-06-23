<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;

/**
 * Admin user preference library
 *
 * Dotclear utility class that provides reuseable user preference
 * across all admin page with lists and filters
 *
 * @phpstan-type TUserPrefFilterProperties array{
 *             0: ?string,
 *             1: null|array<string, string>|array<OptGroup|Option>,
 *             2: ?string,
 *             3: ?string,
 *             4: ?array{0: string, 1: int}
 * }
 *
 * @phpstan-type TUserPrefFilters array<array-key, TUserPrefFilterProperties>
 */
class UserPref
{
    /**
     * Sorts filters preferences
     *
     * @var     ?TUserPrefFilters      $sorts
     */
    protected static ?array $sorts = null;

    /**
     * Gets the default columns.
     *
     * @return     ArrayObject<string, array{string, array<string, array{bool, string}>}>
     */
    public static function getDefaultColumns(): ArrayObject
    {
        $cols = [
            'posts' => [
                __('Posts'),
                [
                    'date'       => [true, __('Date')],
                    'category'   => [true, __('Category')],
                    'author'     => [true, __('Author')],
                    'comments'   => [true, __('Comments')],
                    'trackbacks' => [true, __('Trackbacks')],
                ],
            ],
            'comments' => [
                __('Comments'),
                [
                    'date'        => [true, __('Date')],
                    'author'      => [true, __('Author')],
                    'status'      => [true, __('Status')],
                    'ip'          => [true, __('IP')],
                    'spam_filter' => [true, __('Spam filter')],
                    'entry'       => [true, __('Entry')],
                ],
            ],
        ];

        if (App::auth()->isSuperAdmin()) {
            $cols['users'] = [
                __('Users'),
                [
                    'first_name'   => [true, __('First Name')],
                    'last_name'    => [true, __('Last Name')],
                    'display_name' => [true, __('Display name')],
                    'entries'      => [true, __('Entries (all types)')],
                    'status'       => [true, __('Status')],
                    'user_creadt'  => [false, __('Creation date')],
                    'user_upddt'   => [false, __('Update date')],
                ],
            ];
            $cols['blogs'] = [
                __('Blogs'),
                [
                    'name'   => [true, __('Blog name')],
                    'url'    => [true, __('URL')],
                    'posts'  => [true, __('Entries (all types)')],
                    'upddt'  => [true, __('Last update')],
                    'status' => [true, __('Status')],
                ],
            ];
        }

        $cols = new ArrayObject($cols);

        # --BEHAVIOR-- adminColumnsLists -- ArrayObject
        App::behavior()->callBehavior('adminColumnsListsDefault', $cols);

        // @phpstan-ignore return.type (cannot be specific as behavior may change the content of $cols)
        return $cols;
    }

    /**
     * Gets the user columns.
     *
     * @param      null|string                                              $type     The type
     * @param      null|array<string, mixed>|ArrayObject<string, mixed>     $columns  The columns
     *
     * @return     ($type is null
     *              ? ArrayObject<string, array{string, array<string, array{bool, string}>}>
     *              : ArrayObject<int, array<string, array{bool, string}>|string>)
     */
    public static function getUserColumns(?string $type = null, $columns = null): ArrayObject
    {
        # Get default colums (admin lists)
        $cols = self::getDefaultColumns();

        # --BEHAVIOR-- adminColumnsLists -- ArrayObject
        App::behavior()->callBehavior('adminColumnsListsV2', $cols);

        // Load user settings
        $cols_user = @App::auth()->prefs()->interface->cols;
        if (is_array($cols_user)) {
            /*
             * $ct = type (blogs, users, posts, …)
             * $cv = columns for this type
            */
            foreach ($cols_user as $ct => $cv) {
                if (is_array($cv)) {
                    // Sort corresponding $cols columns
                    $order = array_keys($cv);
                    if (isset($cols[$ct][1])) {
                        uksort($cols[$ct][1], fn ($key1, $key2): int => array_search($key1, $order) <=> array_search($key2, $order));
                    }

                    if ($type !== null && $type !== '' && !empty($columns) && $ct == $type) {
                        // Use ArrayObject in all cases
                        $columns = $columns instanceof ArrayObject ? $columns : new ArrayObject($columns);
                        // Sort also corresponding $columns columns
                        $columns->uksort(fn ($key1, $key2): int => array_search($key1, $order) <=> array_search($key2, $order));
                    }

                    /*
                     * $cn = column id
                     * $cd = column flag (false/true)
                     */
                    foreach ($cv as $cn => $cd) {
                        if (isset($cols[$ct][1][$cn])) {
                            $cols[$ct][1][$cn][0] = $cd;

                            // remove unselected columns if type is given
                            if (!$cd && $type !== null && $type !== '' && !empty($columns) && $ct == $type && isset($columns[$cn])) {
                                unset($columns[$cn]);
                            }
                        }
                    }
                }
            }
        }

        if ($columns !== null) {
            // @phpstan-ignore return.type
            return $columns instanceof ArrayObject ? $columns : new ArrayObject($columns);
        }

        if ($type !== null) {
            return new ArrayObject($cols[$type] ?? []);
        }

        return $cols;
    }

    /**
     * Get all user columns
     *
     * @return array<string, array{string, array<string, array{bool, string}>}>
     */
    public static function getAllUserColumns(): array
    {
        return self::getUserColumns()->getArrayCopy();
    }

    /**
     * Gets the default filters.
     *
     * @return TUserPrefFilters    The default filters
     */
    public static function getDefaultFilters(): array
    {
        // Helper for nb of element per page, use setting if set and > 0, else use default value
        $nb_per_page = fn ($setting, int $default = 30): int => is_numeric($setting)
            ? ((int) $setting > 0 ? (int) $setting : $default)
            : $default;

        $users = [null, null, null, null, null];
        if (App::auth()->isSuperAdmin()) {
            $users = [
                __('Users'),
                App::backend()->combos()->getUsersSortbyCombo(),
                'user_id',
                'asc',
                [__('users per page'), $nb_per_page(App::auth()->prefs()->interface->nb_users_per_page)],
            ] ;
        }

        return [
            'posts' => [
                __('Posts'),
                App::backend()->combos()->getPostsSortbyCombo(),
                'post_dt',
                'desc',
                [__('posts per page'), $nb_per_page(App::auth()->prefs()->interface->nb_posts_per_page)],
            ],
            'comments' => [
                __('Comments'),
                App::backend()->combos()->getCommentsSortbyCombo(),
                'comment_dt',
                'desc',
                [__('comments per page'), $nb_per_page(App::auth()->prefs()->interface->nb_comments_per_page)],
            ],
            'blogs' => [
                __('Blogs'),
                App::backend()->combos()->getBlogsSortbyCombo(),
                'blog_upddt',
                'desc',
                [__('blogs per page'), $nb_per_page(App::auth()->prefs()->interface->nb_blogs_per_page)],
            ],
            'users' => $users,
            'media' => [
                __('Media manager'),
                [
                    __('Name')  => 'name',
                    __('Date')  => 'date',
                    __('Size')  => 'size',
                    __('Title') => 'title',
                ],
                'name',
                'asc',
                [__('media per page'), $nb_per_page(App::auth()->prefs()->interface->media_by_page)],
            ],
            'search' => [
                __('Search'),
                null,
                null,
                null,
                [__('results per page'), $nb_per_page(App::auth()->prefs()->interface->nb_searchresults_per_page, 20)],
            ],
        ];
    }

    /**
     * Get sorts filters users preference for a given type
     *
     * @param      null|string  $type    The type
     * @param      null|string  $option  The option
     *
     * @return     ($type is null ? TUserPrefFilters : ($option is null ? ?TUserPrefFilterProperties : string|int|null)) Filters or typed filter or field value
     */
    public static function getUserFilters(?string $type = null, ?string $option = null): null|array|string|int
    {
        self::initUserFilters();

        if (null === $type) {
            return self::$sorts;
        }

        if ($option === null) {
            return self::getUserFilter($type);
        }

        if (isset(self::$sorts[$type])) {
            if ($option === 'sortby' && null !== self::$sorts[$type][2]) {
                return self::$sorts[$type][2];
            }

            if ($option === 'order' && null !== self::$sorts[$type][3]) {
                return self::$sorts[$type][3];
            }

            if ($option === 'nb' && is_array(self::$sorts[$type][4])) {
                return abs((int) self::$sorts[$type][4][1]);
            }
        }

        return null;
    }

    /**
     * Get sorts filters users preference for a given type
     *
     * @param  string $type Filter type
     *
     * @return ?TUserPrefFilterProperties
     */
    public static function getUserFilter(string $type): ?array
    {
        self::initUserFilters();

        return self::$sorts[$type] ?? null;
    }

    /**
     * Get user preferences sort by for a given type
     *
     * @param  string $type Filter type
     */
    public static function getUserFilterSortBy(string $type): ?string
    {
        self::initUserFilters();

        $filter = self::getUserFilter($type);
        if ($filter !== null) {
            return $filter[2];
        }

        return null;
    }

    /**
     * Get user preferences sort order for a given type
     *
     * @param  string $type Filter type
     */
    public static function getUserFilterOrder(string $type): ?string
    {
        self::initUserFilters();

        $filter = self::getUserFilter($type);
        if ($filter !== null) {
            return $filter[3];
        }

        return null;
    }

    /**
     * Get user preferences number for a given type
     *
     * @param  string $type Filter type
     */
    public static function getUserFilterNb(string $type): ?int
    {
        self::initUserFilters();

        $filter = self::getUserFilter($type);
        if ($filter !== null) {
            return $filter[4][1] ?? null;
        }

        return null;
    }

    /**
     * Populate sorts from user preferences if not already done
     */
    protected static function initUserFilters(): void
    {
        if (self::$sorts === null) {
            $sorts_def = self::getDefaultFilters();
            $sorts_def = new ArrayObject($sorts_def);

            # --BEHAVIOR-- adminFiltersLists -- ArrayObject
            App::behavior()->callBehavior('adminFiltersListsV2', $sorts_def);

            /**
             * @var TUserPrefFilters $sorts
             */
            $sorts = $sorts_def->getArrayCopy();

            $sorts_user = App::auth()->prefs()->interface->sorts;
            if (is_array($sorts_user)) {
                foreach ($sorts_user as $stype => $sdata) {
                    if (!isset($sorts[$stype])) {
                        continue;
                    }

                    if (!is_array($sdata)) {
                        continue;
                    }

                    if (null !== $sorts[$stype][1]
                        && is_string($sdata[0])
                        && in_array($sdata[0], $sorts[$stype][1])
                    ) {
                        $sorts[$stype][2] = $sdata[0];
                    }

                    if (null !== $sorts[$stype][3]
                        && is_string($sdata[1])
                        && in_array($sdata[1], ['asc', 'desc'])
                    ) {
                        $sorts[$stype][3] = $sdata[1];
                    }

                    if (is_array($sorts[$stype][4])
                        && is_numeric($sdata[2])
                        && $sdata[2] > 0
                    ) {
                        $sorts[$stype][4][1] = abs((int) $sdata[2]);
                    }
                }
            }

            self::$sorts = $sorts;
        }
    }
}
