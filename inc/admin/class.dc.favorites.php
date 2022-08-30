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
 * dcFavorites -- Favorites handling facilities
 */
class dcFavorites
{
    /** @var dcCore dotclear core instance */
    /**
     * @deprecated since 2.23
     */
    protected $core;

    /** @var ArrayObject list of favorite definitions  */
    protected $fav_defs;

    /** @var dcWorkspace current favorite landing workspace */
    protected $ws;

    /** @var array list of user-defined favorite ids */
    protected $local_prefs;

    /** @var array list of globally-defined favorite ids */
    protected $global_prefs;

    /** @var array list of user preferences (either one of the 2 above, or not!) */
    protected $user_prefs;

    /**
     * Class constructor
     *
     * @param dcCore   $core   dotclear core
     *
     * @access public
     */
    public function __construct(dcCore $core = null)
    {
        $this->core       = dcCore::app();
        $this->fav_defs   = new ArrayObject();
        $this->ws         = dcCore::app()->auth->user_prefs->addWorkspace('dashboard');
        $this->user_prefs = [];

        if ($this->ws->prefExists('favorites')) {
            $this->local_prefs  = $this->ws->getLocal('favorites');
            $this->global_prefs = $this->ws->getGlobal('favorites');
            // Since we never know what user puts through user:preferences ...
            if (!is_array($this->local_prefs)) {
                $this->local_prefs = [];
            }
            if (!is_array($this->global_prefs)) {
                $this->global_prefs = [];
            }
        } else {
            // No favorite defined ? Huhu, let's go for a migration
            $this->migrateFavorites();
        }
    }

    /**
     * setup - sets up favorites, fetch user favorites (against his permissions)
     *            This method is to be called after loading plugins
     *
     * @access public
     */
    public function setup()
    {
        defaultFavorites::initDefaultFavorites(dcCore::app(), $this);
        $this->legacyFavorites();
        dcCore::app()->callBehavior('adminDashboardFavoritesV2', $this);
        $this->setUserPrefs();
    }

    /**
     * getFavorite - retrieves a favorite (complete description) from its id.
     *
     * @param mixed  $p   the favorite id, or an array having 1 key 'name' set to id, ther keys are merged to favorite.
     *
     * @access public
     *
     * @return mixed    array the favorite, false if not found (or not permitted)
     */
    public function getFavorite($p)
    {
        if (is_array($p)) {
            $fname = $p['name'];
            if (!isset($this->fav_defs[$fname])) {
                return false;
            }
            $fattr = $p;
            unset($fattr['name']);
            $fattr = array_merge($this->fav_defs[$fname], $fattr);
        } else {
            if (!isset($this->fav_defs[$p])) {
                return false;
            }
            $fattr = $this->fav_defs[$p];
        }
        $fattr = array_merge(['id' => null, 'class' => null], $fattr);
        if (isset($fattr['permissions'])) {
            if (is_bool($fattr['permissions']) && !$fattr['permissions']) {
                return false;
            }
            if (!dcCore::app()->auth->check($fattr['permissions'], dcCore::app()->blog->id)) {
                return false;
            }
        } elseif (!dcCore::app()->auth->isSuperAdmin()) {
            return false;
        }

        return $fattr;
    }

    /**
     * getFavorites - retrieves a list of favorites.
     *
     * @param array  $ids   an array of ids, as defined in getFavorite.
     *
     * @access public
     *
     * @return array array of favorites, can be empty if ids are not found (or not permitted)
     */
    public function getFavorites($ids)
    {
        $prefs = [];
        foreach ($ids as $id) {
            $f = $this->getFavorite($id);
            if ($f !== false) {
                $prefs[$id] = $f;
            }
        }

        return $prefs;
    }

