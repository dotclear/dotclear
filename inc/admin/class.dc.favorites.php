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
 * dcFavorites -- Favorites handling facilities
 *
 */
class dcFavorites
{
    /** @var dcCore dotclear core instance */
    protected $core;

    /** @var array list of favorite definitions  */
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
     * @param mixed  $core   dotclear core
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct($core)
    {
        $this->core       = $core;
        $this->fav_defs   = new ArrayObject();
        $this->ws         = $core->auth->user_prefs->addWorkspace('dashboard');
        $this->user_prefs = array();

        if ($this->ws->prefExists('favorites')) {
            $this->local_prefs  = $this->ws->getLocal('favorites');
            $this->global_prefs = $this->ws->getGlobal('favorites');
            // Since we never know what user puts through user:preferences ...
            if (!is_array($this->local_prefs)) {
                $this->local_prefs = array();
            }
            if (!is_array($this->global_prefs)) {
                $this->global_prefs = array();
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
     *
     */
    public function setup()
    {
        defaultFavorites::initDefaultFavorites($this);
        $this->legacyFavorites();
        $this->core->callBehavior('adminDashboardFavorites', $this->core, $this);
        $this->setUserPrefs();
    }

    /**
     * getFavorite - retrieves a favorite (complete description) from its id.
     *
     * @param string  $id   the favorite id, or an array having 1 key 'name' set to id, ther keys are merged to favorite.
     *
     * @access public
     *
     * @return array the favorite, false if not found (or not permitted)
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
        $fattr = array_merge(array('id' => null, 'class' => null), $fattr);
        if (isset($fattr['permissions'])) {
            if (is_bool($fattr['permissions']) && !$fattr['permissions']) {
                return false;
            }
            if (!$this->core->auth->check($fattr['permissions'], $this->core->blog->id)) {
                return false;
            }
        }
        return $fattr;
    }

    /**
     * getFavorites - retrieves a list of favorites.
     *
     * @param string  $ids   an array of ids, as defined in getFavorite.
     *
     * @access public
     *
     * @return array array of favorites, can be empty if ids are not found (or not permitted)
     */
    public function getFavorites($ids)
    {
        $prefs = array();
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
     * @access protected
     *
     */
    protected function setUserPrefs()
    {
        $this->user_prefs = $this->getFavorites($this->local_prefs);
        if (!count($this->user_prefs)) {
            $this->user_prefs = $this->getFavorites($this->global_prefs);
        }
        if (!count($this->user_prefs)) {
            $this->user_prefs = $this->getFavorites(array('new_post'));
        }
        $u = explode('?', $_SERVER['REQUEST_URI']);
        // Loop over prefs to enable active favorites
        foreach ($this->user_prefs as $k => &$v) {
            if (isset($v['active_cb']) && is_callable($v['active_cb'])) {
                // Use callback if defined to match whether favorite is active or not
                $v['active'] = call_user_func($v['active_cb'], $u[0], $_REQUEST);
            } else {
                                     // Failback active detection. We test against URI name & parameters
                $v['active'] = true; // true until something proves it is false
                $u           = explode('?', $v['url'], 2);
                if (!preg_match('/' . preg_quote($u[0], "/") . '/', $_SERVER['REQUEST_URI'])) {
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
     *
     */
    protected function migrateFavorites()
    {
        $fav_ws             = $this->core->auth->user_prefs->addWorkspace('favorites');
        $this->local_prefs  = array();
        $this->global_prefs = array();
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
     *
     */
    protected function legacyFavorites()
    {
        $f = new ArrayObject();
        $this->core->callBehavior('adminDashboardFavs', $this->core, $f);
        foreach ($f as $k => $v) {
            $fav = array(
                'title'       => __($v[1]),
                'url'         => $v[2],
                'small-icon'  => $v[3],
                'large-icon'  => $v[4],
                'permissions' => $v[5],
                'id'          => $v[6],
                'class'       => $v[7]
            );
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
     * @param dcMenu  $menu   admin menu instance
     *
     * @access public
     */
    public function appendMenuTitle($menu)
    {
        $menu['Favorites']        = new dcMenu('favorites-menu', 'My favorites');
        $menu['Favorites']->title = __('My favorites');
    }

    /**
     * appendMenu - adds favorites items title to sidebar menu
     *                    shall not be called outside admin/prepend.php...
     *
     * @param dcMenu  $menu   admin menu instance
     *
     * @access public
     */
    public function appendMenu($menu)
    {
        foreach ($this->user_prefs as $k => $v) {
            $menu['Favorites']->addItem(
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
                call_user_func($v['dashboard_cb'], $this->core, $v);
            }
            $icons[$k] = new ArrayObject(array($v['title'], $v['url'], $v['large-icon']));
            $this->core->callBehavior('adminDashboardFavsIcon', $this->core, $k, $icons[$k]);
        }
    }

    /**
     * register - registers a new favorite definition
     *
     * @param string  $id   favorite id
     * @param array  $data favorite information. Array keys are :
     *    'title' => favorite title (localized)
     *    'url' => favorite URL,
     *    'small-icon' => favorite small icon (for menu)
     *    'large-icon' => favorite large icon (for dashboard)
     *    'permissions' => (optional) comma-separated list of permissions for thie fav, if not set : no restriction
     *    'dashboard_cb' => (optional) callback to modify title if dynamic, if not set : title is taken as is
     *    'active_cb' => (optional) callback to tell whether current page matches favorite or not, for complex pages
     *
     * @access public
     */
    public function register($id, $data)
    {
        $this->fav_defs[$id] = $data;
        return $this;
    }

    /**
     * registerMultiple - registers a list of favorites definition
     *
     * @param array an array defining all favorites key is the id, value is the data.
     *                see register method for data format
     * @access public
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
     * @return true if the fav definition exists, false otherwise
     */
    public function exists($id)
    {
        return isset($this->fav_defs[$id]);
    }

}

/**
 * defaultFavorites -- default favorites definition
 *
 */
class defaultFavorites
{
    public static function initDefaultFavorites($favs)
    {
        $core = &$GLOBALS['core'];
        $favs->registerMultiple(array(
            'prefs'      => array(
                'title'      => __('My preferences'),
                'url'        => $core->adminurl->get("admin.user.preferences"),
                'small-icon' => 'images/menu/user-pref.png',
                'large-icon' => 'images/menu/user-pref-b.png'),
            'new_post'   => array(
                'title'       => __('New entry'),
                'url'         => $core->adminurl->get("admin.post"),
                'small-icon'  => 'images/menu/edit.png',
                'large-icon'  => 'images/menu/edit-b.png',
                'permissions' => 'usage,contentadmin'),
            'posts'      => array(
                'title'        => __('Posts'),
                'url'          => $core->adminurl->get("admin.posts"),
                'small-icon'   => 'images/menu/entries.png',
                'large-icon'   => 'images/menu/entries-b.png',
                'permissions'  => 'usage,contentadmin',
                'dashboard_cb' => array('defaultFavorites', 'postsDashboard')),
            'comments'   => array(
                'title'        => __('Comments'),
                'url'          => $core->adminurl->get("admin.comments"),
                'small-icon'   => 'images/menu/comments.png',
                'large-icon'   => 'images/menu/comments-b.png',
                'permissions'  => 'usage,contentadmin',
                'dashboard_cb' => array('defaultFavorites', 'commentsDashboard')),
            'search'     => array(
                'title'       => __('Search'),
                'url'         => $core->adminurl->get("admin.search"),
                'small-icon'  => 'images/menu/search.png',
                'large-icon'  => 'images/menu/search-b.png',
                'permissions' => 'usage,contentadmin'),
            'categories' => array(
                'title'       => __('Categories'),
                'url'         => $core->adminurl->get("admin.categories"),
                'small-icon'  => 'images/menu/categories.png',
                'large-icon'  => 'images/menu/categories-b.png',
                'permissions' => 'categories'),
            'media'      => array(
                'title'       => __('Media manager'),
                'url'         => $core->adminurl->get("admin.media"),
                'small-icon'  => 'images/menu/media.png',
                'large-icon'  => 'images/menu/media-b.png',
                'permissions' => 'media,media_admin'),
            'blog_pref'  => array(
                'title'       => __('Blog settings'),
                'url'         => $core->adminurl->get("admin.blog.pref"),
                'small-icon'  => 'images/menu/blog-pref.png',
                'large-icon'  => 'images/menu/blog-pref-b.png',
                'permissions' => 'admin'),
            'blog_theme' => array(
                'title'       => __('Blog appearance'),
                'url'         => $core->adminurl->get("admin.blog.theme"),
                'small-icon'  => 'images/menu/themes.png',
                'large-icon'  => 'images/menu/blog-theme-b.png',
                'permissions' => 'admin'),
            'blogs'      => array(
                'title'       => __('Blogs'),
                'url'         => $core->adminurl->get("admin.blogs"),
                'small-icon'  => 'images/menu/blogs.png',
                'large-icon'  => 'images/menu/blogs-b.png',
                'permissions' => 'usage,contentadmin'),
            'users'      => array(
                'title'      => __('Users'),
                'url'        => $core->adminurl->get("admin.users"),
                'small-icon' => 'images/menu/users.png',
                'large-icon' => 'images/menu/users-b.png'),
            'plugins'    => array(
                'title'      => __('Plugins management'),
                'url'        => $core->adminurl->get("admin.plugins"),
                'small-icon' => 'images/menu/plugins.png',
                'large-icon' => 'images/menu/plugins-b.png'),
            'langs'      => array(
                'title'      => __('Languages'),
                'url'        => $core->adminurl->get("admin.langs"),
                'small-icon' => 'images/menu/langs.png',
                'large-icon' => 'images/menu/langs-b.png'),
            'help'       => array(
                'title'      => __('Global help'),
                'url'        => $core->adminurl->get("admin.help"),
                'small-icon' => 'images/menu/help.png',
                'large-icon' => 'images/menu/help-b.png')
        ));
    }

    public static function postsDashboard($core, $v)
    {
        $post_count  = $core->blog->getPosts(array(), true)->f(0);
        $str_entries = __('%d post', '%d posts', $post_count);
        $v['title']  = sprintf($str_entries, $post_count);
    }

    public static function commentsDashboard($core, $v)
    {
        $comment_count = $core->blog->getComments(array(), true)->f(0);
        $str_comments  = __('%d comment', '%d comments', $comment_count);
        $v['title']    = sprintf($str_comments, $comment_count);
    }
}
