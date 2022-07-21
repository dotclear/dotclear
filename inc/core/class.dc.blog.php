<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @brief Dotclear blog class.
 *
 * Dotclear blog class instance is provided by dcCore $blog property.
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcBlog
{
    /** @var dcCore dcCore instance */
    /**
     * @deprecated since 2.23
     */
    protected $core;

    /** @var object Database connection object */
    public $con;
    /** @var string Database table prefix */
    public $prefix;

    /** @var string Blog ID */
    public $id;
    /** @var string Blog unique ID */
    public $uid;
    /** @var string Blog name */
    public $name;
    /** @var string Blog description */
    public $desc;
    /** @var string Blog URL */
    public $url;
    /** @var string Blog host */
    public $host;
    /** @var mixed Blog creation date */
    public $creadt;
    /** @var mixed Blog last update date */
    public $upddt;
    /** @var string Blog status */
    public $status;

    /** @var dcSettings dcSettings object */
    public $settings;
    /** @var string Blog theme path */
    public $themes_path;
    /** @var string Blog public path */
    public $public_path;

    private $post_status    = [];
    private $comment_status = [];

    private $categories;

    /** @var boolean Disallow entries password protection */
    public $without_password = true;

    /**
     * Constructs a new instance.
     *
     * @param      dcCore  $core   The core
     * @param      string  $id     The blog identifier
     */
    public function __construct(dcCore $core, $id)
    {
        $this->con    = dcCore::app()->con;
        $this->prefix = dcCore::app()->prefix;
        $this->core   = dcCore::app();

        if (($b = dcCore::app()->getBlog($id)) !== false) {
            $this->id     = $id;
            $this->uid    = $b->blog_uid;
            $this->name   = $b->blog_name;
            $this->desc   = $b->blog_desc;
            $this->url    = $b->blog_url;
            $this->host   = http::getHostFromURL($this->url);
            $this->creadt = strtotime($b->blog_creadt);
            $this->upddt  = strtotime($b->blog_upddt);
            $this->status = $b->blog_status;

            $this->settings = new dcSettings(dcCore::app(), $this->id);

            $this->themes_path = path::fullFromRoot($this->settings->system->themes_path, DC_ROOT);
            $this->public_path = path::fullFromRoot($this->settings->system->public_path, DC_ROOT);

            $this->post_status['-2'] = __('Pending');
            $this->post_status['-1'] = __('Scheduled');
            $this->post_status['0']  = __('Unpublished');
            $this->post_status['1']  = __('Published');

            $this->comment_status['-2'] = __('Junk');
            $this->comment_status['-1'] = __('Pending');
            $this->comment_status['0']  = __('Unpublished');
            $this->comment_status['1']  = __('Published');

            # --BEHAVIOR-- coreBlogConstruct
            dcCore::app()->callBehavior('coreBlogConstruct', $this);
        }
    }

    /// @name Common public methods
    //@{
    /**
     * Returns blog URL ending with a question mark.
     *
     * @return     string  The qmark url.
     */
    public function getQmarkURL()
    {
        if (substr($this->url, -1) != '?') {
            return $this->url . '?';
        }

        return $this->url;
    }

    /**
     * Gets the jQuery version.
     *
     * @return     string
     */
    public function getJsJQuery()
    {
        $version = $this->settings->system->jquery_version;
        if ($version == '') {
            // Version not set, use default one
            $version = DC_DEFAULT_JQUERY; // defined in inc/prepend.php
        } else {
            if (!$this->settings->system->jquery_allow_old_version) {
                // Use the blog defined version only if more recent than default
                if (version_compare($version, DC_DEFAULT_JQUERY, '<')) {
                    $version = DC_DEFAULT_JQUERY; // defined in inc/prepend.php
                }
            }
        }

        return 'jquery/' . $version;
    }

    /**
     * Returns public URL of specified plugin file.
     *
     * @param      string  $pf          plugin file
     * @param      bool    $strip_host  Strip host in URL
     *
     * @return     string
     */
    public function getPF($pf, $strip_host = true)
    {
        $ret = $this->getQmarkURL() . 'pf=' . $pf;
        if ($strip_host) {
            $ret = html::stripHostURL($ret);
        }

        return $ret;
    }

    /**
     * Returns public URL of specified var file.
     *
     * @param      string  $vf          var file
     * @param      bool    $strip_host  Strip host in URL
     *
     * @return     string
     */
    public function getVF($vf, $strip_host = true)
    {
        $ret = $this->getQmarkURL() . 'vf=' . $vf;
        if ($strip_host) {
            $ret = html::stripHostURL($ret);
        }

        return $ret;
    }

    /**
     * Returns an entry status name given to a code. Status are translated, never
     * use it for tests. If status code does not exist, returns <i>unpublished</i>.
     *
     * @param      integer  $s      The status code
     *
     * @return     string  The post status.
     */
    public function getPostStatus($s)
    {
        if (isset($this->post_status[$s])) {
            return $this->post_status[$s];
        }

        return $this->post_status['0'];
    }

    /**
     * Returns an array of available entry status codes and names.
     *
     * @return     array  Simple array with codes in keys and names in value.
     */
    public function getAllPostStatus()
    {
        return $this->post_status;
    }

    /**
     * Returns an array of available comment status codes and names.
     *
     * @return    array Simple array with codes in keys and names in value
     */
    public function getAllCommentStatus()
    {
        return $this->comment_status;
    }

    /**
     * Disallows entries password protection. You need to set it to
     * <var>false</var> while serving a public blog.
     *
     * @param      mixed  $v
     */
    public function withoutPassword($v)
    {
        $this->without_password = (bool) $v;
    }
    //@}

    /// @name Triggers methods
    //@{
    /**
     * Updates blog last update date. Should be called every time you change
     * an element related to the blog.
     */
    public function triggerBlog()
    {
        $cur = $this->con->openCursor($this->prefix . 'blog');

        $cur->blog_upddt = date('Y-m-d H:i:s');

        $sql = new dcUpdateStatement();
        $sql->where('blog_id = ' . $sql->quote($this->id));

        $sql->update($cur);

        //        $cur->update("WHERE blog_id = '" . $this->con->escape($this->id) . "' ");

        # --BEHAVIOR-- coreBlogAfterTriggerBlog
        dcCore::app()->callBehavior('coreBlogAfterTriggerBlog', $cur);
    }

    /**
     * Updates comment and trackback counters in post table. Should be called
     * every time a comment or trackback is added, removed or changed its status.
     *
     * @param      integer  $id     The comment identifier
     * @param      bool     $del    If comment is deleted, set this to true
     */
    public function triggerComment($id, $del = false)
    {
        $this->triggerComments($id, $del);
    }

    /**
     * Updates comments and trackbacks counters in post table. Should be called
     * every time comments or trackbacks are added, removed or changed their status.
     *
     * @param      mixed   $ids             The identifiers
     * @param      bool    $del             If comment is delete, set this to true
     * @param      mixed   $affected_posts  The affected posts IDs
     */
    public function triggerComments($ids, $del = false, $affected_posts = null)
    {
        $comments_ids = dcUtils::cleanIds($ids);

        # Get posts affected by comments edition
        if (empty($affected_posts)) {
            $sql = new dcSelectStatement();
            $sql
                ->column('post_id')
                ->from($this->prefix . 'comment')
                ->where('comment_id' . $sql->in($comments_ids))
                ->group('post_id');

            $rs = $sql->select();

            $affected_posts = [];
            while ($rs->fetch()) {
                $affected_posts[] = (int) $rs->post_id;
            }
        }

        if (!is_array($affected_posts) || empty($affected_posts)) {
            return;
        }

        # Count number of comments if exists for affected posts
        $sql = new dcSelectStatement();
        $sql
            ->columns([
                'post_id',
                $sql->count('post_id', 'nb_comment'),
                'comment_trackback',
            ])
            ->from($this->prefix . 'comment')
            ->where('comment_status = 1')
            ->and('post_id' . $sql->in($affected_posts))
            ->group([
                'post_id',
                'comment_trackback',
            ]);

        $rs = $sql->select();

        $posts = [];
        while ($rs->fetch()) {
            if ($rs->comment_trackback) {
                $posts[$rs->post_id]['trackback'] = $rs->nb_comment;
            } else {
                $posts[$rs->post_id]['comment'] = $rs->nb_comment;
            }
        }

        # Update number of comments on affected posts
        $cur = $this->con->openCursor($this->prefix . 'post');
        foreach ($affected_posts as $post_id) {
            $cur->clean();

            if (!array_key_exists($post_id, $posts)) {
                $cur->nb_trackback = 0;
                $cur->nb_comment   = 0;
            } else {
                $cur->nb_trackback = empty($posts[$post_id]['trackback']) ? 0 : $posts[$post_id]['trackback'];
                $cur->nb_comment   = empty($posts[$post_id]['comment']) ? 0 : $posts[$post_id]['comment'];
            }

            $sql = new dcUpdateStatement();
            $sql->where('post_id = ' . $sql->quote($post_id));

            $sql->update($cur);
        }
    }
    //@}

    /// @name Categories management methods
    //@{
    /**
     * Get dcCategories instance
     *
     * @return     dcCategories
     */
    public function categories()
    {
        if (!($this->categories instanceof dcCategories)) {
            $this->categories = new dcCategories(dcCore::app());
        }

        return $this->categories;
    }

    /**
     * Retrieves categories. <var>$params</var> is an associative array which can
     * take the following parameters:
     *
     * - post_type: Get only entries with given type (default "post")
     * - cat_url: filter on cat_url field
     * - cat_id: filter on cat_id field
     * - start: start with a given category
     * - level: categories level to retrieve
     *
     * @param      array   $params  The parameters
     *
     * @return     record  The categories.
     */
    public function getCategories($params = [])
    {
        $c_params = [];
        if (isset($params['post_type'])) {
            $c_params['post_type'] = $params['post_type'];
            unset($params['post_type']);
        }
        $counter = $this->getCategoriesCounter($c_params);

        if (isset($params['without_empty']) && ($params['without_empty'] == false)) {
            $without_empty = false;
        } else {
            $without_empty = dcCore::app()->auth->userID() == false; # Get all categories if in admin display
        }

        $start = isset($params['start']) ? (int) $params['start'] : 0;
        $l     = isset($params['level']) ? (int) $params['level'] : 0;

        $rs = $this->categories()->getChildren($start, null, 'desc');

        # Get each categories total posts count
        $data  = [];
        $stack = [];
        $level = 0;
        $cols  = $rs->columns();
        while ($rs->fetch()) {
            $nb_post = isset($counter[$rs->cat_id]) ? (int) $counter[$rs->cat_id] : 0;

            if ($rs->level > $level) {
                $nb_total          = $nb_post;
                $stack[$rs->level] = (int) $nb_post;
            } elseif ($rs->level == $level) {
                $nb_total = $nb_post;
                $stack[$rs->level] += $nb_post;
            } else {
                $nb_total = $stack[$rs->level + 1] + $nb_post;
                if (isset($stack[$rs->level])) {
                    $stack[$rs->level] += $nb_total;
                } else {
                    $stack[$rs->level] = $nb_total;
                }
                unset($stack[$rs->level + 1]);
            }

            if ($nb_total == 0 && $without_empty) {
                continue;
            }

            $level = $rs->level;

            $t = [];
            foreach ($cols as $c) {
                $t[$c] = $rs->f($c);
            }
            $t['nb_post']  = $nb_post;
            $t['nb_total'] = $nb_total;

            if ($l == 0 || ($l > 0 && $l == $rs->level)) {
                array_unshift($data, $t);
            }
        }

        # We need to apply filter after counting
        if (isset($params['cat_id']) && $params['cat_id'] !== '') {
            $found = false;
            foreach ($data as $v) {
                if ($v['cat_id'] == $params['cat_id']) {
                    $found = true;
                    $data  = [$v];

                    break;
                }
            }
            if (!$found) {
                $data = [];
            }
        }

        if (
            isset($params['cat_url']) && ($params['cat_url'] !== '')
            && !isset($params['cat_id'])
        ) {
            $found = false;
            foreach ($data as $v) {
                if ($v['cat_url'] == $params['cat_url']) {
                    $found = true;
                    $data  = [$v];

                    break;
                }
            }
            if (!$found) {
                $data = [];
            }
        }

        return staticRecord::newFromArray($data);
    }

    /**
     * Gets the category by its ID.
     *
     * @param      integer  $id     The category identifier
     *
     * @return     record  The category.
     */
    public function getCategory($id)
    {
        return $this->getCategories(['cat_id' => $id]);
    }

    /**
     * Gets the category parents.
     *
     * @param      integer  $id     The category identifier
     *
     * @return     record  The category parents.
     */
    public function getCategoryParents($id)
    {
        return $this->categories()->getParents($id);
    }

    /**
     * Gets the category first parent.
     *
     * @param      integer  $id     The category identifier
     *
     * @return     record  The category parent.
     */
    public function getCategoryParent($id)
    {
        return $this->categories()->getParent($id);
    }

    /**
     * Gets all category's first children.
     *
     * @param      int     $id     The category identifier
     *
     * @return     record  The category first children.
     */
    public function getCategoryFirstChildren($id)
    {
        return $this->getCategories(['start' => $id, 'level' => $id == 0 ? 1 : 2]);
    }

    /**
     * Returns true if a given category if in a given category's subtree
     *
     * @param      string   $cat_url    The cat url
     * @param      string   $start_url  The top cat url
     *
     * @return     boolean  true if cat_url is in given start_url cat subtree
     */
    public function IsInCatSubtree($cat_url, $start_url)
    {
        // Get cat_id from start_url
        $cat = $this->getCategories(['cat_url' => $start_url]);
        if ($cat->fetch()) {
            // cat_id found, get cat tree list
            $cats = $this->getCategories(['start' => $cat->cat_id]);
            while ($cats->fetch()) {
                // check if post category is one of the cat or sub-cats
                if ($cats->cat_url === $cat_url) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets the categories posts counter.
     *
     * @param      array  $params  The parameters
     *
     * @return     array  The categories counter.
     */
    private function getCategoriesCounter($params = [])
    {
        $sql = new dcSelectStatement();
        $sql
            ->columns([
                'C.cat_id',
                $sql->count('P.post_id', 'nb_post'),
            ])
            ->from($this->prefix . 'category AS C')
            ->join(
                (new dcJoinStatement())
                    ->from($this->prefix . 'post P')
                    ->on('C.cat_id = P.cat_id')
                    ->and('P.blog_id = ' . $sql->quote($this->id))
                    ->statement()
            )
            ->where('C.blog_id = ' . $sql->quote($this->id));

        if (!dcCore::app()->auth->userID()) {
            $sql->and('P.post_status = 1');
        }

        if (!empty($params['post_type'])) {
            $sql->and('P.post_type' . $sql->in($params['post_type']));
        }

        $sql->group('C.cat_id');

        $rs       = $sql->select();
        $counters = [];
        while ($rs->fetch()) {
            $counters[$rs->cat_id] = $rs->nb_post;
        }

        return $counters;
    }

    /**
     * Adds a new category. Takes a cursor as input and returns the new category ID.
     *
     * @param      cursor        $cur     The category cursor
     * @param      int           $parent  The parent category ID
     *
     * @throws     Exception
     *
     * @return     int  New category ID
     */
    public function addCategory($cur, $parent = 0)
    {
        if (!dcCore::app()->auth->check('categories', $this->id)) {
            throw new Exception(__('You are not allowed to add categories'));
        }

        $url = [];
        if ($parent != 0) {
            $rs = $this->getCategory($parent);
            if ($rs->isEmpty()) {
                $url = [];
            } else {
                $url[] = $rs->cat_url;
            }
        }

        if ($cur->cat_url == '') {
            $url[] = text::tidyURL($cur->cat_title, false);
        } else {
            $url[] = $cur->cat_url;
        }

        $cur->cat_url = implode('/', $url);

        $this->getCategoryCursor($cur);
        $cur->blog_id = (string) $this->id;

        # --BEHAVIOR-- coreBeforeCategoryCreate
        dcCore::app()->callBehavior('coreBeforeCategoryCreate', $this, $cur);

        $id = $this->categories()->addNode($cur, $parent);
        # Update category's cursor
        $rs = $this->getCategory($id);
        if (!$rs->isEmpty()) {
            $cur->cat_lft = $rs->cat_lft;
            $cur->cat_rgt = $rs->cat_rgt;
        }

        # --BEHAVIOR-- coreAfterCategoryCreate
        dcCore::app()->callBehavior('coreAfterCategoryCreate', $this, $cur);
        $this->triggerBlog();

        return $cur->cat_id;
    }

    /**
     * Updates an existing category.
     *
     * @param      integer     $id     The category ID
     * @param      cursor      $cur    The category cursor
     *
     * @throws     Exception
     */
    public function updCategory($id, $cur)
    {
        if (!dcCore::app()->auth->check('categories', $this->id)) {
            throw new Exception(__('You are not allowed to update categories'));
        }

        if ($cur->cat_url == '') {
            $url = [];
            $rs  = $this->categories()->getParents($id);
            while ($rs->fetch()) {
                if ($rs->index() == $rs->count() - 1) {
                    $url[] = $rs->cat_url;
                }
            }

            $url[]        = text::tidyURL($cur->cat_title, false);
            $cur->cat_url = implode('/', $url);
        }

        $this->getCategoryCursor($cur, $id);

        # --BEHAVIOR-- coreBeforeCategoryUpdate
        dcCore::app()->callBehavior('coreBeforeCategoryUpdate', $this, $cur);

        $sql = new dcUpdateStatement();
        $sql
            ->where('cat_id = ' . (int) $id)
            ->and('blog_id = ' . $sql->quote($this->id));

        $sql->update($cur);

        # --BEHAVIOR-- coreAfterCategoryUpdate
        dcCore::app()->callBehavior('coreAfterCategoryUpdate', $this, $cur);

        $this->triggerBlog();
    }

    /**
     * Set category position.
     *
     * @param      integer  $id     The category ID
     * @param      integer  $left   The category ID before
     * @param      integer  $right  The category ID after
     */
    public function updCategoryPosition($id, $left, $right)
    {
        $this->categories()->updatePosition($id, $left, $right);
        $this->triggerBlog();
    }

    /**
     * Sets the category parent.
     *
     * @param      integer  $id      The category ID
     * @param      integer  $parent  The parent category ID
     */
    public function setCategoryParent($id, $parent)
    {
        $this->categories()->setNodeParent($id, $parent);
        $this->triggerBlog();
    }

    /**
     * Sets the category position.
     *
     * @param      integer  $id       The category ID
     * @param      integer  $sibling  The sibling category ID
     * @param      string   $move     The move (before|after)
     */
    public function setCategoryPosition($id, $sibling, $move)
    {
        $this->categories()->setNodePosition($id, $sibling, $move);
        $this->triggerBlog();
    }

    /**
     * Delete a category.
     *
     * @param      integer     $id     The category ID
     *
     * @throws     Exception
     */
    public function delCategory($id)
    {
        if (!dcCore::app()->auth->check('categories', $this->id)) {
            throw new Exception(__('You are not allowed to delete categories'));
        }

        $sql = new dcSelectStatement();
        $sql
            ->column($sql->count('post_id', 'nb_post'))
            ->from($this->prefix . 'post')
            ->where('cat_id = ' . (int) $id)
            ->and('blog_id = ' . $sql->quote($this->id));

        $rs = $sql->select();

        if ($rs->nb_post > 0) {
            throw new Exception(__('This category is not empty.'));
        }

        $this->categories()->deleteNode($id, true);
        $this->triggerBlog();
    }

    /**
     * Reset categories order and relocate them to first level
     */
    public function resetCategoriesOrder()
    {
        if (!dcCore::app()->auth->check('categories', $this->id)) {
            throw new Exception(__('You are not allowed to reset categories order'));
        }

        $this->categories()->resetOrder();
        $this->triggerBlog();
    }

    /**
     * Check if the category url is unique.
     *
     * @param      string  $url    The url
     * @param      mixed   $id     The identifier
     *
     * @return     string
     */
    private function checkCategory($url, $id = null)
    {
        # Let's check if URL is taken...
        $sql = new dcSelectStatement();
        $sql
            ->column('cat_url')
            ->from($this->prefix . 'category')
            ->where('cat_url = ' . $sql->quote($url))
            ->and('blog_id = ' . $sql->quote($this->id))
            ->order('cat_url DESC');
        if ($id) {
            $sql->and('cat_id <> ' . (int) $id);
        }

        $rs = $sql->select();

        if (!$rs->isEmpty()) {
            $sql = new dcSelectStatement();
            $sql
                ->column('cat_url')
                ->from($this->prefix . 'category')
                ->where('cat_url' . $sql->regexp($url))
                ->and('blog_id = ' . $sql->quote($this->id))
                ->order('cat_url DESC');
            if ($id) {
                $sql->and('cat_id <> ' . (int) $id);
            }

            $rs = $sql->select();

            if ($rs->isEmpty()) {
                // First duplicate, add '1' to URL and return it
                return $url . '1';
            }

            $a = [];
            while ($rs->fetch()) {
                $a[] = $rs->cat_url;
            }

            natsort($a);
            $t_url = end($a);

            if (preg_match('/(.*?)([0-9]+)$/', $t_url, $m)) {
                $i   = (int) $m[2];
                $url = $m[1];
            } else {
                $i = 1;
            }

            // Return URL with it's counter incremented
            return $url . ($i + 1);
        }

        # URL is empty?
        if ($url == '') {
            throw new Exception(__('Empty category URL'));
        }

        return $url;
    }

    /**
     * Gets the category cursor.
     *
     * @param      cursor     $cur    The category cursor
     * @param      mixed      $id     The category ID
     *
     * @throws     Exception
     */
    private function getCategoryCursor($cur, $id = null)
    {
        if ($cur->cat_title == '') {
            throw new Exception(__('You must provide a category title'));
        }

        # If we don't have any cat_url, let's do one
        if ($cur->cat_url == '') {
            $cur->cat_url = text::tidyURL($cur->cat_title, false);
        }

        # Still empty ?
        if ($cur->cat_url == '') {
            throw new Exception(__('You must provide a category URL'));
        }
        $cur->cat_url = text::tidyURL($cur->cat_url, true);

        # Check if url is unique
        $cur->cat_url = $this->checkCategory($cur->cat_url, $id);

        if ($cur->cat_desc !== null) {
            $cur->cat_desc = dcCore::app()->HTMLfilter($cur->cat_desc);
        }
    }
    //@}

    /// @name Entries management methods
    //@{
    /**
     * Retrieves entries. <b>$params</b> is an array taking the following
     * optionnal parameters:
     *
     * - no_content: Don't retrieve entry content (excerpt and content)
     * - post_type: Get only entries with given type (default "post", array for many types and '' for no type)
     * - post_id: (integer or array) Get entry with given post_id
     * - post_url: Get entry with given post_url field
     * - user_id: (integer) Get entries belonging to given user ID
     * - cat_id: (string or array) Get entries belonging to given category ID
     * - cat_id_not: deprecated (use cat_id with "id ?not" instead)
     * - cat_url: (string or array) Get entries belonging to given category URL
     * - cat_url_not: deprecated (use cat_url with "url ?not" instead)
     * - post_status: (integer) Get entries with given post_status
     * - post_selected: (boolean) Get select flaged entries
     * - post_year: (integer) Get entries with given year
     * - post_month: (integer) Get entries with given month
     * - post_day: (integer) Get entries with given day
     * - post_lang: Get entries with given language code
     * - search: Get entries corresponding of the following search string
     * - columns: (array) More columns to retrieve
     * - join: Append a JOIN clause for the FROM statement in query
     * - sql: Append SQL string at the end of the query
     * - from: Append another FROM source in query
     * - order: Order of results (default "ORDER BY post_dt DES")
     * - limit: Limit parameter
     * - sql_only : return the sql request instead of results. Only ids are selected
     * - exclude_post_id : (integer or array) Exclude entries with given post_id
     *
     * Please note that on every cat_id or cat_url, you can add ?not to exclude
     * the category and ?sub to get subcategories.
     *
     * @param    array              $params        Parameters
     * @param    bool               $count_only    Only counts results
     * @param    dcSelectStatement  $ext_sql       Optional dcSelectStatement instance
     *
     * @return   mixed    A record with some more capabilities or the SQL request
     */
    public function getPosts($params = [], $count_only = false, ?dcSelectStatement $ext_sql = null)
    {
        # --BEHAVIOR-- coreBlogBeforeGetPosts
        $params = new ArrayObject($params);
        dcCore::app()->callBehavior('coreBlogBeforeGetPosts', $params);

        $sql = $ext_sql ? clone $ext_sql : new dcSelectStatement();

        if ($count_only) {
            $sql->column($sql->count($sql->unique('P.post_id')));
        } elseif (!empty($params['sql_only'])) {
            $sql->column('P.post_id');
        } else {
            if (empty($params['no_content'])) {
                $sql->columns([
                    'post_excerpt',
                    'post_excerpt_xhtml',
                    'post_content',
                    'post_content_xhtml',
                    'post_notes',
                ]);
            }

            if (!empty($params['columns']) && is_array($params['columns'])) {
                $sql->columns($params['columns']);
            }
            $sql->columns([
                'P.post_id',
                'P.blog_id',
                'P.user_id',
                'P.cat_id',
                'post_dt',
                'post_tz',
                'post_creadt',
                'post_upddt',
                'post_format',
                'post_password',
                'post_url',
                'post_lang',
                'post_title',
                'post_type',
                'post_meta',
                'post_status',
                'post_firstpub',
                'post_selected',
                'post_position',
                'post_open_comment',
                'post_open_tb',
                'nb_comment',
                'nb_trackback',
                'U.user_name',
                'U.user_firstname',
                'U.user_displayname',
                'U.user_email',
                'U.user_url',
                'C.cat_title',
                'C.cat_url',
                'C.cat_desc',
            ]);
        }

        $sql
            ->from($this->prefix . 'post P', false, true)
            ->join(
                (new dcJoinStatement())
                    ->inner()
                    ->from($this->prefix . 'user U')
                    ->on('U.user_id = P.user_id')
                    ->statement()
            )
            ->join(
                (new dcJoinStatement())
                    ->left()
                    ->from($this->prefix . 'category C')
                    ->on('P.cat_id = C.cat_id')
                    ->statement()
            );

        if (!empty($params['join'])) {
            $sql->join($params['join']);
        }

        if (!empty($params['from'])) {
            $sql->from($params['from']);
        }

        if (!empty($params['where'])) {
            // Cope with legacy code
            $sql->where($params['where']);
        } else {
            $sql->where('P.blog_id = ' . $sql->quote($this->id));
        }

        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $user_id = dcCore::app()->auth->userID();

            $and = ['post_status = 1'];
            if ($this->without_password) {
                $and[] = 'post_password IS NULL';
            }
            $or = [$sql->andGroup($and)];
            if ($user_id) {
                $or[] = 'P.user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($or));
        }

        #Adding parameters
        if (isset($params['post_type'])) {
            if (is_array($params['post_type']) || $params['post_type'] != '') {
                $sql->and('post_type' . $sql->in($params['post_type']));
            }
        } else {
            $sql->and('post_type = ' . $sql->quote('post'));
        }

        if (isset($params['post_id']) && $params['post_id'] !== '') {
            if (is_array($params['post_id'])) {
                array_walk($params['post_id'], function (&$v, $k) {
                    if ($v !== null) {
                        $v = (int) $v;
                    }
                });
            } else {
                $params['post_id'] = [(int) $params['post_id']];
            }
            $sql->and('P.post_id' . $sql->in($params['post_id']));
        }

        if (isset($params['exclude_post_id']) && $params['exclude_post_id'] !== '') {
            if (is_array($params['exclude_post_id'])) {
                array_walk($params['exclude_post_id'], function (&$v, $k) {
                    if ($v !== null) {
                        $v = (int) $v;
                    }
                });
            } else {
                $params['exclude_post_id'] = [(int) $params['exclude_post_id']];
            }
            $sql->and('P.post_id NOT' . $sql->in($params['exclude_post_id']));
        }

        if (isset($params['post_url']) && $params['post_url'] !== '') {
            $sql->and('post_url = ' . $sql->quote($params['post_url']));
        }

        if (!empty($params['user_id'])) {
            $sql->and('U.user_id = ' . $sql->quote($params['user_id']));
        }

        if (isset($params['cat_id']) && $params['cat_id'] !== '') {
            if (!is_array($params['cat_id'])) {
                $params['cat_id'] = [$params['cat_id']];
            }
            if (!empty($params['cat_id_not'])) {
                array_walk($params['cat_id'], function (&$v, $k) {
                    $v = $v . ' ?not';
                });
            }

            $sql->and($this->getPostsCategoryFilter($params['cat_id'], 'cat_id'));
        } elseif (isset($params['cat_url']) && $params['cat_url'] !== '') {
            if (!is_array($params['cat_url'])) {
                $params['cat_url'] = [$params['cat_url']];
            }
            if (!empty($params['cat_url_not'])) {
                array_walk($params['cat_url'], function (&$v, $k) {
                    $v = $v . ' ?not';
                });
            }

            $sql->and($this->getPostsCategoryFilter($params['cat_url'], 'cat_url'));
        }

        /* Other filters */
        if (isset($params['post_status'])) {
            $sql->and('post_status = ' . (int) $params['post_status']);
        }

        if (isset($params['post_firstpub'])) {
            $sql->and('post_firstpub = ' . (int) $params['post_firstpub']);
        }

        if (isset($params['post_selected'])) {
            $sql->and('post_selected = ' . (int) $params['post_selected']);
        }

        if (!empty($params['post_year'])) {
            $sql->and($sql->dateFormat('post_dt', '%Y') . ' = ' . $sql->quote(sprintf('%04d', $params['post_year'])));
        }

        if (!empty($params['post_month'])) {
            $sql->and($sql->dateFormat('post_dt', '%m') . ' = ' . $sql->quote(sprintf('%02d', $params['post_month'])));
        }

        if (!empty($params['post_day'])) {
            $sql->and($sql->dateFormat('post_dt', '%d') . ' = ' . $sql->quote(sprintf('%02d', $params['post_day'])));
        }

        if (!empty($params['post_lang'])) {
            $sql->and('P.post_lang = ' . $sql->quote($params['post_lang']));
        }

        if (!empty($params['search'])) {
            $words = text::splitWords($params['search']);

            if (!empty($words)) {
                # --BEHAVIOR-- corePostSearch
                if (dcCore::app()->hasBehavior('corePostSearch')) {
                    dcCore::app()->callBehavior('corePostSearch', dcCore::app(), [&$words, &$params, $sql]);
                }

                foreach ($words as $i => $w) {
                    $words[$i] = $sql->like('post_words', '%' . $sql->escape($w) . '%');
                }
                $sql->and($words);
            }
        }

        if (isset($params['media'])) {
            $sqlExists = new dcSelectStatement();
            $sqlExists
                ->from($this->prefix . 'post_media M')
                ->column('M.post_id')
                ->where('M.post_id = P.post_id');

            if (isset($params['link_type'])) {
                $sqlExists->and('M.link_type' . $sqlExists->in($params['link_type']));
            }

            $sql->and(($params['media'] == '0' ? 'NOT ' : '') . 'EXISTS (' . $sqlExists->statement() . ')');
        }

        if (!empty($params['sql'])) {
            $sql->sql($params['sql']);
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('post_dt DESC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        if (!empty($params['sql_only'])) {
            return $sql->statement();
        }

        $rs            = $sql->select();
        $rs->core      = dcCore::app();
        $rs->_nb_media = [];
        $rs->extend('rsExtPost');

        # --BEHAVIOR-- coreBlogGetPosts
        dcCore::app()->callBehavior('coreBlogGetPosts', $rs);

        # --BEHAVIOR-- coreBlogAfterGetPosts
        $alt = new arrayObject(['rs' => null, 'params' => $params, 'count_only' => $count_only]);
        dcCore::app()->callBehavior('coreBlogAfterGetPosts', $rs, $alt);
        if ($alt['rs'] instanceof record) { // @phpstan-ignore-line
            $rs = $alt['rs'];
        }

        return $rs;
    }

    /**
     * Returns a record with post id, title and date for next or previous post
     * according to the post ID.
     * $dir could be 1 (next post) or -1 (previous post).
     *
     * @param      record  $post                  The post ID
     * @param      int     $dir                   The search direction
     * @param      bool    $restrict_to_category  Restrict to same category
     * @param      bool    $restrict_to_lang      Restrict to same language
     *
     * @return     mixed   The next post.
     */
    public function getNextPost($post, $dir, $restrict_to_category = false, $restrict_to_lang = false)
    {
        $dt      = $post->post_dt;
        $post_id = (int) $post->post_id;

        if ($dir > 0) {
            $sign  = '>';
            $order = 'ASC';
        } else {
            $sign  = '<';
            $order = 'DESC';
        }

        $params['post_type'] = $post->post_type;
        $params['limit']     = 1;
        $params['order']     = 'post_dt ' . $order . ', P.post_id ' . $order;
        $params['sql']       = 'AND ( ' .
            "   (post_dt = '" . $this->con->escape($dt) . "' AND P.post_id " . $sign . ' ' . $post_id . ') ' .
            '   OR post_dt ' . $sign . " '" . $this->con->escape($dt) . "' " .
            ') ';

        if ($restrict_to_category) {
            $params['sql'] .= $post->cat_id ? 'AND P.cat_id = ' . (int) $post->cat_id . ' ' : 'AND P.cat_id IS NULL ';
        }

        if ($restrict_to_lang) {
            $params['sql'] .= $post->post_lang ? 'AND P.post_lang = \'' . $this->con->escape($post->post_lang) . '\' ' : 'AND P.post_lang IS NULL ';
        }

        $rs = $this->getPosts($params);

        if ($rs->isEmpty()) {
            return;
        }

        return $rs;
    }

    /**
     * Retrieves different languages and post count on blog, based on post_lang
     * field. <var>$params</var> is an array taking the following optionnal
     * parameters:
     *
     * - post_type: Get only entries with given type (default "post", '' for no type)
     * - lang: retrieve post count for selected lang
     * - order: order statement (default post_lang DESC)
     *
     * @param      array   $params  The parameters
     *
     * @return     record  The langs.
     */
    public function getLangs($params = [])
    {
        $sql = new dcSelectStatement();
        $sql
            ->columns([
                $sql->count('post_id', 'nb_post'),
                'post_lang',
            ])
            ->from($this->prefix . 'post')
            ->where('blog_id = ' . $sql->quote($this->id))
            ->and('post_lang <> ' . $sql->quote(''))
            ->and('post_lang IS NOT NULL');

        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $and = ['post_status = 1'];
            if ($this->without_password) {
                $and[] = 'post_password IS NULL';
            }
            $or = [$sql->andGroup($and)];
            if (dcCore::app()->auth->userID()) {
                $or[] = 'user_id = ' . $sql->quote(dcCore::app()->auth->userID());
            }
            $sql->and($sql->orGroup($or));
        }

        if (isset($params['post_type'])) {
            if ($params['post_type'] != '') {
                $sql->and('post_type = ' . $sql->quote($params['post_type']));
            }
        } else {
            $sql->and('post_type = ' . $sql->quote('post'));
        }

        if (isset($params['lang'])) {
            $sql->and('post_lang = ' . $sql->quote($params['lang']));
        }

        $sql->group('post_lang');

        $order = 'desc';
        if (!empty($params['order']) && preg_match('/^(desc|asc)$/i', $params['order'])) {
            $order = $params['order'];
        }
        $sql->order('post_lang ' . $order);

        return $this->con->select($sql->statement());
    }

    /**
     * Returns a record with all distinct blog dates and post count.
     * <var>$params</var> is an array taking the following optionnal parameters:
     *
     * - type: (day|month|year) Get days, months or years
     * - year: (integer) Get dates for given year
     * - month: (integer) Get dates for given month
     * - day: (integer) Get dates for given day
     * - cat_id: (integer) Category ID filter
     * - cat_url: Category URL filter
     * - post_lang: lang of the posts
     * - next: Get date following match
     * - previous: Get date before match
     * - order: Sort by date "ASC" or "DESC"
     *
     * @param      array   $params  The parameters
     *
     * @return     record  The dates.
     */
    public function getDates($params = [])
    {
        $dt_f  = '%Y-%m-%d';
        $dt_fc = '%Y%m%d';
        if (isset($params['type'])) {
            if ($params['type'] == 'year') {
                $dt_f  = '%Y-01-01';
                $dt_fc = '%Y0101';
            } elseif ($params['type'] == 'month') {
                $dt_f  = '%Y-%m-01';
                $dt_fc = '%Y%m01';
            }
        }
        $dt_f  .= ' 00:00:00';
        $dt_fc .= '000000';

        $sql = new dcSelectStatement();
        $sql
            ->distinct()
            ->columns([
                $sql->dateFormat('post_dt', $dt_f) . ' AS dt',
                $sql->count('P.post_id', 'nb_post'),
            ])
            ->from($this->prefix . 'post P')
            ->join(
                (new dcJoinStatement())
                    ->left()
                    ->from($this->prefix . 'category C')
                    ->on('P.cat_id = C.cat_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $sql->quote($this->id))
            ->group('dt');

        if (isset($params['cat_id']) && $params['cat_id'] !== '') {
            $sql->and('P.cat_id = ' . (int) $params['cat_id']);
            $sql->column('C.cat_url');
            $sql->group('C.cat_url');
        } elseif (isset($params['cat_url']) && $params['cat_url'] !== '') {
            $sql->and('C.cat_url = ' . $sql->quote($params['cat_id']));
            $sql->column('C.cat_url');
            $sql->group('C.cat_url');
        }
        if (!empty($params['post_lang'])) {
            $sql->and('P.post_lang = ' . $sql->quote($params['post_lang']));
        }

        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $and = ['post_status = 1'];
            if ($this->without_password) {
                $and[] = 'post_password IS NULL';
            }
            $or = [$sql->andGroup($and)];
            if (dcCore::app()->auth->userID()) {
                $or[] = 'P.user_id = ' . $sql->quote(dcCore::app()->auth->userID());
            }
            $sql->and($sql->orGroup($or));
        }

        if (!empty($params['post_type'])) {
            $sql->and('post_type' . $sql->in($params['post_type']));
        } else {
            $sql->and('post_type = ' . $sql->quote('post'));
        }

        if (!empty($params['year'])) {
            $sql->and($sql->dateFormat('post_dt', '%Y') . ' = ' . $sql->quote(sprintf('%04d', $params['year'])));
        }

        if (!empty($params['month'])) {
            $sql->and($sql->dateFormat('post_dt', '%m') . ' = ' . $sql->quote(sprintf('%02d', $params['month'])));
        }

        if (!empty($params['day'])) {
            $sql->and($sql->dateFormat('post_dt', '%d') . ' = ' . $sql->quote(sprintf('%02d', $params['day'])));
        }

        # Get next or previous date
        if (!empty($params['next']) || !empty($params['previous'])) {
            if (!empty($params['next'])) {
                $pdir            = ' > ';
                $params['order'] = 'asc';
                $dt              = $params['next'];
            } else {
                $pdir            = ' < ';
                $params['order'] = 'desc';
                $dt              = $params['previous'];
            }

            $dt = date('YmdHis', strtotime($dt));

            $sql->and($sql->dateFormat('post_dt', $dt_fc) . $pdir . $sql->quote($dt));
            $sql->limit(1);
        }

        $order = 'desc';
        if (!empty($params['order']) && preg_match('/^(desc|asc)$/i', $params['order'])) {
            $order = $params['order'];
        }
        $sql->order('dt ' . $order);

        $rs = $sql->select();
        $rs->extend('rsExtDates');

        return $rs;
    }

    /**
     * Creates a new entry. Takes a cursor as input and returns the new entry ID.
     *
     * @param      cursor     $cur    The post cursor
     *
     * @throws     Exception
     *
     * @return     integer
     */
    public function addPost($cur)
    {
        if (!dcCore::app()->auth->check('usage,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to create an entry'));
        }

        $this->con->writeLock($this->prefix . 'post');

        try {
            # Get ID
            $sql = new dcSelectStatement();
            $sql
                ->column($sql->max('post_id'))
                ->from($this->prefix . 'post');
            $rs = $sql->select();

            $cur->post_id     = (int) $rs->f(0) + 1;
            $cur->blog_id     = (string) $this->id;
            $cur->post_creadt = date('Y-m-d H:i:s');
            $cur->post_upddt  = date('Y-m-d H:i:s');
            $cur->post_tz     = dcCore::app()->auth->getInfo('user_tz');

            # Post excerpt and content
            $this->getPostContent($cur, $cur->post_id);

            $this->getPostCursor($cur);

            $cur->post_url = $this->getPostURL($cur->post_url, $cur->post_dt, $cur->post_title, $cur->post_id);

            if (!dcCore::app()->auth->check('publish,contentadmin', $this->id)) {
                $cur->post_status = -2;
            }

            # --BEHAVIOR-- coreBeforePostCreate
            dcCore::app()->callBehavior('coreBeforePostCreate', $this, $cur);

            $cur->insert();
            $this->con->unlock();
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- coreAfterPostCreate
        dcCore::app()->callBehavior('coreAfterPostCreate', $this, $cur);

        $this->triggerBlog();

        $this->firstPublicationEntries($cur->post_id);

        return $cur->post_id;
    }

    /**
     * Updates an existing post.
     *
     * @param      integer     $id     The post identifier
     * @param      cursor      $cur    The post cursor
     *
     * @throws     Exception
     */
    public function updPost($id, $cur)
    {
        if (!dcCore::app()->auth->check('usage,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to update entries'));
        }

        $id = (int) $id;

        if (empty($id)) {
            throw new Exception(__('No such entry ID'));
        }

        # Post excerpt and content
        $this->getPostContent($cur, $id);

        $this->getPostCursor($cur);

        if ($cur->post_url !== null) {
            $cur->post_url = $this->getPostURL($cur->post_url, $cur->post_dt, $cur->post_title, $id);
        }

        if (!dcCore::app()->auth->check('publish,contentadmin', $this->id)) {
            $cur->unsetField('post_status');
        }

        $cur->post_upddt = date('Y-m-d H:i:s');

        #If user is only "usage", we need to check the post's owner
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sql = new dcSelectStatement();
            $sql
                ->column('post_id')
                ->from($this->prefix . 'post')
                ->where('post_id = ' . (int) $id)
                ->and('user_id = ' . $sql->quote(dcCore::app()->auth->userID()));

            if ($sql->select()->isEmpty()) {
                throw new Exception(__('You are not allowed to edit this entry'));
            }
        }

        # --BEHAVIOR-- coreBeforePostUpdate
        dcCore::app()->callBehavior('coreBeforePostUpdate', $this, $cur);

        $cur->update('WHERE post_id = ' . $id . ' ');

        # --BEHAVIOR-- coreAfterPostUpdate
        dcCore::app()->callBehavior('coreAfterPostUpdate', $this, $cur);

        $this->triggerBlog();

        $this->firstPublicationEntries($id);
    }

    /**
     * Update post status.
     *
     * @param      integer  $id      The identifier
     * @param      integer  $status  The status
     */
    public function updPostStatus($id, $status)
    {
        $this->updPostsStatus($id, $status);
    }

    /**
     * Updates posts status.
     *
     * @param      mixed       $ids     The identifiers
     * @param      integer     $status  The status
     *
     * @throws     Exception
     */
    public function updPostsStatus($ids, $status)
    {
        if (!dcCore::app()->auth->check('publish,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to change this entry status'));
        }

        $posts_ids = dcUtils::cleanIds($ids);
        $status    = (int) $status;

        $sql = new dcUpdateStatement();
        $sql
            ->where('blog_id = ' . $sql->quote($this->id))
            ->and('post_id' . $sql->in($posts_ids));

        #If user can only publish, we need to check the post's owner
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sql->and('user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $cur = $this->con->openCursor($this->prefix . 'post');

        $cur->post_status = $status;
        $cur->post_upddt  = date('Y-m-d H:i:s');

        $sql->update($cur);
        $this->triggerBlog();

        $this->firstPublicationEntries($posts_ids);
    }

    /**
     * Updates post selection.
     *
     * @param      integer  $id        The identifier
     * @param      mixed    $selected  The selected flag
     */
    public function updPostSelected($id, $selected)
    {
        $this->updPostsSelected($id, $selected);
    }

    /**
     * Updates posts selection.
     *
     * @param      mixed      $ids       The identifiers
     * @param      mixed      $selected  The selected flag
     *
     * @throws     Exception
     */
    public function updPostsSelected($ids, $selected)
    {
        if (!dcCore::app()->auth->check('usage,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to change this entry category'));
        }

        $posts_ids = dcUtils::cleanIds($ids);
        $selected  = (bool) $selected;

        $sql = new dcUpdateStatement();
        $sql
            ->where('blog_id = ' . $sql->quote($this->id))
            ->and('post_id' . $sql->in($posts_ids));

        # If user is only usage, we need to check the post's owner
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sql->and('user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $cur = $this->con->openCursor($this->prefix . 'post');

        $cur->post_selected = (int) $selected;
        $cur->post_upddt    = date('Y-m-d H:i:s');

        $sql->update($cur);
        $this->triggerBlog();
    }

    /**
     * Updates post category. <var>$cat_id</var> can be null.
     *
     * @param      integer  $id      The identifier
     * @param      mixed    $cat_id  The cat identifier
     */
    public function updPostCategory($id, $cat_id)
    {
        $this->updPostsCategory($id, $cat_id);
    }

    /**
     * Updates posts category. <var>$cat_id</var> can be null.
     *
     * @param      mixed      $ids     The identifiers
     * @param      mixed      $cat_id  The cat identifier
     *
     * @throws     Exception
     */
    public function updPostsCategory($ids, $cat_id)
    {
        if (!dcCore::app()->auth->check('usage,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to change this entry category'));
        }

        $posts_ids = dcUtils::cleanIds($ids);
        $cat_id    = (int) $cat_id;

        $sql = new dcUpdateStatement();
        $sql
            ->where('blog_id = ' . $sql->quote($this->id))
            ->and('post_id' . $sql->in($posts_ids));

        # If user is only usage, we need to check the post's owner
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sql->and('user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $cur = $this->con->openCursor($this->prefix . 'post');

        $cur->cat_id     = ($cat_id ?: null);
        $cur->post_upddt = date('Y-m-d H:i:s');

        $sql->update($cur);
        $this->triggerBlog();
    }

    /**
     * Updates posts category. <var>$new_cat_id</var> can be null.
     *
     * @param      mixed    $old_cat_id  The old cat identifier
     * @param      mixed    $new_cat_id  The new cat identifier
     *
     * @throws     Exception
     */
    public function changePostsCategory($old_cat_id, $new_cat_id)
    {
        if (!dcCore::app()->auth->check('contentadmin,categories', $this->id)) {
            throw new Exception(__('You are not allowed to change entries category'));
        }

        $old_cat_id = (int) $old_cat_id;
        $new_cat_id = (int) $new_cat_id;

        $sql = new dcUpdateStatement();
        $sql
            ->where('blog_id = ' . $sql->quote($this->id))
            ->and('cat_id = ' . (int) $old_cat_id);

        $cur = $this->con->openCursor($this->prefix . 'post');

        $cur->cat_id     = ($new_cat_id ?: null);
        $cur->post_upddt = date('Y-m-d H:i:s');

        $sql->update($cur);
        $this->triggerBlog();
    }

    /**
     * Deletes a post.
     *
     * @param      integer  $id     The post identifier
     */
    public function delPost($id)
    {
        $this->delPosts($id);
    }

    /**
     * Deletes multiple posts.
     *
     * @param      mixed     $ids    The posts identifiers
     *
     * @throws     Exception
     */
    public function delPosts($ids)
    {
        if (!dcCore::app()->auth->check('delete,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to delete entries'));
        }

        $posts_ids = dcUtils::cleanIds($ids);

        if (empty($posts_ids)) {
            throw new Exception(__('No such entry ID'));
        }

        $sql = new dcDeleteStatement();
        $sql
            ->from($this->prefix . 'post')
            ->where('blog_id = ' . $sql->quote($this->id))
            ->and('post_id' . $sql->in($posts_ids));

        #If user can only delete, we need to check the post's owner
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sql->and('user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $sql->delete();
        $this->triggerBlog();
    }

    /**
     * Publishes all entries flaged as "scheduled".
     */
    public function publishScheduledEntries()
    {
        $sql = new dcSelectStatement();
        $sql
            ->columns([
                'post_id',
                'post_dt',
                'post_tz',
            ])
            ->from($this->prefix . 'post')
            ->where('post_status = -1')
            ->and('blog_id = ' . $sql->quote($this->id));

        $rs = $sql->select();

        if ($rs->isEmpty()) {
            return;
        }

        $now       = dt::toUTC(time());
        $to_change = new ArrayObject();

        while ($rs->fetch()) {
            # Now timestamp with post timezone
            $now_tz = $now + dt::getTimeOffset($rs->post_tz, $now);

            # Post timestamp
            $post_ts = strtotime($rs->post_dt);

            # If now_tz >= post_ts, we publish the entry
            if ($now_tz >= $post_ts) {
                $to_change[] = (int) $rs->post_id;
            }
        }
        if (count($to_change)) {
            # --BEHAVIOR-- coreBeforeScheduledEntriesPublish
            dcCore::app()->callBehavior('coreBeforeScheduledEntriesPublish', $this, $to_change);

            $sql = new dcUpdateStatement();
            $sql
                ->ref($this->prefix . 'post')
                ->set('post_status = 1')
                ->where('blog_id = ' . $sql->quote($this->id))
                ->and('post_id' . $sql->in([...$to_change]));

            $sql->update();
            $this->triggerBlog();

            # --BEHAVIOR-- coreAfterScheduledEntriesPublish
            dcCore::app()->callBehavior('coreAfterScheduledEntriesPublish', $this, $to_change);

            $this->firstPublicationEntries($to_change);
        }
    }

    /**
     * First publication mecanism (on post create, update, publish, status)
     *
     * @param      mixed  $ids    The posts identifiers
     */
    public function firstPublicationEntries($ids)
    {
        $posts = $this->getPosts([
            'post_id'       => dcUtils::cleanIds($ids),
            'post_status'   => 1,
            'post_firstpub' => 0,
        ]);

        $to_change = [];
        while ($posts->fetch()) {
            $to_change[] = $posts->post_id;
        }

        if (count($to_change)) {
            $sql = new dcUpdateStatement();
            $sql
                ->ref($this->prefix . 'post')
                ->set('post_firstpub = 1')
                ->where('blog_id = ' . $sql->quote($this->id))
                ->and('post_id' . $sql->in([...$to_change]));

            $sql->update();

            # --BEHAVIOR-- coreFirstPublicationEntries
            dcCore::app()->callBehavior('coreFirstPublicationEntries', $this, $to_change);
        }
    }

    /**
     * Retrieves all users having posts on current blog.
     *
     * @param    string     $post_type post_type filter (post)
     *
     * @return    record
     */
    public function getPostsUsers($post_type = 'post')
    {
        $sql = new dcSelectStatement();
        $sql
            ->columns([
                'P.user_id',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
            ])
            ->from([
                $this->prefix . 'post P',
                $this->prefix . 'user U',
            ])
            ->where('P.user_id = U.user_id')
            ->and('blog_id = ' . $sql->quote($this->id))
            ->group([
                'P.user_id',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
            ]);

        if ($post_type) {
            $sql->and('post_type = ' . $sql->quote($post_type));
        }

        return $sql->select();
    }

    private function getPostsCategoryFilter($arr, $field = 'cat_id')
    {
        $field = $field == 'cat_id' ? 'cat_id' : 'cat_url';

        $sub     = [];
        $not     = [];
        $queries = [];

        foreach ($arr as $v) {
            $v    = trim((string) $v);
            $args = preg_split('/\s*[?]\s*/', $v, -1, PREG_SPLIT_NO_EMPTY);
            $id   = array_shift($args);
            $args = array_flip($args);

            if (isset($args['not'])) {
                $not[$id] = 1;
            }
            if (isset($args['sub'])) {
                $sub[$id] = 1;
            }
            if ($field == 'cat_id') {
                if (preg_match('/^null$/i', $id)) {
                    $queries[$id] = 'P.cat_id IS NULL';
                } else {
                    $queries[$id] = 'P.cat_id = ' . (int) $id;
                }
            } else {
                $queries[$id] = "C.cat_url = '" . $this->con->escape($id) . "' ";
            }
        }

        if (!empty($sub)) {
            $sql = new dcSelectStatement();
            $sql
                ->columns([
                    'cat_id',
                    'cat_url',
                    'cat_lft',
                    'cat_rgt',
                ])
                ->from($this->prefix . 'category')
                ->where('blog_id = ' . $sql->quote($this->id))
                ->and($field . $sql->in(array_keys($sub)));

            $rs = $sql->select();

            while ($rs->fetch()) {
                $queries[$rs->f($field)] = '(C.cat_lft BETWEEN ' . $rs->cat_lft . ' AND ' . $rs->cat_rgt . ')';
            }
        }

        # Create queries
        $sql = [
            0 => [], # wanted categories
            1 => [], # excluded categories
        ];

        foreach ($queries as $id => $q) {
            $sql[(int) isset($not[$id])][] = $q;
        }

        $sql[0] = implode(' OR ', $sql[0]);
        $sql[1] = implode(' OR ', $sql[1]);

        if ($sql[0]) {
            $sql[0] = '(' . $sql[0] . ')';
        } else {
            unset($sql[0]);
        }

        if ($sql[1]) {
            $sql[1] = '(P.cat_id IS NULL OR NOT(' . $sql[1] . '))';
        } else {
            unset($sql[1]);
        }

        return implode(' AND ', $sql);
    }

    /**
     * Gets the post cursor.
     *
     * @param      cursor      $cur      The post cursor
     * @param      integer     $post_id  The post identifier
     *
     * @throws     Exception
     */
    private function getPostCursor($cur, $post_id = null)
    {
        if ($cur->post_title == '') {
            throw new Exception(__('No entry title'));
        }

        if ($cur->post_content == '') {
            throw new Exception(__('No entry content'));
        }

        if ($cur->post_password === '') {
            $cur->post_password = null;
        }

        if ($cur->post_dt == '') {
            $offset       = dt::getTimeOffset(dcCore::app()->auth->getInfo('user_tz'));
            $now          = time() + $offset;
            $cur->post_dt = date('Y-m-d H:i:00', $now);
        }

        $post_id = is_int($post_id) ? $post_id : $cur->post_id;

        if ($cur->post_content_xhtml == '') {
            throw new Exception(__('No entry content'));
        }

        # Words list
        if (
            $cur->post_title !== null && $cur->post_excerpt_xhtml !== null
            && $cur->post_content_xhtml !== null
        ) {
            $words = $cur->post_title . ' ' .
                $cur->post_excerpt_xhtml . ' ' .
                $cur->post_content_xhtml;

            $cur->post_words = implode(' ', text::splitWords($words));
        }

        if ($cur->isField('post_firstpub')) {
            $cur->unsetField('post_firstpub');
        }
    }

    /**
     * Gets the post content.
     *
     * @param      cursor  $cur      The post cursor
     * @param      integer $post_id  The post identifier
     */
    private function getPostContent($cur, $post_id)
    {
        $post_excerpt       = $cur->post_excerpt;
        $post_excerpt_xhtml = $cur->post_excerpt_xhtml;
        $post_content       = $cur->post_content;
        $post_content_xhtml = $cur->post_content_xhtml;

        $this->setPostContent(
            $post_id,
            $cur->post_format,
            $cur->post_lang,
            $post_excerpt,
            $post_excerpt_xhtml,
            $post_content,
            $post_content_xhtml
        );

        $cur->post_excerpt       = $post_excerpt;
        $cur->post_excerpt_xhtml = $post_excerpt_xhtml;
        $cur->post_content       = $post_content;
        $cur->post_content_xhtml = $post_content_xhtml;
    }

    /**
     * Creates post HTML content, taking format and lang into account.
     *
     * @param      integer  $post_id        The post identifier
     * @param      string   $format         The format
     * @param      string   $lang           The language
     * @param      string   $excerpt        The excerpt
     * @param      string   $excerpt_xhtml  The excerpt xhtml
     * @param      string   $content        The content
     * @param      string   $content_xhtml  The content xhtml
     */
    public function setPostContent($post_id, $format, $lang, &$excerpt, &$excerpt_xhtml, &$content, &$content_xhtml)
    {
        if ($format == 'wiki') {
            dcCore::app()->initWikiPost();
            dcCore::app()->wiki2xhtml->setOpt('note_prefix', 'pnote-' . $post_id);
            switch ($this->settings->system->note_title_tag) {
                case 1:
                    $tag = 'h3';

                    break;
                case 2:
                    $tag = 'p';

                    break;
                default:
                    $tag = 'h4';

                    break;
            }
            dcCore::app()->wiki2xhtml->setOpt('note_str', '<div class="footnotes"><' . $tag . ' class="footnotes-title">' .
                __('Notes') . '</' . $tag . '>%s</div>');
            dcCore::app()->wiki2xhtml->setOpt('note_str_single', '<div class="footnotes"><' . $tag . ' class="footnotes-title">' .
                __('Note') . '</' . $tag . '>%s</div>');
            if (strpos($lang, 'fr') === 0) {
                dcCore::app()->wiki2xhtml->setOpt('active_fr_syntax', 1);
            }
        }

        if ($excerpt) {
            $excerpt_xhtml = dcCore::app()->callFormater($format, $excerpt);
            $excerpt_xhtml = dcCore::app()->HTMLfilter($excerpt_xhtml);
        } else {
            $excerpt_xhtml = '';
        }

        if ($content) {
            $content_xhtml = dcCore::app()->callFormater($format, $content);
            $content_xhtml = dcCore::app()->HTMLfilter($content_xhtml);
        } else {
            $content_xhtml = '';
        }

        # --BEHAVIOR-- coreAfterPostContentFormat
        dcCore::app()->callBehavior('coreAfterPostContentFormat', [
            'excerpt'       => &$excerpt,
            'content'       => &$content,
            'excerpt_xhtml' => &$excerpt_xhtml,
            'content_xhtml' => &$content_xhtml,
        ]);
    }

    /**
     * Returns URL for a post according to blog setting <var>post_url_format</var>.
     * It will try to guess URL and append some figures if needed.
     *
     * @param      string   $url         The url
     * @param      string   $post_dt     The post dt
     * @param      string   $post_title  The post title
     * @param      integer  $post_id     The post identifier
     *
     * @return     string  The post url.
     */
    public function getPostURL($url, $post_dt, $post_title, $post_id)
    {
        $url = trim((string) $url);

        $url_patterns = [
            '{y}'  => date('Y', strtotime($post_dt)),
            '{m}'  => date('m', strtotime($post_dt)),
            '{d}'  => date('d', strtotime($post_dt)),
            '{t}'  => text::tidyURL($post_title),
            '{id}' => (int) $post_id,
        ];

        # If URL is empty, we create a new one
        if ($url == '') {
            # Transform with format
            $url = str_replace(
                array_keys($url_patterns),
                array_values($url_patterns),
                $this->settings->system->post_url_format
            );
        } else {
            $url = text::tidyURL($url);
        }

        # Let's check if URL is taken...
        $sql = new dcSelectStatement();
        $sql
            ->column('post_url')
            ->from($this->prefix . 'post')
            ->where('post_url = ' . $sql->quote($url))
            ->and('post_id <> ' . (int) $post_id)
            ->and('blog_id = ' . $sql->quote($this->id))
            ->order('post_url DESC');

        $rs = $sql->select();

        if (!$rs->isEmpty()) {
            $sql = new dcSelectStatement();
            $sql
                ->column('post_url')
                ->from($this->prefix . 'post')
                ->where('post_url' . $sql->regexp($url))
                ->and('post_id <> ' . (int) $post_id)
                ->and('blog_id = ' . $sql->quote($this->id))
                ->order('post_url DESC');

            $rs = $sql->select();
            $a  = [];
            while ($rs->fetch()) {
                $a[] = $rs->post_url;
            }

            natsort($a);
            $t_url = end($a);

            if (preg_match('/(.*?)([0-9]+)$/', $t_url, $m)) {
                $i   = (int) $m[2];
                $url = $m[1];
            } else {
                $i = 1;
            }

            return $url . ($i + 1);
        }

        # URL is empty?
        if ($url == '') {
            throw new Exception(__('Empty entry URL'));
        }

        return $url;
    }
    //@}

    /// @name Comments management methods
    //@{
    /**
     * Retrieves comments. <b>$params</b> is an array taking the following
     * optionnal parameters:
     *
     * - no_content: Don't retrieve comment content
     * - post_type: Get only entries with given type (default no type, array for many types)
     * - post_id: (integer) Get comments belonging to given post_id
     * - cat_id: (integer or array) Get comments belonging to entries of given category ID
     * - comment_id: (integer or array) Get comment with given ID (or IDs)
     * - comment_site: (string) Get comments with given comment_site
     * - comment_status: (integer) Get comments with given comment_status
     * - comment_trackback: (integer) Get only comments (0) or trackbacks (1)
     * - comment_ip: (string) Get comments with given IP address
     * - post_url: Get entry with given post_url field
     * - user_id: (integer) Get entries belonging to given user ID
     * - q_author: Search comments by author
     * - sql: Append SQL string at the end of the query
     * - from: Append SQL string after "FROM" statement in query
     * - order: Order of results (default "ORDER BY comment_dt DES")
     * - limit: Limit parameter
     * - sql_only : return the sql request instead of results. Only ids are selected
     *
     * @param    array              $params        Parameters
     * @param    bool               $count_only    Only counts results
     * @param    dcSelectStatement  $ext_sql       Optional dcSelectStatement instance
     *
     * @return   mixed      A record with some more capabilities
     */
    public function getComments($params = [], $count_only = false, ?dcSelectStatement $ext_sql = null)
    {
        $sql = $ext_sql ? clone $ext_sql : new dcSelectStatement();

        if ($count_only) {
            $sql->column($sql->count('comment_id'));
        } elseif (!empty($params['sql_only'])) {
            $sql->column('P.post_id');
        } else {
            if (empty($params['no_content'])) {
                $sql->column('comment_content');
            }

            if (!empty($params['columns']) && is_array($params['columns'])) {
                $sql->columns($params['columns']);
            }

            $sql->columns([
                'C.comment_id',
                'comment_dt',
                'comment_tz',
                'comment_upddt',
                'comment_author',
                'comment_email',
                'comment_site',
                'comment_trackback',
                'comment_status',
                'comment_spam_status',
                'comment_spam_filter',
                'comment_ip',
                'P.post_title',
                'P.post_url',
                'P.post_id',
                'P.post_password',
                'P.post_type',
                'P.post_dt',
                'P.user_id',
                'U.user_email',
                'U.user_url',
            ]);
        }

        $sql
            ->from($this->prefix . 'comment C')
            ->join(
                (new dcJoinStatement())
                    ->inner()
                    ->from($this->prefix . 'post P')
                    ->on('C.post_id = P.post_id')
                    ->statement()
            )
            ->join(
                (new dcJoinStatement())
                    ->inner()
                    ->from($this->prefix . 'user U')
                    ->on('P.user_id = U.user_id')
                    ->statement()
            );

        if (!empty($params['from'])) {
            $sql->from($params['from']);
        }

        if (!empty($params['where'])) {
            // Cope with legacy code
            $sql->where($params['where']);
        } else {
            $sql->where('P.blog_id = ' . $sql->quote($this->id));
        }

        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $user_id = dcCore::app()->auth->userID();

            $and = [
                'comment_status = 1',
                'P.post_status = 1',
            ];

            if ($this->without_password) {
                $and[] = 'post_password IS NULL';
            }

            $or = [$sql->andGroup($and)];
            if ($user_id) {
                $or[] = 'P.user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($or));
        }

        if (!empty($params['post_type'])) {
            $sql->and('post_type' . $sql->in($params['post_type']));
        }

        if (isset($params['post_id']) && $params['post_id'] !== '') {
            $sql->and('P.post_id = ' . (int) $params['post_id']);
        }

        if (isset($params['cat_id']) && $params['cat_id'] !== '') {
            $sql->and('P.cat_id = ' . (int) $params['cat_id']);
        }

        if (isset($params['comment_id']) && $params['comment_id'] !== '') {
            if (is_array($params['comment_id'])) {
                array_walk($params['comment_id'], function (&$v, $k) {
                    if ($v !== null) {
                        $v = (int) $v;
                    }
                });
            } else {
                $params['comment_id'] = [(int) $params['comment_id']];
            }
            $sql->and('comment_id' . $sql->in($params['comment_id']));
        }

        if (isset($params['comment_email'])) {
            $comment_email = $sql->escape(str_replace('*', '%', $params['comment_email']));
            $sql->and($sql->like('comment_email', $comment_email));
        }

        if (isset($params['comment_site'])) {
            $comment_site = $sql->escape(str_replace('*', '%', $params['comment_site']));
            $sql->and($sql->like('comment_site', $comment_site));
        }

        if (isset($params['comment_status'])) {
            $sql->and('comment_status = ' . (int) $params['comment_status']);
        }

        if (!empty($params['comment_status_not'])) {
            $sql->and('comment_status <> ' . (int) $params['comment_status_not']);
        }

        if (isset($params['comment_trackback'])) {
            $sql->and('comment_trackback = ' . (int) (bool) $params['comment_trackback']);
        }

        if (isset($params['comment_ip'])) {
            $comment_ip = $sql->escape(str_replace('*', '%', $params['comment_ip']));
            $sql->and($sql->like('comment_ip', $comment_ip));
        }

        if (isset($params['q_author'])) {
            $q_author = $sql->escape(str_replace('*', '%', strtolower($params['q_author'])));
            $sql->and($sql->like('LOWER(comment_author)', $q_author));
        }

        if (!empty($params['search'])) {
            $words = text::splitWords($params['search']);

            if (!empty($words)) {
                # --BEHAVIOR coreCommentSearch
                if (dcCore::app()->hasBehavior('coreCommentSearch')) {
                    dcCore::app()->callBehavior('coreCommentSearch', dcCore::app(), [&$words, &$sql, &$params]);
                }

                foreach ($words as $i => $w) {
                    $words[$i] = $sql->like('comment_words', $w);
                }
                $sql->and($words);
            }
        }

        if (!empty($params['sql'])) {
            $sql->sql($params['sql']);
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('comment_dt DESC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        if (!empty($params['sql_only'])) {
            return $sql->statement();
        }

        $rs       = $sql->select();
        $rs->core = dcCore::app();
        $rs->extend('rsExtComment');

        # --BEHAVIOR-- coreBlogGetComments
        dcCore::app()->callBehavior('coreBlogGetComments', $rs);

        return $rs;
    }

    /**
     * Creates a new comment. Takes a cursor as input and returns the new comment ID.
     *
     * @param      cursor  $cur    The comment cursor
     *
     * @return     integer
     */
    public function addComment($cur)
    {
        $this->con->writeLock($this->prefix . 'comment');

        try {
            # Get ID
            $sql = new dcSelectStatement();
            $sql
                ->column($sql->max('comment_id'))
                ->from($this->prefix, 'comment');

            $rs = $sql->select();

            $cur->comment_id    = (int) $rs->f(0) + 1;
            $cur->comment_upddt = date('Y-m-d H:i:s');

            $offset          = dt::getTimeOffset($this->settings->system->blog_timezone);
            $cur->comment_dt = date('Y-m-d H:i:s', time() + $offset);
            $cur->comment_tz = $this->settings->system->blog_timezone;

            $this->getCommentCursor($cur);

            if ($cur->comment_ip === null) {
                $cur->comment_ip = http::realIP();
            }

            # --BEHAVIOR-- coreBeforeCommentCreate
            dcCore::app()->callBehavior('coreBeforeCommentCreate', $this, $cur);

            $cur->insert();
            $this->con->unlock();
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- coreAfterCommentCreate
        dcCore::app()->callBehavior('coreAfterCommentCreate', $this, $cur);

        $this->triggerComment($cur->comment_id);
        if ($cur->comment_status != -2) {
            $this->triggerBlog();
        }

        return $cur->comment_id;
    }

    /**
     * Updates an existing comment.
     *
     * @param      integer     $id     The comment identifier
     * @param      cursor      $cur    The comment cursor
     *
     * @throws     Exception
     */
    public function updComment($id, $cur)
    {
        if (!dcCore::app()->auth->check('usage,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to update comments'));
        }

        $id = (int) $id;

        if (empty($id)) {
            throw new Exception(__('No such comment ID'));
        }

        $rs = $this->getComments(['comment_id' => $id]);

        if ($rs->isEmpty()) {
            throw new Exception(__('No such comment ID'));
        }

        #If user is only usage, we need to check the post's owner
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            if ($rs->user_id != dcCore::app()->auth->userID()) {
                throw new Exception(__('You are not allowed to update this comment'));
            }
        }

        $this->getCommentCursor($cur);

        $cur->comment_upddt = date('Y-m-d H:i:s');

        if (!dcCore::app()->auth->check('publish,contentadmin', $this->id)) {
            $cur->unsetField('comment_status');
        }

        # --BEHAVIOR-- coreBeforeCommentUpdate
        dcCore::app()->callBehavior('coreBeforeCommentUpdate', $this, $cur, $rs);

        $sql = new dcUpdateStatement();
        $sql->where('comment_id = ' . $id);

        $sql->update($cur);

        # --BEHAVIOR-- coreAfterCommentUpdate
        dcCore::app()->callBehavior('coreAfterCommentUpdate', $this, $cur, $rs);

        $this->triggerComment($id);
        $this->triggerBlog();
    }

    /**
     * Updates comment status.
     *
     * @param      integer  $id      The comment identifier
     * @param      mixed    $status  The comment status
     */
    public function updCommentStatus($id, $status)
    {
        $this->updCommentsStatus($id, $status);
    }

    /**
     * Updates comments status.
     *
     * @param      mixed      $ids     The identifiers
     * @param      mixed      $status  The status
     *
     * @throws     Exception
     */
    public function updCommentsStatus($ids, $status)
    {
        if (!dcCore::app()->auth->check('publish,contentadmin', $this->id)) {
            throw new Exception(__("You are not allowed to change this comment's status"));
        }

        $co_ids = dcUtils::cleanIds($ids);
        $status = (int) $status;

        $sql = new dcUpdateStatement();
        $sql
            ->ref($this->prefix . 'comment')
            ->set('comment_status = ' . $status)
            ->where('comment_id' . $sql->in($co_ids));

        $sqlIn = new dcSelectStatement();
        $sqlIn
            ->column('tp.post_id')
            ->from($this->prefix . 'post tp')
            ->where('tp.blog_id = ' . $sqlIn->quote($this->id));
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sqlIn->and('tp.user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $sql->and($sql->inSelect('post_id', $sqlIn));

        $sql->update();
        $this->triggerComments($co_ids);
        $this->triggerBlog();
    }

    /**
     * Delete a comment.
     *
     * @param      integer  $id     The comment identifier
     */
    public function delComment($id)
    {
        $this->delComments($id);
    }

    /**
     * Delete comments.
     *
     * @param      mixed     $ids    The comments identifiers
     *
     * @throws     Exception
     */
    public function delComments($ids)
    {
        if (!dcCore::app()->auth->check('delete,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to delete comments'));
        }

        $co_ids = dcUtils::cleanIds($ids);

        if (empty($co_ids)) {
            throw new Exception(__('No such comment ID'));
        }

        # Retrieve posts affected by comments edition
        $affected_posts = [];
        $sql            = new dcSelectStatement();
        $sql
            ->column('post_id')
            ->from($this->prefix . 'comment')
            ->where('comment_id' . $sql->in($co_ids))
            ->group('post_id');

        $rs = $sql->select();
        while ($rs->fetch()) {
            $affected_posts[] = (int) $rs->post_id;
        }

        $sql = new dcDeleteStatement();
        $sql
            ->from($this->prefix . 'comment')
            ->where('comment_id' . $sql->in($co_ids));

        $sqlIn = new dcSelectStatement();
        $sqlIn
            ->column('tp.post_id')
            ->from($this->prefix . 'post tp')
            ->where('tp.blog_id = ' . $sqlIn->quote($this->id));
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sqlIn->and('tp.user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $sql->and($sql->inSelect('post_id', $sqlIn));

        $sql->delete();
        $this->triggerComments($co_ids, true, $affected_posts);
        $this->triggerBlog();
    }

    /**
     * Delete Junk comments
     *
     * @throws     Exception  (description)
     */
    public function delJunkComments()
    {
        if (!dcCore::app()->auth->check('delete,contentadmin', $this->id)) {
            throw new Exception(__('You are not allowed to delete comments'));
        }

        $sql = new dcDeleteStatement();
        $sql
            ->from($this->prefix . 'comment')
            ->where('comment_status = -2');

        $sqlIn = new dcSelectStatement();
        $sqlIn
            ->column('tp.post_id')
            ->from($this->prefix . 'post tp')
            ->where('tp.blog_id = ' . $sqlIn->quote($this->id));
        if (!dcCore::app()->auth->check('contentadmin', $this->id)) {
            $sqlIn->and('tp.user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $sql->and($sql->inSelect('post_id', $sqlIn));

        $sql->delete();
        $this->triggerBlog();
    }

    /**
     * Gets the comment cursor.
     *
     * @param      cursor     $cur    The comment cursor
     *
     * @throws     Exception
     */
    private function getCommentCursor($cur)
    {
        if ($cur->comment_content !== null && $cur->comment_content == '') {
            throw new Exception(__('You must provide a comment'));
        }

        if ($cur->comment_author !== null && $cur->comment_author == '') {
            throw new Exception(__('You must provide an author name'));
        }

        if ($cur->comment_email != '' && !text::isEmail($cur->comment_email)) {
            throw new Exception(__('Email address is not valid.'));
        }

        if ($cur->comment_site !== null && $cur->comment_site != '') {
            if (!preg_match('|^http(s?)://|i', $cur->comment_site, $matches)) {
                $cur->comment_site = 'http://' . $cur->comment_site;
            } else {
                $cur->comment_site = strtolower($matches[0]) . substr($cur->comment_site, strlen($matches[0]));
            }
        }

        if ($cur->comment_status === null) {
            $cur->comment_status = (int) $this->settings->system->comments_pub;
        }

        # Words list
        if ($cur->comment_content !== null) {
            $cur->comment_words = implode(' ', text::splitWords($cur->comment_content));
        }
    }
    //@}
}