    /**
     * setUserPrefs - get user favorites from settings. These are complete favorites, not ids only
     *                 returned favorites are the first non-empty list from :
     *                 * user-defined favorites
     *                 * globally-defined favorites
     *                 * a failback list "new post" (shall never be empty)
     *                This method is called by ::setup()
     *
     * @access protected
     */
    protected function setUserPrefs()
    {
        $this->user_prefs = $this->getFavorites($this->local_prefs);
        if (!count($this->user_prefs)) {
            $this->user_prefs = $this->getFavorites($this->global_prefs);
        }
        if (!count($this->user_prefs)) {
            $this->user_prefs = $this->getFavorites(['new_post']);
        }
        $uri = explode('?', $_SERVER['REQUEST_URI']);
        // take only last part of the URI, all plugins work like that
        $uri[0] = preg_replace('#(.*?)([^/]+)$#', '$2', $uri[0]);
        // Loop over prefs to enable active favorites
        foreach ($this->user_prefs as $k => &$v) {
            // duplicate request URI on each loop as it takes previous pref value ?!
            $u = $uri;
            if (isset($v['active_cb']) && is_callable($v['active_cb'])) {
                // Use callback if defined to match whether favorite is active or not
                $v['active'] = call_user_func($v['active_cb'], $u[0], $_REQUEST);
            } else {
                // Failback active detection. We test against URI name & parameters
                $v['active'] = true; // true until something proves it is false
                $u           = explode('?', $v['url'], 2);
                if (!preg_match('/' . preg_quote($u[0], '/') . '/', $_SERVER['REQUEST_URI'])) {
                    $v['active'] = false; // no URI match
                }
                if (count($u) == 2) {
                    parse_str($u[1], $p);
                    // test against each request parameter.
                    foreach ($p as $k2 => $v2) {
                        if (!isset($_REQUEST[$k2]) || $_REQUEST[$k2] !== $v2) {
                            $v['active'] = false;
                        }
                    }
                }
            }
        }
    }

    /**
     * migrateFavorites - migrate dc < 2.6 favorites to new format
     *
     * @access protected
     */
    protected function migrateFavorites()
    {
        $fav_ws             = dcCore::app()->auth->user_prefs->addWorkspace('favorites');
        $this->local_prefs  = [];
        $this->global_prefs = [];
        foreach ($fav_ws->dumpPrefs() as $k => $v) {
            $fav = @unserialize($v['value']);
            if (is_array($fav)) {
                if ($v['global']) {
                    $this->global_prefs[] = $fav['name'];
                } else {
                    $this->local_prefs[] = $fav['name'];
                }
            }
        }
        $this->ws->put('favorites', $this->global_prefs, 'array', 'User favorites', true, true);
        $this->ws->put('favorites', $this->local_prefs);
        $this->user_prefs = $this->getFavorites($this->local_prefs);
    }

    /**
     * legacyFavorites - handle legacy favorites using adminDashboardFavs behavior
     *
     * @access protected
     */
    protected function legacyFavorites()
    {
        $f = new ArrayObject();
        dcCore::app()->callBehavior('adminDashboardFavsV2', $f);
        foreach ($f as $k => $v) {
            $fav = [
                'title'       => __($v[1]),
                'url'         => $v[2],
                'small-icon'  => $v[3],
                'large-icon'  => $v[4],
                'permissions' => $v[5],
                'id'          => $v[6],
                'class'       => $v[7],
            ];
            $this->register($v[0], $fav);
        }
    }

    /**
     * getUserFavorites - returns favorites that correspond to current user
     *   (may be local, global, or failback favorites)
     *
     * @access public
     *
     * @return array array of favorites (enriched)
     */
    public function getUserFavorites()
    {
        return $this->user_prefs;
    }

    /**
     * getFavoriteIDs - returns user-defined or global favorites ids list
     *                    shall not be called outside preferences.php...
     *
     * @param boolean  $global   if true, retrieve global favs, user favs otherwise
     *
     * @access public
     *
     * @return array array of favorites ids (only ids, not enriched)
     */
    public function getFavoriteIDs($global = false)
    {
        return $global ? $this->global_prefs : $this->local_prefs;
    }

