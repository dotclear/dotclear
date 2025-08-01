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
use Dotclear\Interface\Core\UserWorkspaceInterface;

/**
 * Favorites handling facilities
 */
class Favorites
{
    /**
     * List of favorite definitions
     *
     * @var array<string, Favorite>  $favorites
     */
    protected array $favorites = [];

    /**
     * Current favorite landing workspace
     */
    protected UserWorkspaceInterface $workspace;

    /**
     * List of user-defined favorite ids
     *
     * @var string[]   $local_favorites_ids
     */
    protected array $local_favorites_ids;

    /**
     * List of globally-defined favorite ids
     *
     * @var string[]   $global_favorites_ids
     */
    protected array $global_favorites_ids;

    /**
     * List of user preferences (made from either one of the 2 above, or not!)
     *
     * @var array<string, Favorite>    $user_favorites
     */
    protected $user_favorites = [];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->workspace = App::auth()->prefs()->dashboard;

        if ($this->workspace->prefExists('favorites')) {
            $local_favs  = $this->workspace->getLocal('favorites');
            $global_favs = $this->workspace->getGlobal('favorites');

            // Since we never know what user puts through user:preferences ...
            $this->local_favorites_ids  = is_array($local_favs) ? $local_favs : [];
            $this->global_favorites_ids = is_array($global_favs) ? $global_favs : [];
        } else {
            // No favorite defined ? Huhu, let's go for a migration
            $this->migrateFavorites();
        }
    }

    /**
     * Sets up favorites, fetch user favorites (against his permissions)
     * This method is to be called after loading plugins
     */
    public function setup(): void
    {
        $this->initDefaultFavorites();
        $this->legacyFavorites();
        # --BEHAVIOR-- adminDashboardFavoritesV2 -- Favorites
        App::behavior()->callBehavior('adminDashboardFavoritesV2', $this);
        $this->setUserPrefs();
    }

    /**
     * Retrieves a favorite (complete description) from its id.
     *
     * @param string  $id   the favorite id
     *
     * @return Favorite|false   Some of the favorite properties, false if not found (or not permitted)
     */
    public function getFavorite($id): false|Favorite
    {
        if (!isset($this->favorites[$id])) {
            return false;
        }
        $favorite = $this->favorites[$id];
        if (!is_null($favorite->permissions())) {
            if (is_bool($favorite->permissions())) {
                if (!$favorite->permissions()) {
                    return false;
                }
            } elseif (!App::auth()->check($favorite->permissions(), App::blog()->id())) {
                return false;
            }
        } elseif (!App::auth()->isSuperAdmin()) {
            return false;
        }

        return $favorite;
    }

    /**
     * getFavorites - retrieves a list of favorites.
     *
     * @param  array<string>  $ids   an array of ids, as defined in getFavorite.
     *
     * @return array<string, Favorite> array of favorites, can be empty if ids are not found (or not permitted)
     */
    public function getFavorites(array $ids): array
    {
        $favorites = [];
        foreach ($ids as $id) {
            $favorite = $this->getFavorite($id);
            if ($favorite !== false) {
                $favorites[$id] = $favorite;
            }
        }

        return $favorites;
    }

    /**
     * Get user favorites from settings.
     *
     * These are complete favorites, not ids only returned favorites are the first non-empty list from :
     * - user-defined favorites
     * - globally-defined favorites
     * - a failback list "new post" (shall never be empty)
     *
     * This method is called by ::setup()
     */
    protected function setUserPrefs(): void
    {
        $this->user_favorites = $this->getFavorites($this->local_favorites_ids);
        if ($this->user_favorites === []) {
            $this->user_favorites = $this->getFavorites($this->global_favorites_ids);
        }
        if ($this->user_favorites === []) {
            $this->user_favorites = $this->getFavorites(['new_post']);
        }

        $uri = explode('?', (string) $_SERVER['REQUEST_URI']);
        // take only last part of the URI, all plugins work like that
        $uri[0] = preg_replace('#(.*?)([^/]+)$#', '$2', $uri[0]);

        // Loop over prefs to enable active favorites
        foreach ($this->user_favorites as $favorite) {
            // duplicate request URI on each loop as it takes previous pref value ?!
            $url = $uri;
            if ($favorite->activedCallback()) {
                // Use callback if defined to match whether favorite is active or not
                $favorite->setActive($favorite->isActive((string) $url[0], $_REQUEST));
            } else {
                // Failback active detection. We test against URI name & parameters
                $favorite->setActive(true); // true until something proves it is false
                $url = explode('?', (string) $favorite->url(), 2);
                if (!preg_match('/' . preg_quote($url[0], '/') . '/', (string) $_SERVER['REQUEST_URI'])) {
                    $favorite->setActive(false); // no URI match
                }
                if (count($url) === 2) {
                    parse_str(html_entity_decode($url[1]), $result);
                    // test against each request parameter.
                    foreach ($result as $key => $value) {
                        if (!isset($_REQUEST[$key]) || $_REQUEST[$key] !== $value) {
                            $favorite->setActive(false);
                        }
                    }
                }
            }
        }
    }

    /**
     * migrateFavorites - migrate dc < 2.6 favorites to new format
     */
    protected function migrateFavorites(): void
    {
        $favorites_workspace        = App::auth()->prefs()->favorites;
        $this->local_favorites_ids  = [];
        $this->global_favorites_ids = [];
        foreach ($favorites_workspace->dumpPrefs() as $pref) {
            $favorite = @unserialize($pref['value']);
            if (is_array($favorite)) {
                if ($pref['global']) {
                    $this->global_favorites_ids[] = $favorite['name'];
                } else {
                    $this->local_favorites_ids[] = $favorite['name'];
                }
            }
        }
        $this->workspace->put('favorites', $this->global_favorites_ids, 'array', 'User favorites', true, true);
        $this->workspace->put('favorites', $this->local_favorites_ids);
        $this->user_favorites = $this->getFavorites($this->local_favorites_ids);
    }

    /**
     * legacyFavorites - handle legacy favorites using adminDashboardFavs behavior
     */
    protected function legacyFavorites(): void
    {
        $favorites = new ArrayObject();
        # --BEHAVIOR-- adminDashboardFavsV2 -- ArrayObject
        App::behavior()->callBehavior('adminDashboardFavsV2', $favorites);
        foreach ($favorites as $favorite) {
            $this->register($favorite[0], [
                'title'        => __($favorite[1]),
                'url'          => $favorite[2],
                'small-icon'   => $favorite[3],
                'large-icon'   => $favorite[4],
                'permissions'  => $favorite[5],
                'dashboard_cb' => null,
                'active_cb'    => null,
                'active'       => false,
            ]);
        }
    }

    /**
     * Returns favorites that correspond to current user
     * (may be local, global, or failback favorites)
     *
     * @return array<string, Favorite> array of favorites
     */
    public function getUserFavorites(): array
    {
        return $this->user_favorites;
    }

    /**
     * Returns user-defined or global favorites ids list
     * shall not be called outside preferences.php...
     *
     * @param boolean  $global   if true, retrieve global favs, user favs otherwise
     *
     * @return string[] array of favorites ids (only ids, not enriched)
     */
    public function getFavoriteIDs(bool $global = false): array
    {
        return $global ? $this->global_favorites_ids : $this->local_favorites_ids;
    }

    /**
     * Stores user-defined or global favorites ids list
     * shall not be called outside preferences.php...
     *
     * @param string[]          $ids     list of fav ids
     * @param boolean           $global  if true, retrieve global favs, user favs otherwise
     */
    public function setFavoriteIDs(array $ids, bool $global = false): void
    {
        $this->workspace->put('favorites', $ids, 'array', null, true, $global);
    }

    /**
     * Returns all available fav ids
     *
     * @return string[] array of favorites ids (only ids, not enriched)
     */
    public function getAvailableFavoritesIDs(): array
    {
        return array_keys($this->favorites);
    }

    /**
     * Adds favorites section title to sidebar menu
     * shall not be called outside backend Utility...
     *
     * @param   Menus   $menu   admin menu
     */
    public function appendMenuSection(Menus $menu): void
    {
        $menu->prependSection(Menus::MENU_FAVORITES, new Menu('favorites-menu', __('My favorites')));
    }

    /**
     * Adds favorites items title to sidebar menu
     * shall not be called outside backend Utility...
     *
     * @param Menus  $menu   admin menu
     */
    public function appendMenu(Menus $menu): void
    {
        foreach ($this->user_favorites as $favorite_id => $favorite) {
            $menu[Menus::MENU_FAVORITES]?->addItem(
                $favorite->title() ?? '',
                $favorite->url()   ?? '',
                $favorite->smallIcon(),
                $favorite->active(),
                true,
                $favorite_id . '-fav',
                null,
                true
            );
        }
    }

    /**
     * Adds favorites icons to index page
     * shall not be called outside Home.php...
     *
     * @param array<string, mixed>|ArrayObject<string, mixed>  $icons   dashboard icon list to enrich
     */
    public function appendDashboardIcons(array|ArrayObject $icons): void
    {
        foreach ($this->user_favorites as $favorite_id => $favorite) {
            $favorite->setDashboardTitle();
            /*
             * $icons items structure:
             * [0] = title
             * [1] = url
             * [2] = icons (usually array (light/dark))
             * [3] = additional informations (usually set by 3rd party plugins)
             */
            $icons[$favorite_id] = new ArrayObject([
                $favorite->title() ?? '',
                $favorite->url()   ?? '',
                $favorite->largeIcon(),
            ]);
            # --BEHAVIOR-- adminDashboardFavsIconV2 -- string, ArrayObject
            App::behavior()->callBehavior('adminDashboardFavsIconV2', $favorite_id, $icons[$favorite_id]);
        }
    }

    /**
     * Registers a new favorite definition
     *
     * @param string                $favorite_id   favorite id
     * @param array<string, mixed>  $favorite_data favorite information
     *
     * @return Favorites instance
     */
    public function register(string $favorite_id, array $favorite_data): Favorites
    {
        $this->favorites[$favorite_id] = new Favorite(
            $favorite_id,
            $favorite_data['title']        ?? null,
            $favorite_data['url']          ?? null,
            $favorite_data['small-icon']   ?? null,
            $favorite_data['large-icon']   ?? null,
            $favorite_data['permissions']  ?? null,
            $favorite_data['dashboard_cb'] ?? null,
            $favorite_data['active_cb']    ?? null,
            $favorite_data['active']       ?? false,
        );

        return $this;
    }

    /**
     * Registers a list of favorites definition
     *
     * @param array<string, array<string, mixed>> $data Array of favorites to register.
     *
     * @return Favorites instance
     */
    public function registerMultiple(array $data): Favorites
    {
        foreach ($data as $favorite_id => $favorite_data) {
            $this->register($favorite_id, $favorite_data);
        }

        return $this;
    }

    /**
     * exists - tells whether a fav definition exists or not
     *
     * @param string $id : the fav id to test
     *
     * @return bool true if the fav definition exists, false otherwise
     */
    public function exists(string $id): bool
    {
        return isset($this->favorites[$id]);
    }

    /**
     * Initializes the default favorites.
     */
    public function initDefaultFavorites(): void
    {
        $this->registerMultiple([
            'prefs' => [
                'title'      => __('My preferences'),
                'url'        => App::backend()->url()->get('admin.user.preferences'),
                'small-icon' => 'images/menu/user-pref.svg',
                'large-icon' => 'images/menu/user-pref.svg',
            ],
            'new_post' => [
                'title'       => __('New post'),
                'url'         => App::backend()->url()->get('admin.post'),
                'small-icon'  => ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
                'large-icon'  => ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]),
                'active_cb' => fn (string $request_uri, array $request_params): bool => App::backend()->url()::INDEX === $request_uri && isset($request_params['process']) && $request_params['process'] === 'Post' && !isset($request_params['id']),
            ],
            'posts' => [
                'title'       => __('Posts'),
                'url'         => App::backend()->url()->get('admin.posts'),
                'small-icon'  => ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                'large-icon'  => ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]),
                'dashboard_cb' => function (ArrayObject $icon): void {
                    $post_count    = (int) App::blog()->getPosts([], true)->f(0);
                    $str_entries   = __('%d post', '%d posts', $post_count);
                    $icon['title'] = sprintf($str_entries, $post_count);
                },
            ],
            'comments' => [
                'title'       => __('Comments'),
                'url'         => App::backend()->url()->get('admin.comments'),
                'small-icon'  => ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                'large-icon'  => ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]),
                'dashboard_cb' => function (ArrayObject $icon): void {
                    $comment_count = (int) App::blog()->getComments([], true)->f(0);
                    $str_comments  = __('%d comment', '%d comments', $comment_count);
                    $icon['title'] = sprintf($str_comments, $comment_count);
                },
            ],
            'search' => [
                'title'       => __('Search'),
                'url'         => App::backend()->url()->get('admin.search'),
                'small-icon'  => ['images/menu/search.svg','images/menu/search-dark.svg'],
                'large-icon'  => ['images/menu/search.svg','images/menu/search-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]),
            ],
            'categories' => [
                'title'       => __('Categories'),
                'url'         => App::backend()->url()->get('admin.categories'),
                'small-icon'  => ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                'large-icon'  => ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_CATEGORIES,
                ]),
            ],
            'media' => [
                'title'       => __('Media manager'),
                'url'         => App::backend()->url()->get('admin.media'),
                'small-icon'  => ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                'large-icon'  => ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_MEDIA,
                    App::auth()::PERMISSION_MEDIA_ADMIN,
                ]),
            ],
            'blog_pref' => [
                'title'       => __('Blog settings'),
                'url'         => App::backend()->url()->get('admin.blog.pref'),
                'small-icon'  => ['images/menu/blog-pref.svg','images/menu/blog-pref-dark.svg'],
                'large-icon'  => ['images/menu/blog-pref.svg','images/menu/blog-pref-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_ADMIN,
                ]),
            ],
            'blog_theme' => [
                'title'       => __('Blog appearance'),
                'url'         => App::backend()->url()->get('admin.blog.theme'),
                'small-icon'  => ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                'large-icon'  => ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                'permissions' => App::auth()->makePermissions([
                    App::auth()::PERMISSION_ADMIN,
                ]),
            ],
            'blogs' => [
                'title'       => __('Blogs'),
                'url'         => App::backend()->url()->get('admin.blogs'),
                'small-icon'  => ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                'large-icon'  => ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                'permissions' => !App::auth()->isSuperAdmin() && App::auth()->getBlogCount() > 1 ? App::auth()->makePermissions([
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]) : null,
            ],
            'users' => [
                'title'      => __('Users'),
                'url'        => App::backend()->url()->get('admin.users'),
                'small-icon' => 'images/menu/users.svg',
                'large-icon' => 'images/menu/users.svg',
            ],
            'plugins' => [
                'title'      => __('Plugins management'),
                'url'        => App::backend()->url()->get('admin.plugins'),
                'small-icon' => ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
                'large-icon' => ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
            ],
            'settings' => [
                'title'      => __('Plugins settings'),
                'url'        => App::backend()->url()->get('admin.settings'),
                'small-icon' => ['images/menu/settings.svg', 'images/menu/settings-dark.svg'],
                'large-icon' => ['images/menu/settings.svg', 'images/menu/settings-dark.svg'],
            ],
            'langs' => [
                'title'      => __('Languages'),
                'url'        => App::backend()->url()->get('admin.langs'),
                'small-icon' => ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
                'large-icon' => ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
            ],
            'help' => [
                'title'      => __('Global help'),
                'url'        => App::backend()->url()->get('admin.help'),
                'small-icon' => 'images/menu/help.svg',
                'large-icon' => 'images/menu/help.svg',
            ],
        ]);
    }
}
