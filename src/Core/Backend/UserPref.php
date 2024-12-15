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

/**
 * Admin user preference library
 *
 * Dotclear utility class that provides reuseable user preference
 * across all admin page with lists and filters
 */
class UserPref
{
    /**
     * Sorts filters preferences
     *
     * @var ArrayObject<string, mixed>
     */
    protected static ?ArrayObject $sorts = null;

    /**
     * Gets the default columns.
     *
     * @return     ArrayObject<string, mixed>  The default columns.
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
                ],
            ];
            $cols['blogs'] = [
                __('Blogs'),
                [
                    'name'   => [true, __('Blog name')],
                    'url'    => [true,  __('URL')],
                    'posts'  => [true,  __('Entries (all types)')],
                    'upddt'  => [true,  __('Last update')],
                    'status' => [true,  __('Status')],
                ],
            ];
        }

        $cols = new ArrayObject($cols);

        # --BEHAVIOR-- adminColumnsLists -- ArrayObject
        App::behavior()->callBehavior('adminColumnsListsDefault', $cols);

        return $cols;
    }

    /**
     * Gets the user columns.
     *
     * @param      null|string                                              $type     The type
     * @param      null|array<string, mixed>|ArrayObject<string, mixed>     $columns  The columns
     *
     * @return     ArrayObject<string, mixed>  The user columns.
     */
    public static function getUserColumns(?string $type = null, $columns = null): ArrayObject
    {
        # Get default colums (admin lists)
        $cols = self::getDefaultColumns();

        # --BEHAVIOR-- adminColumnsLists -- ArrayObject
        App::behavior()->callBehavior('adminColumnsListsV2', $cols);

        // Load user settings
        $cols_user = @App::auth()->prefs()->interface->cols;
        if (is_array($cols_user) || $cols_user instanceof ArrayObject) {
            /*
             * $ct = type (blogs, users, posts, …)
             * $cv = columns for this type
            */
            foreach ($cols_user as $ct => $cv) {
                // Sort corresponding $cols columns
                $order = array_keys($cv);
                if (isset($cols[$ct][1])) {
                    uksort($cols[$ct][1], fn ($key1, $key2) => array_search($key1, $order) <=> array_search($key2, $order));
                }
                if (!empty($type) && !empty($columns) && $ct == $type) {
                    // Use ArrayObject in all cases
                    $columns = $columns instanceof ArrayObject ? $columns : new ArrayObject($columns);
                    // Sort also corresponding $columns columns
                    $columns->uksort(fn ($key1, $key2) => array_search($key1, $order) <=> array_search($key2, $order));
                }
                /*
                 * $cn = column id
                 * $cd = column flag (false/true)
                 */
                foreach ($cv as $cn => $cd) {
                    if (isset($cols[$ct][1][$cn])) {
                        $cols[$ct][1][$cn][0] = $cd;

                        // remove unselected columns if type is given
                        if (!$cd && !empty($type) && !empty($columns) && $ct == $type && isset($columns[$cn])) {
                            unset($columns[$cn]);
                        }
                    }
                }
            }
        }
        if ($columns !== null) {
            return $columns instanceof ArrayObject ? $columns : new ArrayObject($columns);
        }
        if ($type !== null) {
            return new ArrayObject($cols[$type] ?? []); // @phpstan-ignore-line
        }

        return $cols;
    }

    /**
     * Gets the default filters.
     *
     * @return     array<string, mixed>  The default filters.
     */
    public static function getDefaultFilters(): array
    {
        // Helper for nb of element per page, use setting if set and > 0, else use default value
        $nb_per_page = fn ($setting, $default = 30) => $setting ? ((int) $setting > 0 ? $setting : $default) : $default;

        $users = [null, null, null, null, null];
        if (App::auth()->isSuperAdmin()) {
            $users = [
                __('Users'),
                Combos::getUsersSortbyCombo(),
                'user_id',
                'asc',
                [__('users per page'), $nb_per_page(App::auth()->prefs()->interface->nb_users_per_page)],
            ] ;
        }

        return [
            'posts' => [
                __('Posts'),
                Combos::getPostsSortbyCombo(),
                'post_dt',
                'desc',
                [__('entries per page'), $nb_per_page(App::auth()->prefs()->interface->nb_posts_per_page)],
            ],
            'comments' => [
                __('Comments'),
                Combos::getCommentsSortbyCombo(),
                'comment_dt',
                'desc',
                [__('comments per page'), $nb_per_page(App::auth()->prefs()->interface->nb_comments_per_page)],
            ],
            'blogs' => [
                __('Blogs'),
                Combos::getBlogsSortbyCombo(),
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
     * @return     mixed       Filters or typed filter or field value(s)
     */
    public static function getUserFilters(?string $type = null, ?string $option = null)
    {
        if (self::$sorts === null) {
            $sorts = self::getDefaultFilters();
            $sorts = new ArrayObject($sorts);

            # --BEHAVIOR-- adminFiltersLists -- ArrayObject
            App::behavior()->callBehavior('adminFiltersListsV2', $sorts);

            $sorts_user = App::auth()->prefs()->interface->sorts;
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
                        $sorts[$stype][4][1] = abs((int) $sdata[2]);
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