    /**
     * setFavoriteIDs - stores user-defined or global favorites ids list
     *                    shall not be called outside preferences.php...
     *
     * @param array  $ids   list of fav ids
     * @param boolean  $global   if true, retrieve global favs, user favs otherwise
     *
     * @access public
     */
    public function setFavoriteIDs($ids, $global = false)
    {
        $this->ws->put('favorites', $ids, 'array', null, true, $global);
    }

    /**
     * getAvailableFavoritesIDs - returns all available fav ids
     *
     * @access public
     *
     * @return array array of favorites ids (only ids, not enriched)
     */
    public function getAvailableFavoritesIDs()
    {
        return array_keys($this->fav_defs->getArrayCopy());
    }

    /**
     * appendMenuTitle - adds favorites section title to sidebar menu
     *                    shall not be called outside admin/prepend.php...
     *
     * @param array|ArrayObject  $menu   admin menu
     *
     * @access public
     */
    public function appendMenuTitle($menu)
    {
        $menu[dcAdmin::MENU_FAVORITES]        = new dcMenu('favorites-menu', 'My favorites');
        $menu[dcAdmin::MENU_FAVORITES]->title = __('My favorites');
    }

    /**
     * appendMenu - adds favorites items title to sidebar menu
     *                    shall not be called outside admin/prepend.php...
     *
     * @param array|ArrayObject  $menu   admin menu
     *
     * @access public
     */
    public function appendMenu($menu)
    {
        foreach ($this->user_prefs as $k => $v) {
            $menu[dcAdmin::MENU_FAVORITES]->addItem(
                $v['title'],
                $v['url'],
                $v['small-icon'],
                $v['active'],
                true,
                $v['id'],
                $v['class'],
                true
            );
        }
    }

    /**
     * appendDashboardIcons - adds favorites icons to index page
     *                    shall not be called outside admin/index.php...
     *
     * @param array  $icons   dashboard icon list to enrich
     *
     * @access public
     */
    public function appendDashboardIcons($icons)
    {
        foreach ($this->user_prefs as $k => $v) {
            if (isset($v['dashboard_cb']) && is_callable($v['dashboard_cb'])) {
                $v = new ArrayObject($v);
                call_user_func($v['dashboard_cb'], dcCore::app(), $v);
            }
            $icons[$k] = new ArrayObject([$v['title'], $v['url'], $v['large-icon']]);
            dcCore::app()->callBehavior('adminDashboardFavsIconV2', $k, $icons[$k]);
        }
    }

    /**
     * register - registers a new favorite definition
     *
     * @param string  $id   favorite id
     * @param array  $data favorite information. Array keys are :
     *    'title' => favorite title (localized)
     *    'url' => favorite URL,
     *    'small-icon' => favorite small icon(s) (for menu)
     *    'large-icon' => favorite large icon(s) (for dashboard)
     *    'permissions' => (optional) comma-separated list of permissions for thie fav, if not set : no restriction
     *    'dashboard_cb' => (optional) callback to modify title if dynamic, if not set : title is taken as is
     *    'active_cb' => (optional) callback to tell whether current page matches favorite or not, for complex pages
     *
     * @access public
     *
     * @return dcFavorites instance
     */
    public function register($id, $data)
    {
        $this->fav_defs[$id] = $data;

        return $this;
    }

    /**
     * registerMultiple - registers a list of favorites definition
     *
     * @param array $data an array defining all favorites key is the id, value is the data.
     *                see register method for data format
     * @access public
     *
     * @return dcFavorites instance
     */
    public function registerMultiple($data)
    {
        foreach ($data as $k => $v) {
            $this->register($k, $v);
        }

        return $this;
    }

