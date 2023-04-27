<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * dcFavorites -- Favorites handling facilities
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcFavorites
{
    /**
     * List of favorite definitions
     *
     * @var ArrayObject
     */
    protected $favorites;

    /**
     * Current favorite landing workspace
     *
     * @var dcWorkspace
     */
    protected $workspace;

    /**
     * List of user-defined favorite ids
     *
     * @var array
     */
    protected $local_favorites_ids;

    /**
     * List of globally-defined favorite ids
     *
     * @var array
     */
    protected $global_favorites_ids;

    /**
     * List of user preferences (made from either one of the 2 above, or not!)
     *
     * @var array
     */
    protected $user_favorites;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->favorites      = new ArrayObject();
        $this->workspace      = dcCore::app()->auth->user_prefs->dashboard;
        $this->user_favorites = [];

        if ($this->workspace->prefExists('favorites')) {
            $this->local_favorites_ids  = $this->workspace->getLocal('favorites');
            $this->global_favorites_ids = $this->workspace->getGlobal('favorites');
            // Since we never know what user puts through user:preferences ...
            if (!is_array($this->local_favorites_ids)) {
                $this->local_favorites_ids = [];
            }
            if (!is_array($this->global_favorites_ids)) {
                $this->global_favorites_ids = [];
            }
        } else {
            // No favorite defined ? Huhu, let's go for a migration
            $this->migrateFavorites();
        }
    }

    /**
     * Sets up favorites, fetch user favorites (against his permissions)
     * This method is to be called after loading plugins
     */
    public function setup()
    {
        defaultFavorites::initDefaultFavorites($this);
        $this->legacyFavorites();
        # --BEHAVIOR-- adminDashboardFavoritesV2 -- dcFavorites
        dcCore::app()->callBehavior('adminDashboardFavoritesV2', $this);
        $this->setUserPrefs();
    }

    /**
     * Retrieves a favorite (complete description) from its id.
     *
     * @param string|array  $id   the favorite id, or an array having 1 key 'name' set to id, their keys are merged to favorite.
     *
     * @return array|bool   array the favorite, false if not found (or not permitted)
     */
    public function getFavorite($id)
    {
        if (is_array($id)) {
            $fname = $id['name'];
            if (!isset($this->favorites[$fname])) {
                return false;
            }
            $favorite = $id;
            unset($favorite['name']);
            $favorite = array_merge($this->favorites[$fname], $favorite);
        } else {
            if (!isset($this->favorites[$id])) {
                return false;
            }
            $favorite = $this->favorites[$id];
        }
        $favorite = array_merge(['id' => null, 'class' => null], $favorite);
        if (isset($favorite['permissions'])) {
            if (is_bool($favorite['permissions']) && !$favorite['permissions']) {
                return false;
            }
            if (!dcCore::app()->auth->check($favorite['permissions'], dcCore::app()->blog->id)) {
                return false;
            }
        } elseif (!dcCore::app()->auth->isSuperAdmin()) {
            return false;
        }

        return $favorite;
    }

    /**
     * getFavorites - retrieves a list of favorites.
     *
     * @param array  $ids   an array of ids, as defined in getFavorite.
     *
     * @return array array of favorites, can be empty if ids are not found (or not permitted)
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
    protected function setUserPrefs()
    {
        $this->user_favorites = $this->getFavorites($this->local_favorites_ids);
        if (!count($this->user_favorites)) {
            $this->user_favorites = $this->getFavorites($this->global_favorites_ids);
        }
        if (!count($this->user_favorites)) {
            $this->user_favorites = $this->getFavorites(['new_post']);
        }

        $uri = explode('?', $_SERVER['REQUEST_URI']);
        // take only last part of the URI, all plugins work like that
        $uri[0] = preg_replace('#(.*?)([^/]+)$#', '$2', $uri[0]);

        // Loop over prefs to enable active favorites
        foreach ($this->user_favorites as &$favorite) {
            // duplicate request URI on each loop as it takes previous pref value ?!
            $url = $uri;
            if (isset($favorite['active_cb']) && is_callable($favorite['active_cb'])) {
                // Use callback if defined to match whether favorite is active or not
                $favorite['active'] = call_user_func($favorite['active_cb'], $url[0], $_REQUEST);
            } else {
                // Failback active detection. We test against URI name & parameters
                $favorite['active'] = true; // true until something proves it is false
                $url                = explode('?', $favorite['url'], 2);
                if (!preg_match('/' . preg_quote($url[0], '/') . '/', (string) $_SERVER['REQUEST_URI'])) {
                    $favorite['active'] = false; // no URI match
                }
                if (count($url) == 2) {
                    parse_str($url[1], $result);
                    // test against each request parameter.
                    foreach ($result as $key => $value) {
                        if (!isset($_REQUEST[$key]) || $_REQUEST[$key] !== $value) {
                            $favorite['active'] = false;
                        }
                    }
                }
            }
        }
    }

    /**
     * migrateFavorites - migrate dc < 2.6 favorites to new format
     */
    protected function migrateFavorites()
    {
        $favorites_workspace        = dcCore::app()->auth->user_prefs->favorites;
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
    protected function legacyFavorites()
    {
        $favorites = new ArrayObject();
        # --BEHAVIOR-- adminDashboardFavsV2 -- ArrayObject
        dcCore::app()->callBehavior('adminDashboardFavsV2', $favorites);
        foreach ($favorites as $favorite) {
            $favorite_data = [
                'title'       => __($favorite[1]),
                'url'         => $favorite[2],
                'small-icon'  => $favorite[3],
                'large-icon'  => $favorite[4],
                'permissions' => $favorite[5],
                'id'          => $favorite[6],
                'class'       => $favorite[7],
            ];
            $this->register($favorite[0], $favorite_data);
        }
    }

    /**
     * Returns favorites that correspond to current user
     * (may be local, global, or failback favorites)
     *
     * @return array array of favorites (enriched)
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
     * @return array array of favorites ids (only ids, not enriched)
     */
    public function getFavoriteIDs(bool $global = false): array
    {
        return $global ? $this->global_favorites_ids : $this->local_favorites_ids;
    }

    /**
     * Stores user-defined or global favorites ids list
     * shall not be called outside preferences.php...
     *
     * @param array    $ids     list of fav ids
     * @param boolean  $global  if true, retrieve global favs, user favs otherwise
     */
    public function setFavoriteIDs(array $ids, bool $global = false)
    {
        $this->workspace->put('favorites', $ids, 'array', null, true, $global);
    }

    /**
     * Returns all available fav ids
     *
     * @return array array of favorites ids (only ids, not enriched)
     */
    public function getAvailableFavoritesIDs(): array
    {
        return array_keys($this->favorites->getArrayCopy());
    }

    /**
     * Adds favorites section title to sidebar menu
     * shall not be called outside admin/prepend.php...
     *
     * @param array|ArrayObject  $menu   admin menu
     */
    public function appendMenuTitle($menu)
    {
        $menu[dcAdmin::MENU_FAVORITES]        = new dcMenu('favorites-menu', 'My favorites');
        $menu[dcAdmin::MENU_FAVORITES]->title = __('My favorites');
    }

    /**
     * Adds favorites items title to sidebar menu
     * shall not be called outside admin/prepend.php...
     *
     * @param array|ArrayObject  $menu   admin menu
     */
    public function appendMenu($menu)
    {
        foreach ($this->user_favorites as $favorite_menu) {
            $menu[dcAdmin::MENU_FAVORITES]->addItem(
                $favorite_menu['title'],
                $favorite_menu['url'],
                $favorite_menu['small-icon'],
                $favorite_menu['active'],
                true,
                $favorite_menu['id'],
                $favorite_menu['class'],
                true
            );
        }
    }

    /**
     * Adds favorites icons to index page
     * shall not be called outside admin/index.php...
     *
     * @param array|ArrayObject  $icons   dashboard icon list to enrich
     */
    public function appendDashboardIcons($icons)
    {
        foreach ($this->user_favorites as $icon_id => $icon_data) {
            if (isset($icon_data['dashboard_cb']) && is_callable($icon_data['dashboard_cb'])) {
                $icon_data = new ArrayObject($icon_data);
                call_user_func($icon_data['dashboard_cb'], $icon_data);
            }
            $icons[$icon_id] = new ArrayObject([$icon_data['title'], $icon_data['url'], $icon_data['large-icon']]);
            # --BEHAVIOR-- adminDashboardFavsIconV2 -- string, ArrayObject
            dcCore::app()->callBehavior('adminDashboardFavsIconV2', $icon_id, $icons[$icon_id]);
        }
    }

    /**
     * Registers a new favorite definition
     *
     * @param string  $favorite_id   favorite id
     * @param array  $favorite_data favorite information. Array keys are :
     *    'title' => favorite title (localized)
     *    'url' => favorite URL,
     *    'small-icon' => favorite small icon(s) (for menu)
     *    'large-icon' => favorite large icon(s) (for dashboard)
     *    'permissions' => (optional) comma-separated list of permissions for thie fav, if not set : no restriction
     *    'dashboard_cb' => (optional) callback to modify title if dynamic, if not set : title is taken as is
     *    'active_cb' => (optional) callback to tell whether current page matches favorite or not, for complex pages
     *
     * @return dcFavorites instance
     */
    public function register(string $favorite_id, array $favorite_data): dcFavorites
    {
        $this->favorites[$favorite_id] = $favorite_data;

        return $this;
    }

    /**
     * Registers a list of favorites definition
     *
     * @param array $data an array defining all favorites key is the id, value is the data.
     *  see register method for data format
     *
     * @return dcFavorites instance
     */
    public function registerMultiple(array $data): dcFavorites
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
}

