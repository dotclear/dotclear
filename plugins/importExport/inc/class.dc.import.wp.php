<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcImportWP extends dcIeModule
{
    protected $con;
    protected $prefix;
    protected $blog_id;

    protected $action = null;
    protected $step   = 1;

    protected $post_offset = 0;
    protected $post_limit  = 20;
    protected $post_count  = 0;

    protected $has_table = array();

    protected $vars;
    protected $base_vars = array(
        'db_host'            => '',
        'db_name'            => '',
        'db_user'            => '',
        'db_pwd'             => '',
        'db_prefix'          => 'wp_',
        'ignore_first_cat'   => 1,
        'cat_import'         => 1,
        'cat_as_tags'        => '',
        'cat_tags_prefix'    => 'cat: ',
        'post_limit'         => 20,
        'post_formater'      => 'xhtml',
        'comment_formater'   => 'xhtml',
        'user_ids'           => array(),
        'cat_ids'            => array(),
        'permalink_template' => 'p=%post_id%',
        'permalink_tags'     => array(
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            '%postname%',
            '%post_id%',
            '%category%',
            '%author%'
        )
    );
    protected $formaters;

    protected function setInfo()
    {
        $this->type        = 'import';
        $this->name        = __('WordPress import');
        $this->description = __('Import a WordPress installation into your current blog.');
    }

    public function init()
    {
        $this->con     = &$this->core->con;
        $this->prefix  = $this->core->prefix;
        $this->blog_id = $this->core->blog->id;

        if (!isset($_SESSION['wp_import_vars'])) {
            $_SESSION['wp_import_vars'] = $this->base_vars;
        }
        $this->vars = &$_SESSION['wp_import_vars'];

        if ($this->vars['post_limit'] > 0) {
            $this->post_limit = $this->vars['post_limit'];
        }

        $this->formaters = dcAdminCombos::getFormatersCombo();
    }

    public function resetVars()
    {
        $this->vars = $this->base_vars;
        unset($_SESSION['wp_import_vars']);
    }

    public function process($do)
    {
        $this->action = $do;
    }

    # We handle process in another way to always display something to
    # user
    protected function guiprocess($do)
    {
        switch ($do) {
            case 'step1':
                $this->vars['db_host']          = $_POST['db_host'];
                $this->vars['db_name']          = $_POST['db_name'];
                $this->vars['db_user']          = $_POST['db_user'];
                $this->vars['db_pwd']           = $_POST['db_pwd'];
                $this->vars['db_prefix']        = $_POST['db_prefix'];
                $this->vars['ignore_first_cat'] = isset($_POST['ignore_first_cat']);
                $this->vars['cat_import']       = isset($_POST['cat_import']);
                $this->vars['cat_as_tags']      = isset($_POST['cat_as_tags']);
                $this->vars['cat_tags_prefix']  = $_POST['cat_tags_prefix'];
                $this->vars['post_limit']       = abs((integer) $_POST['post_limit']) > 0 ? $_POST['post_limit'] : 0;
                $this->vars['post_formater']    = isset($this->formaters[$_POST['post_formater']]) ? $_POST['post_formater'] : 'xhtml';
                $this->vars['comment_formater'] = isset($this->formaters[$_POST['comment_formater']]) ? $_POST['comment_formater'] : 'xhtml';
                $db                             = $this->db();
                $db->close();
                $this->step = 2;
                echo $this->progressBar(1);
                break;
            case 'step2':
                $this->step = 2;
                $this->importUsers();
                $this->step = 3;
                echo $this->progressBar(3);
                break;
            case 'step3':
                $this->step = 3;
                $this->importCategories();
                if ($this->core->plugins->moduleExists('blogroll')) {
                    $this->step = 4;
                    echo $this->progressBar(5);
                } else {
                    $this->step = 5;
                    echo $this->progressBar(7);
                }
                break;
            case 'step4':
                $this->step = 4;
                $this->importLinks();
                $this->step = 5;
                echo $this->progressBar(7);
                break;
            case 'step5':
                $this->step        = 5;
                $this->post_offset = !empty($_REQUEST['offset']) ? abs((integer) $_REQUEST['offset']) : 0;
                if ($this->importPosts($percent) === -1) {
                    http::redirect($this->getURL() . '&do=ok');
                } else {
                    echo $this->progressBar(ceil($percent * 0.93) + 7);
                }
                break;
            case 'ok':
                $this->resetVars();
                $this->core->blog->triggerBlog();
                $this->step = 6;
                echo $this->progressBar(100);
                break;
        }
    }

    public function gui()
    {
        try {
            $this->guiprocess($this->action);
        } catch (Exception $e) {
            $this->error($e);
        }

        switch ($this->step) {
            case 1:
                echo
                '<p>' . sprintf(__('This will import your WordPress content as new content in the current blog: %s.'),
                    '<strong>' . html::escapeHTML($this->core->blog->name) . '</strong>') . '</p>' .
                '<p class="warning">' . __('Please note that this process ' .
                    'will empty your categories, blogroll, entries and comments on the current blog.') . '</p>';

                printf($this->imForm(1, __('General information'), __('Import my blog now')),
                    '<p>' . __('We first need some information about your old WordPress installation.') . '</p>' .
                    '<p><label for="db_host">' . __('Database Host Name:') . '</label> ' .
                    form::field('db_host', 30, 255, html::escapeHTML($this->vars['db_host'])) . '</p>' .
                    '<p><label for="db_name">' . __('Database Name:', html::escapeHTML($this->vars['db_name'])) . '</label> ' .
                    form::field('db_name', 30, 255, html::escapeHTML($this->vars['db_name'])) . '</p>' .
                    '<p><label for="db_user">' . __('Database User Name:') . '</label> ' .
                    form::field('db_user', 30, 255, html::escapeHTML($this->vars['db_user'])) . '</p>' .
                    '<p><label for="db_pwd">' . __('Database Password:') . '</label> ' .
                    form::password('db_pwd', 30, 255) . '</p>' .
                    '<p><label for="db_prefix">' . __('Database Tables Prefix:') . '</label> ' .
                    form::field('db_prefix', 30, 255, html::escapeHTML($this->vars['db_prefix'])) . '</p>' .

                    '<h3 class="vertical-separator">' . __('Entries import options') . '</h3>' .
                    '<div class="two-cols">' .

                    '<div class="col">' .
                    '<p>' . __('WordPress and Dotclear\'s handling of categories are quite different. ' .
                        'You can assign several categories to a single post in WordPress. In the Dotclear world, ' .
                        'we see it more like "One category, several tags." Therefore Dotclear can only import one ' .
                        'category per post and will chose the lowest numbered one. If you want to keep a trace of ' .
                        'every category, you can import them as tags, with an optional prefix.') . '</p>' .
                    '<p>' . __('On the other hand, in WordPress, a post can not be uncategorized, and a ' .
                        'default installation has a first category labelised <i>"Uncategorized"</i>.' .
                        'If you did not change that category, you can just ignore it while ' .
                        'importing your blog, as Dotclear allows you to actually keep your posts ' .
                        'uncategorized.') . '</p>' .
                    '</div>' .

                    '<div class="col">' .
                    '<p><label for="ignore_first_cat" class="classic">' . form::checkbox('ignore_first_cat', 1, $this->vars['ignore_first_cat']) . ' ' .
                    __('Ignore the first category:') . '</label></p>' .
                    '<p><label for="cat_import" class="classic">' . form::checkbox('cat_import', 1, $this->vars['cat_import']) . ' ' .
                    __('Import lowest numbered category on posts:') . '</label></p>' .
                    '<p><label for="cat_as_tags" class="classic">' . form::checkbox('cat_as_tags', 1, $this->vars['cat_as_tags']) . ' ' .
                    __('Import all categories as tags:') . '</label></p>' .
                    '<p><label for="cat_tags_prefix">' . __('Prefix such tags with:') . '</label> ' .
                    form::field('cat_tags_prefix', 10, 20, html::escapeHTML($this->vars['cat_tags_prefix'])) . '</p>' .
                    '<p><label for="post_limit">' . __('Number of entries to import at once:') . '</label> ' .
                    form::number('post_limit', 0, 999, html::escapeHTML($this->vars['post_limit'])) . '</p>' .
                    '</div>' .

                    '</div>' .

                    '<h3 class="clear vertical-separator">' . __('Content filters') . '</h3>' .
                    '<p>' . __('You may want to process your post and/or comment content with the following filters.') . '</p>' .
                    '<p><label for="post_formater">' . __('Post content formatter:') . '</label> ' .
                    form::combo('post_formater', $this->formaters, $this->vars['post_formater']) . '</p>' .
                    '<p><label for="comment_formater">' . __('Comment content formatter:') . '</label> '
                    . form::combo('comment_formater', $this->formaters, $this->vars['comment_formater']) . '</p>'
                );
                break;
            case 2:
                printf($this->imForm(2, __('Importing users')),
                    $this->autoSubmit()
                );
                break;
            case 3:
                printf($this->imForm(3, __('Importing categories')),
                    $this->autoSubmit()
                );
                break;
            case 4:
                printf($this->imForm(4, __('Importing blogroll')),
                    $this->autoSubmit()
                );
                break;
            case 5:
                $t = sprintf(__('Importing entries from %d to %d / %d'), $this->post_offset,
                    min(array($this->post_offset + $this->post_limit, $this->post_count)), $this->post_count);
                printf($this->imForm(5, $t),
                    form::hidden(array('offset'), $this->post_offset) .
                    $this->autoSubmit()
                );
                break;
            case 6:
                echo
                '<p class="message">' . __('Every newly imported user has received a random password ' .
                    'and will need to ask for a new one by following the "I forgot my password" link on the login page ' .
                    '(Their registered email address has to be valid.)') . '</p>' .
                $this->congratMessage();
                break;
        }
    }

    # Simple form for step by step process
    protected function imForm($step, $legend, $submit_value = null)
    {
        if (!$submit_value) {
            $submit_value = __('next step') . ' >';
        }

        return
        '<form action="' . $this->getURL(true) . '" method="post">' .
        '<h3 class="vertical-separator">' . $legend . '</h3>' .
        '<div>' . $this->core->formNonce() .
        form::hidden(array('do'), 'step' . $step) .
        '%s' . '</div>' .
        '<p><input type="submit" value="' . $submit_value . '" /></p>' .
        '<p class="form-note info">' . __('Depending on the size of your blog, it could take a few minutes.') . '</p>' .
            '</form>';
    }

    # Error display
    protected function error($e)
    {
        echo '<div class="error"><strong>' . __('Errors:') . '</strong>' .
        '<p>' . $e->getMessage() . '</p></div>';
    }

    # Database init
    protected function db()
    {
        $db = dbLayer::init('mysql', $this->vars['db_host'], $this->vars['db_name'], $this->vars['db_user'], $this->vars['db_pwd']);

        $rs = $db->select("SHOW TABLES LIKE '" . $this->vars['db_prefix'] . "%'");
        if ($rs->isEmpty()) {
            throw new Exception(__('WordPress tables not found'));
        }

        while ($rs->fetch()) {
            $this->has_table[$rs->f(0)] = true;
        }

        # Set this to read data as they were written
        try {
            $db->execute('SET NAMES DEFAULT');
        } catch (Exception $e) {}

        $db->execute('SET CHARACTER SET DEFAULT');
        $db->execute("SET COLLATION_CONNECTION = DEFAULT");
        $db->execute("SET COLLATION_SERVER = DEFAULT");
        $db->execute("SET CHARACTER_SET_SERVER = DEFAULT");
        $db->execute("SET CHARACTER_SET_DATABASE = DEFAULT");

        $this->post_count = $db->select(
            'SELECT COUNT(ID) FROM ' . $this->vars['db_prefix'] . 'posts ' .
            'WHERE post_type = \'post\' OR post_type = \'page\''
        )->f(0);

        return $db;
    }

    protected function cleanStr($str)
    {
        return text::cleanUTF8(@text::toUTF8($str));
    }

    # Users import
    protected function importUsers()
    {
        $db     = $this->db();
        $prefix = $this->vars['db_prefix'];
        $rs     = $db->select('SELECT * FROM ' . $prefix . 'users');

        try
        {
            $this->con->begin();

            while ($rs->fetch()) {
                $user_login                      = preg_replace('/[^A-Za-z0-9@._-]/', '-', $rs->user_login);
                $this->vars['user_ids'][$rs->ID] = $user_login;
                if (!$this->core->userExists($user_login)) {
                    $cur                   = $this->con->openCursor($this->prefix . 'user');
                    $cur->user_id          = $user_login;
                    $cur->user_pwd         = crypt::createPassword();
                    $cur->user_displayname = $rs->user_nicename;
                    $cur->user_email       = $rs->user_email;
                    $cur->user_url         = $rs->user_url;
                    $cur->user_creadt      = $rs->user_registered;
                    $cur->user_lang        = $this->core->blog->settings->system->lang;
                    $cur->user_tz          = $this->core->blog->settings->system->blog_timezone;
                    $permissions           = array();

                    $rs_meta = $db->select('SELECT * FROM ' . $prefix . 'usermeta WHERE user_id = ' . $rs->ID);
                    while ($rs_meta->fetch()) {
                        switch ($rs_meta->meta_key) {
                            case 'first_name':
                                $cur->user_firstname = $this->cleanStr($rs_meta->meta_value);
                                break;
                            case 'last_name':
                                $cur->user_name = $this->cleanStr($rs_meta->meta_value);
                                break;
                            case 'description':
                                $cur->user_desc = $this->cleanStr($rs_meta->meta_value);
                                break;
                            case 'rich_editing':
                                $cur->user_options = new ArrayObject(array(
                                    'enable_wysiwyg' => $rs_meta->meta_value == 'true' ? true : false
                                ));
                                break;
                            case 'wp_user_level':
                                switch ($rs_meta->meta_value) {
                                    case '0': # Subscriber
                                        $cur->user_status = 0;
                                        break;
                                    case '1': # Contributor
                                        $permissions['usage']   = true;
                                        $permissions['publish'] = true;
                                        $permissions['delete']  = true;
                                        break;
                                    case '2': # Author
                                    case '3':
                                    case '4':
                                        $permissions['contentadmin'] = true;
                                        $permissions['media']        = true;
                                        break;
                                    case '5': # Editor
                                    case '6':
                                    case '7':
                                        $permissions['contentadmin'] = true;
                                        $permissions['categories']   = true;
                                        $permissions['media_admin']  = true;
                                        $permissions['pages']        = true;
                                        $permissions['blogroll']     = true;
                                        break;
                                    case '8': # Administrator
                                    case '9':
                                    case '10':
                                        $permissions['admin'] = true;
                                        break;
                                }
                                break;
                        }
                    }
                    $this->core->addUser($cur);
                    $this->core->setUserBlogPermissions(
                        $cur->user_id,
                        $this->blog_id,
                        $permissions
                    );
                }
            }
            $this->con->commit();
            $db->close();
        } catch (Exception $e) {
            $this->con->rollback();
            $db->close();
            throw $e;
        }
    }

    # Categories import
    protected function importCategories()
    {
        $db     = $this->db();
        $prefix = $this->vars['db_prefix'];
        $rs     = $db->select(
            'SELECT * FROM ' . $prefix . 'terms AS t, ' . $prefix . 'term_taxonomy AS x ' .
            'WHERE x.taxonomy = \'category\' ' .
            'AND t.term_id = x.term_id ' .
            ($this->vars['ignore_first_cat'] ? 'AND t.term_id <> 1 ' : '') .
            'ORDER BY t.term_id ASC'
        );

        try
        {
            $this->con->execute(
                'DELETE FROM ' . $this->prefix . 'category ' .
                "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' "
            );

            $ord = 2;
            while ($rs->fetch()) {
                $cur            = $this->con->openCursor($this->prefix . 'category');
                $cur->blog_id   = $this->blog_id;
                $cur->cat_title = $this->cleanStr($rs->name);
                $cur->cat_desc  = $this->cleanStr($rs->description);
                $cur->cat_url   = $this->cleanStr($rs->slug);
                $cur->cat_lft   = $ord++;
                $cur->cat_rgt   = $ord++;

                $cur->cat_id = $this->con->select(
                    'SELECT MAX(cat_id) FROM ' . $this->prefix . 'category'
                )->f(0) + 1;
                $this->vars['cat_ids'][$rs->term_id] = $cur->cat_id;
                $cur->insert();
            }

            $db->close();
        } catch (Exception $e) {
            $db->close();
            throw $e;
        }
    }

    # Blogroll import
    protected function importLinks()
    {
        $db     = $this->db();
        $prefix = $this->vars['db_prefix'];
        $rs     = $db->select('SELECT * FROM ' . $prefix . 'links ORDER BY link_id ASC');

        try
        {
            $this->con->execute(
                'DELETE FROM ' . $this->prefix . 'link ' .
                "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' "
            );

            while ($rs->fetch()) {
                $cur             = $this->con->openCursor($this->prefix . 'link');
                $cur->blog_id    = $this->blog_id;
                $cur->link_href  = $this->cleanStr($rs->link_url);
                $cur->link_title = $this->cleanStr($rs->link_name);
                $cur->link_desc  = $this->cleanStr($rs->link_description);
                $cur->link_xfn   = $this->cleanStr($rs->link_rel);

                $cur->link_id = $this->con->select(
                    'SELECT MAX(link_id) FROM ' . $this->prefix . 'link'
                )->f(0) + 1;
                $cur->insert();
            }

            $db->close();
        } catch (Exception $e) {
            $db->close();
            throw $e;
        }
    }

    # Entries import
    protected function importPosts(&$percent)
    {
        $db     = $this->db();
        $prefix = $this->vars['db_prefix'];

        $plink = $db->select(
            'SELECT option_value FROM ' . $prefix . 'options ' .
            "WHERE option_name = 'permalink_structure'"
        )->option_value;
        if ($plink) {
            $this->vars['permalink_template'] = substr($plink, 1);
        }

        $rs = $db->select(
            'SELECT * FROM ' . $prefix . 'posts ' .
            'WHERE post_type = \'post\' OR post_type = \'page\' ' .
            'ORDER BY ID ASC ' .
            $db->limit($this->post_offset, $this->post_limit)
        );

        try
        {
            if ($this->post_offset == 0) {
                $this->con->execute(
                    'DELETE FROM ' . $this->prefix . 'post ' .
                    "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' "
                );
            }

            while ($rs->fetch()) {
                $this->importPost($rs, $db);
            }

            $db->close();
        } catch (Exception $e) {
            $db->close();
            throw $e;
        }

        if ($rs->count() < $this->post_limit) {
            return -1;
        } else {
            $this->post_offset += $this->post_limit;
        }

        if ($this->post_offset > $this->post_count) {
            $percent = 100;
        } else {
            $percent = $this->post_offset * 100 / $this->post_count;
        }
    }

    protected function importPost($rs, $db)
    {
        $post_date = !@strtotime($rs->post_date) ? '1970-01-01 00:00' : $rs->post_date;
        if (!isset($this->vars['user_ids'][$rs->post_author])) {
            $user_id = $this->core->auth->userID();
        } else {
            $user_id = $this->vars['user_ids'][$rs->post_author];
        }

        $cur              = $this->con->openCursor($this->prefix . 'post');
        $cur->blog_id     = $this->blog_id;
        $cur->user_id     = $user_id;
        $cur->post_dt     = $post_date;
        $cur->post_creadt = $post_date;
        $cur->post_upddt  = $rs->post_modified;
        $cur->post_title  = $this->cleanStr($rs->post_title);

        if (!$cur->post_title) {
            $cur->post_title = 'No title';
        }

        if ($this->vars['cat_import'] || $this->vars['cat_as_tags']) {
            $old_cat_ids = $db->select(
                'SELECT * FROM ' . $this->vars['db_prefix'] . 'terms AS t, ' .
                $this->vars['db_prefix'] . 'term_taxonomy AS x, ' .
                $this->vars['db_prefix'] . 'term_relationships AS r ' .
                'WHERE t.term_id = x.term_id ' .
                ($this->vars['ignore_first_cat'] ? 'AND t.term_id <> 1 ' : '') .
                'AND x.taxonomy = \'category\' ' .
                'AND t.term_id = r.term_taxonomy_id ' .
                'AND r.object_id =' . $rs->ID .
                ' ORDER BY t.term_id ASC '
            );
            if (!$old_cat_ids->isEmpty() && $this->vars['cat_import']) {
                $cur->cat_id = $this->vars['cat_ids'][(integer) $old_cat_ids->term_id];
            }
        }

        $permalink_infos = array(
            date('Y', strtotime($cur->post_dt)),
            date('m', strtotime($cur->post_dt)),
            date('d', strtotime($cur->post_dt)),
            date('H', strtotime($cur->post_dt)),
            date('i', strtotime($cur->post_dt)),
            date('s', strtotime($cur->post_dt)),
            $rs->post_name,
            $rs->ID,
            $cur->cat_id,
            $cur->user_id
        );
        $cur->post_url = str_replace(
            $this->vars['permalink_tags'],
            $permalink_infos,
            $rs->post_type == 'post' ? $this->vars['permalink_template'] : '%postname%'
        );
        $cur->post_url = substr($cur->post_url, 0, 255);

        if (!$cur->post_url) {
            $cur->post_url = $rs->ID;
        }

        $cur->post_format = $this->vars['post_formater'];
        $_post_content    = explode('<!--more-->', $rs->post_content, 2);
        if (count($_post_content) == 1) {
            $cur->post_excerpt = null;
            $cur->post_content = $this->cleanStr(array_shift($_post_content));
        } else {
            $cur->post_excerpt = $this->cleanStr(array_shift($_post_content));
            $cur->post_content = $this->cleanStr(array_shift($_post_content));
        }

        $cur->post_content_xhtml = $this->core->callFormater($this->vars['post_formater'], $cur->post_content);
        $cur->post_excerpt_xhtml = $this->core->callFormater($this->vars['post_formater'], $cur->post_excerpt);

        switch ($rs->post_status) {
            case 'publish':
                $cur->post_status = 1;
                break;
            case 'draft':
                $cur->post_status = 0;
                break;
            case 'pending':
                $cur->post_status = -2;
                break;
            default:
                $cur->post_status = -2;
        }
        $cur->post_type         = $rs->post_type;
        $cur->post_password     = $rs->post_password ?: null;
        $cur->post_open_comment = $rs->comment_status == 'open' ? 1 : 0;
        $cur->post_open_tb      = $rs->ping_status == 'open' ? 1 : 0;

        $cur->post_words = implode(' ', text::splitWords(
            $cur->post_title . ' ' .
            $cur->post_excerpt_xhtml . ' ' .
            $cur->post_content_xhtml
        ));

        $cur->post_id = $this->con->select(
            'SELECT MAX(post_id) FROM ' . $this->prefix . 'post'
        )->f(0) + 1;

        $cur->post_url = $this->core->blog->getPostURL($cur->post_url, $cur->post_dt, $cur->post_title, $cur->post_id);

        $cur->insert();
        $this->importComments($rs->ID, $cur->post_id, $db);
        $this->importPings($rs->ID, $cur->post_id, $db);

        # Create tags
        $this->importTags($rs->ID, $cur->post_id, $db);

        if (isset($old_cat_ids)) {
            if (!$old_cat_ids->isEmpty() && $this->vars['cat_as_tags']) {
                $old_cat_ids->moveStart();
                while ($old_cat_ids->fetch()) {
                    $this->core->meta->setPostMeta($cur->post_id, 'tag', $this->cleanStr($this->vars['cat_tags_prefix'] . $old_cat_ids->name));
                }
            }
        }
    }

    # Comments import
    protected function importComments($post_id, $new_post_id, $db)
    {
        $count_c = $count_t = 0;

        $rs = $db->select(
            'SELECT * FROM ' . $this->vars['db_prefix'] . 'comments ' .
            'WHERE comment_post_ID = ' . (integer) $post_id . ' '
        );

        while ($rs->fetch()) {
            $cur                    = $this->con->openCursor($this->prefix . 'comment');
            $cur->post_id           = (integer) $new_post_id;
            $cur->comment_author    = $this->cleanStr($rs->comment_author);
            $cur->comment_status    = (integer) $rs->comment_approved;
            $cur->comment_dt        = $rs->comment_date;
            $cur->comment_email     = $this->cleanStr($rs->comment_author_email);
            $cur->comment_content   = $this->core->callFormater($this->vars['comment_formater'], $this->cleanStr($rs->comment_content));
            $cur->comment_ip        = $rs->comment_author_IP;
            $cur->comment_trackback = $rs->comment_type == 'trackback' ? 1 : 0;
            $cur->comment_site      = substr($this->cleanStr($rs->comment_author_url), 0, 255);
            if ($cur->comment_site == '') {
                $cur->comment_site = null;
            }

            if ($rs->comment_approved == 'spam') {
                $cur->comment_status = -2;
            }

            $cur->comment_words = implode(' ', text::splitWords($cur->comment_content));

            $cur->comment_id = $this->con->select(
                'SELECT MAX(comment_id) FROM ' . $this->prefix . 'comment'
            )->f(0) + 1;

            $cur->insert();

            if ($cur->comment_trackback && $cur->comment_status == 1) {
                $count_t++;
            } elseif ($cur->comment_status == 1) {
                $count_c++;
            }
        }

        if ($count_t > 0 || $count_c > 0) {
            $this->con->execute(
                'UPDATE ' . $this->prefix . 'post SET ' .
                'nb_comment = ' . $count_c . ', ' .
                'nb_trackback = ' . $count_t . ' ' .
                'WHERE post_id = ' . (integer) $new_post_id . ' '
            );
        }
    }

    # Pings import
    protected function importPings($post_id, $new_post_id, $db)
    {
        $urls  = array();
        $pings = array();

        $rs = $db->select(
            'SELECT pinged FROM ' . $this->vars['db_prefix'] . 'posts ' .
            'WHERE ID = ' . (integer) $post_id
        );
        $pings = explode("\n", $rs->pinged);
        unset($pings[0]);

        foreach ($pings as $ping_url) {
            $url = $this->cleanStr($ping_url);
            if (isset($urls[$url])) {
                continue;
            }

            $cur           = $this->con->openCursor($this->prefix . 'ping');
            $cur->post_id  = (integer) $new_post_id;
            $cur->ping_url = $url;
            $cur->insert();

            $urls[$url] = true;
        }
    }

    # Meta import
    protected function importTags($post_id, $new_post_id, $db)
    {
        $rs = $db->select(
            'SELECT * FROM ' . $this->vars['db_prefix'] . 'terms AS t, ' .
            $this->vars['db_prefix'] . 'term_taxonomy AS x, ' .
            $this->vars['db_prefix'] . 'term_relationships AS r ' .
            'WHERE t.term_id = x.term_id ' .
            'AND x.taxonomy = \'post_tag\' ' .
            'AND t.term_id = r.term_taxonomy_id ' .
            'AND r.object_id =' . $post_id .
            ' ORDER BY t.term_id ASC'
        );

        if ($rs->isEmpty()) {
            return;
        }

        while ($rs->fetch()) {
            $this->core->meta->setPostMeta($new_post_id, 'tag', $this->cleanStr($rs->name));
        }
    }
}