    /**
     * exists - tells whether a fav definition exists or not
     *
     * @param string $id : the fav id to test
     *
     * @access public
     *
     * @return bool true if the fav definition exists, false otherwise
     */
    public function exists($id)
    {
        return isset($this->fav_defs[$id]);
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
     * @param      dcFavorites  $favs   The favs
     */
    public static function initDefaultFavorites(dcCore $core, $favs)
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
                'permissions' => 'usage,contentadmin',
                'active_cb'   => ['defaultFavorites', 'newpostActive'], ],
            'posts' => [
                'title'        => __('Posts'),
                'url'          => dcCore::app()->adminurl->get('admin.posts'),
                'small-icon'   => ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                'large-icon'   => ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                'permissions'  => 'usage,contentadmin',
                'dashboard_cb' => ['defaultFavorites', 'postsDashboard'], ],
            'comments' => [
                'title'        => __('Comments'),
                'url'          => dcCore::app()->adminurl->get('admin.comments'),
                'small-icon'   => ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                'large-icon'   => ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                'permissions'  => 'usage,contentadmin',
                'dashboard_cb' => ['defaultFavorites', 'commentsDashboard'], ],
            'search' => [
                'title'       => __('Search'),
                'url'         => dcCore::app()->adminurl->get('admin.search'),
                'small-icon'  => ['images/menu/search.svg','images/menu/search-dark.svg'],
                'large-icon'  => ['images/menu/search.svg','images/menu/search-dark.svg'],
                'permissions' => 'usage,contentadmin', ],
            'categories' => [
                'title'       => __('Categories'),
                'url'         => dcCore::app()->adminurl->get('admin.categories'),
                'small-icon'  => ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                'large-icon'  => ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                'permissions' => 'categories', ],
            'media' => [
                'title'       => __('Media manager'),
                'url'         => dcCore::app()->adminurl->get('admin.media'),
                'small-icon'  => ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                'large-icon'  => ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                'permissions' => 'media,media_admin', ],
            'blog_pref' => [
                'title'       => __('Blog settings'),
                'url'         => dcCore::app()->adminurl->get('admin.blog.pref'),
                'small-icon'  => ['images/menu/blog-pref.svg','images/menu/blog-pref-dark.svg'],
                'large-icon'  => ['images/menu/blog-pref.svg','images/menu/blog-pref-dark.svg'],
                'permissions' => 'admin', ],
            'blog_theme' => [
                'title'       => __('Blog appearance'),
                'url'         => dcCore::app()->adminurl->get('admin.blog.theme'),
                'small-icon'  => ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                'large-icon'  => ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                'permissions' => 'admin', ],
            'blogs' => [
                'title'       => __('Blogs'),
                'url'         => dcCore::app()->adminurl->get('admin.blogs'),
                'small-icon'  => ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                'large-icon'  => ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                'permissions' => 'usage,contentadmin', ],
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
     * @param      dcCore  $core   The core
     * @param      mixed   $v      { parameter_description }
     */
    public static function postsDashboard($core, $v)
    {
        $post_count  = dcCore::app()->blog->getPosts([], true)->f(0);
        $str_entries = __('%d post', '%d posts', $post_count);
        $v['title']  = sprintf($str_entries, $post_count);
    }

    /**
     * Helper for new post active menu
     *
     * Take account of post edition (if id is set)
     *
     * @param  string   $request_uri    The URI
     * @param  array    $request_params The params
     * @return boolean                  Active
     */
    public static function newpostActive($request_uri, $request_params)
    {
        return 'post.php' == $request_uri && !isset($request_params['id']);
    }

    /**
     * Helper for comments icon on dashboard
     *
     * @param      dcCore  $core   The core
     * @param      mixed   $v      { parameter_description }
     */
    public static function commentsDashboard($core, $v)
    {
        $comment_count = dcCore::app()->blog->getComments([], true)->f(0);
        $str_comments  = __('%d comment', '%d comments', $comment_count);
        $v['title']    = sprintf($str_comments, $comment_count);
    }
}