/**
 * defaultFavorites -- default favorites definition
 */
class defaultFavorites
{
    /**
     * Initializes the default favorites.
     *
     * @param      dcFavorites  $favs   The favorites
     */
    public static function initDefaultFavorites(dcFavorites $favs)
    {
        $favs->registerMultiple([
            'prefs' => [
                'title'      => __('My preferences'),
                'url'        => dcCore::app()->adminurl->get('admin.user.preferences'),
                'small-icon' => 'images/menu/user-pref.svg',
                'large-icon' => 'images/menu/user-pref.svg', ],
            'new_post' => [
                'title'       => __('New post'),
                'url'         => dcCore::app()->adminurl->get('admin.post'),
                'small-icon'  => ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
                'large-icon'  => ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]),
                'active_cb' => ['defaultFavorites', 'newpostActive'], ],
            'posts' => [
                'title'       => __('Posts'),
                'url'         => dcCore::app()->adminurl->get('admin.posts'),
                'small-icon'  => ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                'large-icon'  => ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]),
                'dashboard_cb' => ['defaultFavorites', 'postsDashboard'], ],
            'comments' => [
                'title'       => __('Comments'),
                'url'         => dcCore::app()->adminurl->get('admin.comments'),
                'small-icon'  => ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                'large-icon'  => ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]),
                'dashboard_cb' => ['defaultFavorites', 'commentsDashboard'], ],
            'search' => [
                'title'       => __('Search'),
                'url'         => dcCore::app()->adminurl->get('admin.search'),
                'small-icon'  => ['images/menu/search.svg','images/menu/search-dark.svg'],
                'large-icon'  => ['images/menu/search.svg','images/menu/search-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]), ],
            'categories' => [
                'title'       => __('Categories'),
                'url'         => dcCore::app()->adminurl->get('admin.categories'),
                'small-icon'  => ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                'large-icon'  => ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_CATEGORIES,
                ]), ],
            'media' => [
                'title'       => __('Media manager'),
                'url'         => dcCore::app()->adminurl->get('admin.media'),
                'small-icon'  => ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                'large-icon'  => ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_MEDIA,
                    dcAuth::PERMISSION_MEDIA_ADMIN,
                ]), ],
            'blog_pref' => [
                'title'       => __('Blog settings'),
                'url'         => dcCore::app()->adminurl->get('admin.blog.pref'),
                'small-icon'  => ['images/menu/blog-pref.svg','images/menu/blog-pref-dark.svg'],
                'large-icon'  => ['images/menu/blog-pref.svg','images/menu/blog-pref-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_ADMIN,
                ]), ],
            'blog_theme' => [
                'title'       => __('Blog appearance'),
                'url'         => dcCore::app()->adminurl->get('admin.blog.theme'),
                'small-icon'  => ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                'large-icon'  => ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                'permissions' => dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_ADMIN,
                ]), ],
            'blogs' => [
                'title'       => __('Blogs'),
                'url'         => dcCore::app()->adminurl->get('admin.blogs'),
                'small-icon'  => ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                'large-icon'  => ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                'permissions' => !dcCore::app()->auth->isSuperAdmin() && dcCore::app()->auth->getBlogCount() > 1 ? dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]) : null, ],
            'users' => [
                'title'      => __('Users'),
                'url'        => dcCore::app()->adminurl->get('admin.users'),
                'small-icon' => 'images/menu/users.svg',
                'large-icon' => 'images/menu/users.svg', ],
            'plugins' => [
                'title'      => __('Plugins management'),
                'url'        => dcCore::app()->adminurl->get('admin.plugins'),
                'small-icon' => ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
                'large-icon' => ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'], ],
            'langs' => [
                'title'      => __('Languages'),
                'url'        => dcCore::app()->adminurl->get('admin.langs'),
                'small-icon' => ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
                'large-icon' => ['images/menu/langs.svg', 'images/menu/langs-dark.svg'], ],
            'help' => [
                'title'      => __('Global help'),
                'url'        => dcCore::app()->adminurl->get('admin.help'),
                'small-icon' => 'images/menu/help.svg',
                'large-icon' => 'images/menu/help.svg', ],
        ]);
    }

    /**
     * Helper for posts icon on dashboard
     *
     * @param      ArrayObject   $icon      The icon
     */
    public static function postsDashboard(ArrayObject $icon)
    {
        $post_count    = dcCore::app()->blog->getPosts([], true)->f(0);
        $str_entries   = __('%d post', '%d posts', $post_count);
        $icon['title'] = sprintf($str_entries, $post_count);
    }

    /**
     * Helper for new post active menu
     *
     * Take account of post edition (if id is set)
     *
     * @param  string   $request_uri    The URI
     * @param  array    $request_params The params
     *
     * @return boolean                  Active
     */
    public static function newpostActive(string $request_uri, array $request_params): bool
    {
        return 'post.php' === $request_uri && !isset($request_params['id']);
    }

    /**
     * Helper for comments icon on dashboard
     *
     * @param      ArrayObject   $icon      The icon
     */
    public static function commentsDashboard(ArrayObject $icon)
    {
        $comment_count = dcCore::app()->blog->getComments([], true)->f(0);
        $str_comments  = __('%d comment', '%d comments', $comment_count);
        $icon['title'] = sprintf($str_comments, $comment_count);
    }
}
