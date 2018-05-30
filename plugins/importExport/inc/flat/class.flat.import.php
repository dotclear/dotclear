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

class flatImport extends flatBackup
{
    private $core;
    private $con;
    private $prefix;

    private $dc_version;
    private $dc_major;
    private $mode;

    private $blog_url;
    private $blog_name;
    private $blog_desc;

    private $users = array();

    public $old_ids = array(
        'category' => array(),
        'post'     => array(),
        'media'    => array()
    );

    public $stack = array(
        'categories' => null,
        'cat_id'     => 1,
        'cat_lft'    => array(),
        'post_id'    => 1,
        'media_id'   => 1,
        'comment_id' => 1,
        'link_id'    => 1,
        'log_id'     => 1
    );

    public $has_categories = false;

    public function __construct($core, $file)
    {
        parent::__construct($file);

        $first_line = fgets($this->fp);
        if (strpos($first_line, '///DOTCLEAR|') !== 0) {
            throw new Exception(__('File is not a DotClear backup.'));
        }

        @set_time_limit(300);

        $l = explode('|', $first_line);

        if (isset($l[1])) {
            $this->dc_version = $l[1];
        }

        $this->mode = isset($l[2]) ? strtolower(trim($l[2])) : 'single';
        if ($this->mode != 'full' && $this->mode != 'single') {
            $this->mode = 'single';
        }

        if (version_compare('1.2', $this->dc_version, '<=') &&
            version_compare('1.3', $this->dc_version, '>')) {
            $this->dc_major_version = '1.2';
        } else {
            $this->dc_major_version = '2.0';
        }

        $this->core   = &$core;
        $this->con    = &$core->con;
        $this->prefix = $core->prefix;

        $this->cur_blog        = $this->con->openCursor($this->prefix . 'blog');
        $this->cur_category    = $this->con->openCursor($this->prefix . 'category');
        $this->cur_link        = $this->con->openCursor($this->prefix . 'link');
        $this->cur_setting     = $this->con->openCursor($this->prefix . 'setting');
        $this->cur_user        = $this->con->openCursor($this->prefix . 'user');
        $this->cur_pref        = $this->con->openCursor($this->prefix . 'pref');
        $this->cur_permissions = $this->con->openCursor($this->prefix . 'permissions');
        $this->cur_post        = $this->con->openCursor($this->prefix . 'post');
        $this->cur_meta        = $this->con->openCursor($this->prefix . 'meta');
        $this->cur_media       = $this->con->openCursor($this->prefix . 'media');
        $this->cur_post_media  = $this->con->openCursor($this->prefix . 'post_media');
        $this->cur_log         = $this->con->openCursor($this->prefix . 'log');
        $this->cur_ping        = $this->con->openCursor($this->prefix . 'ping');
        $this->cur_comment     = $this->con->openCursor($this->prefix . 'comment');
        $this->cur_spamrule    = $this->con->openCursor($this->prefix . 'spamrule');
        $this->cur_version     = $this->con->openCursor($this->prefix . 'version');

        # --BEHAVIOR-- importInit
        $this->core->callBehavior('importInit', $this, $this->core);
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function importSingle()
    {
        if ($this->mode != 'single') {
            throw new Exception(__('File is not a single blog export.'));
        }

        if (!$this->core->auth->check('admin', $this->core->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $this->blog_id = $this->core->blog->id;

        $this->stack['categories'] = $this->con->select(
            'SELECT cat_id, cat_title, cat_url ' .
            'FROM ' . $this->prefix . 'category ' .
            "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' "
        );

        $rs                    = $this->con->select('SELECT MAX(cat_id) FROM ' . $this->prefix . 'category');
        $this->stack['cat_id'] = ((integer) $rs->f(0)) + 1;

        $rs                     = $this->con->select('SELECT MAX(link_id) FROM ' . $this->prefix . 'link');
        $this->stack['link_id'] = ((integer) $rs->f(0)) + 1;

        $rs                     = $this->con->select('SELECT MAX(post_id) FROM ' . $this->prefix . 'post');
        $this->stack['post_id'] = ((integer) $rs->f(0)) + 1;

        $rs                      = $this->con->select('SELECT MAX(media_id) FROM ' . $this->prefix . 'media');
        $this->stack['media_id'] = ((integer) $rs->f(0)) + 1;

        $rs                        = $this->con->select('SELECT MAX(comment_id) FROM ' . $this->prefix . 'comment');
        $this->stack['comment_id'] = ((integer) $rs->f(0)) + 1;

        $rs                    = $this->con->select('SELECT MAX(log_id) FROM ' . $this->prefix . 'log');
        $this->stack['log_id'] = ((integer) $rs->f(0)) + 1;

        $rs = $this->con->select(
            'SELECT MAX(cat_rgt) AS cat_rgt FROM ' . $this->prefix . 'category ' .
            "WHERE blog_id = '" . $this->con->escape($this->core->blog->id) . "'"
        );

        if ((integer) $rs->cat_rgt > 0) {
            $this->has_categories                          = true;
            $this->stack['cat_lft'][$this->core->blog->id] = (integer) $rs->cat_rgt + 1;
        }

        $this->con->begin();

        try
        {
            $last_line_name = '';
            $constrained    = array('post', 'meta', 'post_media', 'ping', 'comment');

            while (($line = $this->getLine()) !== false) {
                # import DC 1.2.x, we fix lines before insert
                if ($this->dc_major_version == '1.2') {
                    $this->prepareDC12line($line);
                }

                if ($last_line_name != $line->__name) {
                    if (in_array($last_line_name, $constrained)) {
                        # UNDEFER
                        if ($this->con->syntax() == 'mysql') {
                            $this->con->execute('SET foreign_key_checks = 1');
                        }

                        if ($this->con->syntax() == 'postgresql') {
                            $this->con->execute('SET CONSTRAINTS ALL DEFERRED');
                        }

                    }

                    if (in_array($line->__name, $constrained)) {
                        # DEFER
                        if ($this->con->syntax() == 'mysql') {
                            $this->con->execute('SET foreign_key_checks = 0');
                        }

                        if ($this->con->syntax() == 'postgresql') {
                            $this->con->execute('SET CONSTRAINTS ALL IMMEDIATE');
                        }

                    }

                    $last_line_name = $line->__name;
                }

                switch ($line->__name) {
                    case 'category':
                        $this->insertCategorySingle($line);
                        break;
                    case 'link':
                        $this->insertLinkSingle($line);
                        break;
                    case 'post':
                        $this->insertPostSingle($line);
                        break;
                    case 'meta':
                        $this->insertMetaSingle($line);
                        break;
                    case 'media':
                        $this->insertMediaSingle($line);
                        break;
                    case 'post_media':
                        $this->insertPostMediaSingle($line);
                        break;
                    case 'ping':
                        $this->insertPingSingle($line);
                        break;
                    case 'comment':
                        $this->insertCommentSingle($line);
                        break;
                }

                # --BEHAVIOR-- importSingle
                $this->core->callBehavior('importSingle', $line, $this, $this->core);
            }

            if ($this->con->syntax() == 'mysql') {
                $this->con->execute('SET foreign_key_checks = 1');
            }

            if ($this->con->syntax() == 'postgresql') {
                $this->con->execute('SET CONSTRAINTS ALL DEFERRED');
            }

        } catch (Exception $e) {
            @fclose($this->fp);
            $this->con->rollback();
            throw new Exception($e->getMessage() . ' - ' . sprintf(__('Error raised at line %s'), $line->__line));
        }
        @fclose($this->fp);
        $this->con->commit();
    }

    public function importFull()
    {
        if ($this->mode != 'full') {
            throw new Exception(__('File is not a full export.'));
        }

        if (!$this->core->auth->isSuperAdmin()) {
            throw new Exception(__('Permission denied.'));
        }

        $this->con->begin();
        $this->con->execute('DELETE FROM ' . $this->prefix . 'blog');
        $this->con->execute('DELETE FROM ' . $this->prefix . 'media');
        $this->con->execute('DELETE FROM ' . $this->prefix . 'spamrule');
        $this->con->execute('DELETE FROM ' . $this->prefix . 'setting');
        $this->con->execute('DELETE FROM ' . $this->prefix . 'log');

        try
        {
            while (($line = $this->getLine()) !== false) {
                switch ($line->__name) {
                    case 'blog':
                        $this->insertBlog($line);
                        break;
                    case 'category':
                        $this->insertCategory($line);
                        break;
                    case 'link':
                        $this->insertLink($line);
                        break;
                    case 'setting':
                        $this->insertSetting($line);
                        break;
                    case 'user':
                        $this->insertUser($line);
                        break;
                    case 'pref':
                        $this->insertPref($line);
                        break;
                    case 'permissions':
                        $this->insertPermissions($line);
                        break;
                    case 'post':
                        $this->insertPost($line);
                        break;
                    case 'meta':
                        $this->insertMeta($line);
                        break;
                    case 'media':
                        $this->insertMedia($line);
                        break;
                    case 'post_media':
                        $this->insertPostMedia($line);
                        break;
                    case 'log';
                        $this->insertLog($line);
                        break;
                    case 'ping':
                        $this->insertPing($line);
                        break;
                    case 'comment':
                        $this->insertComment($line);
                        break;
                    case 'spamrule':
                        $this->insertSpamRule($line);
                        break;
                }
                # --BEHAVIOR-- importFull
                $this->core->callBehavior('importFull', $line, $this, $this->core);
            }
        } catch (Exception $e) {
            @fclose($this->fp);
            $this->con->rollback();
            throw new Exception($e->getMessage() . ' - ' . sprintf(__('Error raised at line %s'), $line->__line));
        }
        @fclose($this->fp);
        $this->con->commit();
    }

    private function insertBlog($blog)
    {
        $this->cur_blog->clean();

        $this->cur_blog->blog_id     = (string) $blog->blog_id;
        $this->cur_blog->blog_uid    = (string) $blog->blog_uid;
        $this->cur_blog->blog_creadt = (string) $blog->blog_creadt;
        $this->cur_blog->blog_upddt  = (string) $blog->blog_upddt;
        $this->cur_blog->blog_url    = (string) $blog->blog_url;
        $this->cur_blog->blog_name   = (string) $blog->blog_name;
        $this->cur_blog->blog_desc   = (string) $blog->blog_desc;

        $this->cur_blog->blog_status = $blog->exists('blog_status') ? (integer) $blog->blog_status : 1;

        $this->cur_blog->insert();
    }

    private function insertCategory($category)
    {
        $this->cur_category->clean();

        $this->cur_category->cat_id    = (string) $category->cat_id;
        $this->cur_category->blog_id   = (string) $category->blog_id;
        $this->cur_category->cat_title = (string) $category->cat_title;
        $this->cur_category->cat_url   = (string) $category->cat_url;
        $this->cur_category->cat_desc  = (string) $category->cat_desc;

        if (!$this->has_categories && $category->exists('cat_lft') && $category->exists('cat_rgt')) {
            $this->cur_category->cat_lft = (integer) $category->cat_lft;
            $this->cur_category->cat_rgt = (integer) $category->cat_rgt;
        } else {
            if (!isset($this->stack['cat_lft'][$category->blog_id])) {
                $this->stack['cat_lft'][$category->blog_id] = 2;
            }
            $this->cur_category->cat_lft = $this->stack['cat_lft'][$category->blog_id]++;
            $this->cur_category->cat_rgt = $this->stack['cat_lft'][$category->blog_id]++;
        }

        $this->cur_category->insert();
    }

    private function insertLink($link)
    {
        $this->cur_link->clean();

        $this->cur_link->link_id       = (integer) $link->link_id;
        $this->cur_link->blog_id       = (string) $link->blog_id;
        $this->cur_link->link_href     = (string) $link->link_href;
        $this->cur_link->link_title    = (string) $link->link_title;
        $this->cur_link->link_desc     = (string) $link->link_desc;
        $this->cur_link->link_lang     = (string) $link->link_lang;
        $this->cur_link->link_xfn      = (string) $link->link_xfn;
        $this->cur_link->link_position = (integer) $link->link_position;

        $this->cur_link->insert();
    }

    private function insertSetting($setting)
    {
        $this->cur_setting->clean();

        $this->cur_setting->setting_id    = (string) $setting->setting_id;
        $this->cur_setting->blog_id       = !$setting->blog_id ? null : (string) $setting->blog_id;
        $this->cur_setting->setting_ns    = (string) $setting->setting_ns;
        $this->cur_setting->setting_value = (string) $setting->setting_value;
        $this->cur_setting->setting_type  = (string) $setting->setting_type;
        $this->cur_setting->setting_label = (string) $setting->setting_label;

        $this->cur_setting->insert();
    }

    private function insertPref($pref)
    {
        if ($this->prefExists($pref->pref_ws, $pref->pref_id, $pref->user_id)) {
            return;
        }

        $this->cur_pref->clean();

        $this->cur_pref->pref_id    = (string) $pref->pref_id;
        $this->cur_pref->user_id    = !$pref->user_id ? null : (string) $pref->user_id;
        $this->cur_pref->pref_ws    = (string) $pref->pref_ws;
        $this->cur_pref->pref_value = (string) $pref->pref_value;
        $this->cur_pref->pref_type  = (string) $pref->pref_type;
        $this->cur_pref->pref_label = (string) $pref->pref_label;

        $this->cur_pref->insert();
    }

    private function insertUser($user)
    {
        if ($this->userExists($user->user_id)) {
            return;
        }

        $this->cur_user->clean();

        $this->cur_user->user_id           = (string) $user->user_id;
        $this->cur_user->user_super        = (integer) $user->user_super;
        $this->cur_user->user_pwd          = (string) $user->user_pwd;
        $this->cur_user->user_recover_key  = (string) $user->user_recover_key;
        $this->cur_user->user_name         = (string) $user->user_name;
        $this->cur_user->user_firstname    = (string) $user->user_firstname;
        $this->cur_user->user_displayname  = (string) $user->user_displayname;
        $this->cur_user->user_email        = (string) $user->user_email;
        $this->cur_user->user_url          = (string) $user->user_url;
        $this->cur_user->user_default_blog = !$user->user_default_blog ? null : (string) $user->user_default_blog;
        $this->cur_user->user_lang         = (string) $user->user_lang;
        $this->cur_user->user_tz           = (string) $user->user_tz;
        $this->cur_user->user_post_status  = (integer) $user->user_post_status;
        $this->cur_user->user_creadt       = (string) $user->user_creadt;
        $this->cur_user->user_upddt        = (string) $user->user_upddt;

        $this->cur_user->user_desc    = $user->exists('user_desc') ? (string) $user->user_desc : null;
        $this->cur_user->user_options = $user->exists('user_options') ? (string) $user->user_options : null;
        $this->cur_user->user_status  = $user->exists('user_status') ? (integer) $user->user_status : 1;

        $this->cur_user->insert();

        $this->stack['users'][$user->user_id] = true;
    }

    private function insertPermissions($permissions)
    {
        $this->cur_permissions->clean();

        $this->cur_permissions->user_id     = (string) $permissions->user_id;
        $this->cur_permissions->blog_id     = (string) $permissions->blog_id;
        $this->cur_permissions->permissions = (string) $permissions->permissions;

        $this->cur_permissions->insert();
    }

    private function insertPost($post)
    {
        $this->cur_post->clean();

        $cat_id = (integer) $post->cat_id;
        if (!$cat_id) {
            $cat_id = null;
        }

        $post_password = $post->post_password ? (string) $post->post_password : null;

        $this->cur_post->post_id            = (integer) $post->post_id;
        $this->cur_post->blog_id            = (string) $post->blog_id;
        $this->cur_post->user_id            = (string) $this->getUserId($post->user_id);
        $this->cur_post->cat_id             = $cat_id;
        $this->cur_post->post_dt            = (string) $post->post_dt;
        $this->cur_post->post_creadt        = (string) $post->post_creadt;
        $this->cur_post->post_upddt         = (string) $post->post_upddt;
        $this->cur_post->post_password      = $post_password;
        $this->cur_post->post_type          = (string) $post->post_type;
        $this->cur_post->post_format        = (string) $post->post_format;
        $this->cur_post->post_url           = (string) $post->post_url;
        $this->cur_post->post_lang          = (string) $post->post_lang;
        $this->cur_post->post_title         = (string) $post->post_title;
        $this->cur_post->post_excerpt       = (string) $post->post_excerpt;
        $this->cur_post->post_excerpt_xhtml = (string) $post->post_excerpt_xhtml;
        $this->cur_post->post_content       = (string) $post->post_content;
        $this->cur_post->post_content_xhtml = (string) $post->post_content_xhtml;
        $this->cur_post->post_notes         = (string) $post->post_notes;
        $this->cur_post->post_words         = (string) $post->post_words;
        $this->cur_post->post_meta          = (string) $post->post_meta;
        $this->cur_post->post_status        = (integer) $post->post_status;
        $this->cur_post->post_selected      = (integer) $post->post_selected;
        $this->cur_post->post_open_comment  = (integer) $post->post_open_comment;
        $this->cur_post->post_open_tb       = (integer) $post->post_open_tb;
        $this->cur_post->nb_comment         = (integer) $post->nb_comment;
        $this->cur_post->nb_trackback       = (integer) $post->nb_trackback;
        $this->cur_post->post_position      = (integer) $post->post_position;

        $this->cur_post->post_tz = $post->exists('post_tz') ? (string) $post->post_tz : 'UTC';

        $this->cur_post->insert();
    }

    private function insertMeta($meta)
    {
        $this->cur_meta->clean();

        $this->cur_meta->meta_id   = (string) $meta->meta_id;
        $this->cur_meta->meta_type = (string) $meta->meta_type;
        $this->cur_meta->post_id   = (integer) $meta->post_id;

        $this->cur_meta->insert();
    }

    private function insertMedia($media)
    {
        $this->cur_media->clean();

        $this->cur_media->media_id      = (integer) $media->media_id;
        $this->cur_media->user_id       = (string) $media->user_id;
        $this->cur_media->media_path    = (string) $media->media_path;
        $this->cur_media->media_title   = (string) $media->media_title;
        $this->cur_media->media_file    = (string) $media->media_file;
        $this->cur_media->media_meta    = (string) $media->media_meta;
        $this->cur_media->media_dt      = (string) $media->media_dt;
        $this->cur_media->media_creadt  = (string) $media->media_creadt;
        $this->cur_media->media_upddt   = (string) $media->media_upddt;
        $this->cur_media->media_private = (integer) $media->media_private;

        $this->cur_media->media_dir = $media->exists('media_dir') ? (string) $media->media_dir : dirname($media->media_file);

        if (!$this->mediaExists()) {
            $this->cur_media->insert();
        }
    }

    private function insertPostMedia($post_media)
    {
        $this->cur_post_media->clean();

        $this->cur_post_media->media_id = (integer) $post_media->media_id;
        $this->cur_post_media->post_id  = (integer) $post_media->post_id;

        $this->cur_post_media->insert();
    }

    private function insertLog($log)
    {
        $this->cur_log->clean();

        $this->cur_log->log_id    = (integer) $log->log_id;
        $this->cur_log->user_id   = (string) $log->user_id;
        $this->cur_log->log_table = (string) $log->log_table;
        $this->cur_log->log_dt    = (string) $log->log_dt;
        $this->cur_log->log_ip    = (string) $log->log_ip;
        $this->cur_log->log_msg   = (string) $log->log_msg;

        $this->cur_log->insert();
    }

    private function insertPing($ping)
    {
        $this->cur_ping->clean();

        $this->cur_ping->post_id  = (integer) $ping->post_id;
        $this->cur_ping->ping_url = (string) $ping->ping_url;
        $this->cur_ping->ping_dt  = (string) $ping->ping_dt;

        $this->cur_ping->insert();
    }

    private function insertComment($comment)
    {
        $this->cur_comment->clean();

        $this->cur_comment->comment_id          = (integer) $comment->comment_id;
        $this->cur_comment->post_id             = (integer) $comment->post_id;
        $this->cur_comment->comment_dt          = (string) $comment->comment_dt;
        $this->cur_comment->comment_upddt       = (string) $comment->comment_upddt;
        $this->cur_comment->comment_author      = (string) $comment->comment_author;
        $this->cur_comment->comment_email       = (string) $comment->comment_email;
        $this->cur_comment->comment_site        = (string) $comment->comment_site;
        $this->cur_comment->comment_content     = (string) $comment->comment_content;
        $this->cur_comment->comment_words       = (string) $comment->comment_words;
        $this->cur_comment->comment_ip          = (string) $comment->comment_ip;
        $this->cur_comment->comment_status      = (integer) $comment->comment_status;
        $this->cur_comment->comment_spam_status = (string) $comment->comment_spam_status;
        $this->cur_comment->comment_trackback   = (integer) $comment->comment_trackback;

        $this->cur_comment->comment_tz          = $comment->exists('comment_tz') ? (string) $comment->comment_tz : 'UTC';
        $this->cur_comment->comment_spam_filter = $comment->exists('comment_spam_filter') ? (string) $comment->comment_spam_filter : null;

        $this->cur_comment->insert();
    }

    private function insertSpamRule($spamrule)
    {
        $this->cur_spamrule->clean();

        $this->cur_spamrule->rule_id      = (integer) $spamrule->rule_id;
        $this->cur_spamrule->blog_id      = !$spamrule->blog_id ? null : (string) $spamrule->blog_id;
        $this->cur_spamrule->rule_type    = (string) $spamrule->rule_type;
        $this->cur_spamrule->rule_content = (string) $spamrule->rule_content;

        $this->cur_spamrule->insert();
    }

    private function insertCategorySingle($category)
    {
        $this->cur_category->clean();

        $m = $this->searchCategory($this->stack['categories'], $category->cat_url);

        $old_id = $category->cat_id;
        if ($m !== false) {
            $cat_id = $m;
        } else {
            $cat_id            = $this->stack['cat_id'];
            $category->cat_id  = $cat_id;
            $category->blog_id = $this->blog_id;

            $this->insertCategory($category);
            $this->stack['cat_id']++;
        }

        $this->old_ids['category'][(integer) $old_id] = $cat_id;
    }

    private function insertLinkSingle($link)
    {
        $link->blog_id = $this->blog_id;
        $link->link_id = $this->stack['link_id'];

        $this->insertLink($link);
        $this->stack['link_id']++;
    }

    private function insertPostSingle($post)
    {
        if (!$post->cat_id || isset($this->old_ids['category'][(integer) $post->cat_id])) {
            $post_id                                         = $this->stack['post_id'];
            $this->old_ids['post'][(integer) $post->post_id] = $post_id;

            $cat_id = $post->cat_id ? $this->old_ids['category'][(integer) $post->cat_id] : null;

            $post->post_id = $post_id;
            $post->cat_id  = $cat_id;
            $post->blog_id = $this->blog_id;

            $post->post_url = $this->core->blog->getPostURL(
                $post->post_url, $post->post_dt, $post->post_title, $post->post_id
            );

            $this->insertPost($post);
            $this->stack['post_id']++;
        } else {
            self::throwIdError($post->__name, $post->__line, 'category');
        }
    }

    private function insertMetaSingle($meta)
    {
        if (isset($this->old_ids['post'][(integer) $meta->post_id])) {
            $meta->post_id = $this->old_ids['post'][(integer) $meta->post_id];
            $this->insertMeta($meta);
        } else {
            self::throwIdError($meta->__name, $meta->__line, 'post');
        }
    }

    private function insertMediaSingle($media)
    {
        $media_id = $this->stack['media_id'];
        $old_id   = $media->media_id;

        $media->media_id   = $media_id;
        $media->media_path = $this->core->blog->settings->system->public_path;
        $media->user_id    = $this->getUserId($media->user_id);

        $this->insertMedia($media);
        $this->stack['media_id']++;
        $this->old_ids['media'][(integer) $old_id] = $media_id;
    }

    private function insertPostMediaSingle($post_media)
    {
        if (isset($this->old_ids['media'][(integer) $post_media->media_id]) &&
            isset($this->old_ids['post'][(integer) $post_media->post_id])) {
            $post_media->media_id = $this->old_ids['media'][(integer) $post_media->media_id];
            $post_media->post_id  = $this->old_ids['post'][(integer) $post_media->post_id];

            $this->insertPostMedia($post_media);
        } elseif (!isset($this->old_ids['media'][(integer) $post_media->media_id])) {
            self::throwIdError($post_media->__name, $post_media->__line, 'media');
        } else {
            self::throwIdError($post_media->__name, $post_media->__line, 'post');
        }
    }

    private function insertPingSingle($ping)
    {
        if (isset($this->old_ids['post'][(integer) $ping->post_id])) {
            $ping->post_id = $this->old_ids['post'][(integer) $ping->post_id];

            $this->insertPing($ping);
        } else {
            self::throwIdError($ping->__name, $ping->__line, 'post');
        }
    }

    private function insertCommentSingle($comment)
    {
        if (isset($this->old_ids['post'][(integer) $comment->post_id])) {
            $comment_id = $this->stack['comment_id'];

            $comment->comment_id = $comment_id;
            $comment->post_id    = $this->old_ids['post'][(integer) $comment->post_id];

            $this->insertComment($comment);
            $this->stack['comment_id']++;
        } else {
            self::throwIdError($comment->__name, $comment->__line, 'post');
        }
    }

    private static function throwIdError($name, $line, $related)
    {
        throw new Exception(sprintf(
            __('ID of "%3$s" does not match on record "%1$s" at line %2$s of backup file.'),
            html::escapeHTML($name),
            html::escapeHTML($line),
            html::escapeHTML($related)
        ));
    }

    public function searchCategory($rs, $url)
    {
        while ($rs->fetch()) {
            if ($rs->cat_url == $url) {
                return $rs->cat_id;
            }
        }

        return false;
    }

    public function getUserId($user_id)
    {
        if (!$this->userExists($user_id)) {
            if ($this->core->auth->isSuperAdmin()) {
                # Sanitizes user_id and create a lambda user
                $user_id = preg_replace('/[^A-Za-z0-9]$/', '', $user_id);
                $user_id .= strlen($user_id) < 2 ? '-a' : '';

                # We change user_id, we need to check again
                if (!$this->userExists($user_id)) {
                    $this->cur_user->clean();
                    $this->cur_user->user_id  = (string) $user_id;
                    $this->cur_user->user_pwd = md5(uniqid());

                    $this->core->addUser($this->cur_user);

                    $this->stack['users'][$user_id] = true;
                }
            } else {
                # Returns current user id
                $user_id = $this->core->auth->userID();
            }
        }

        return $user_id;
    }

    private function userExists($user_id)
    {
        if (isset($this->stack['users'][$user_id])) {
            return $this->stack['users'][$user_id];
        }

        $strReq = 'SELECT user_id ' .
        'FROM ' . $this->prefix . 'user ' .
        "WHERE user_id = '" . $this->con->escape($user_id) . "' ";

        $rs = $this->con->select($strReq);

        $this->stack['users'][$user_id] = !$rs->isEmpty();
        return $this->stack['users'][$user_id];
    }

    private function prefExists($pref_ws, $pref_id, $user_id)
    {
        $strReq = 'SELECT pref_id,pref_ws,user_id ' .
        'FROM ' . $this->prefix . 'pref ' .
        "WHERE pref_id = '" . $this->con->escape($pref_id) . "' " .
        "AND pref_ws = '" . $this->con->escape($pref_ws) . "' ";
        if (!$user_id) {
            $strReq .= "AND user_id IS NULL ";
        } else {
            $strReq .= "AND user_id = '" . $this->con->escape($user_id) . "' ";
        }

        $rs = $this->con->select($strReq);

        return !$rs->isEmpty();
    }

    private function mediaExists()
    {
        $strReq = 'SELECT media_id ' .
        'FROM ' . $this->prefix . 'media ' .
        "WHERE media_path = '" . $this->con->escape($this->cur_media->media_path) . "' " .
        "AND media_file = '" . $this->con->escape($this->cur_media->media_file) . "' ";

        $rs = $this->con->select($strReq);

        return !$rs->isEmpty();
    }

    private function prepareDC12line(&$line)
    {
        $settings = array('dc_theme', 'dc_nb_post_per_page', 'dc_allow_comments',
            'dc_allow_trackbacks', 'dc_comment_pub', 'dc_comments_ttl',
            'dc_wiki_comments', 'dc_use_smilies', 'dc_date_format', 'dc_time_format',
            'dc_url_scan');

        switch ($line->__name) {
            case 'categorie':
                $line->substitute('cat_libelle', 'cat_title');
                $line->substitute('cat_libelle_url', 'cat_url');
                $line->__name  = 'category';
                $line->blog_id = 'default';
                break;
            case 'link':
                $line->substitute('href', 'link_href');
                $line->substitute('label', 'link_title');
                $line->substitute('title', 'link_desc');
                $line->substitute('lang', 'link_lang');
                $line->substitute('rel', 'link_xfn');
                $line->substitute('position', 'link_position');
                $line->blog_id = 'default';
                break;
            case 'post':
                $line->substitute('post_titre', 'post_title');
                $line->post_title         = html::decodeEntities($line->post_title);
                $line->post_url           = date('Y/m/d/', strtotime($line->post_dt)) . $line->post_id . '-' . $line->post_titre_url;
                $line->post_url           = substr($line->post_url, 0, 255);
                $line->post_format        = $line->post_content_wiki == '' ? 'xhtml' : 'wiki';
                $line->post_content_xhtml = $line->post_content;
                $line->post_excerpt_xhtml = $line->post_chapo;

                if ($line->post_format == 'wiki') {
                    $line->post_content = $line->post_content_wiki;
                    $line->post_excerpt = $line->post_chapo_wiki;
                } else {
                    $line->post_content = $line->post_content;
                    $line->post_excerpt = $line->post_chapo;
                }

                $line->post_status = (integer) $line->post_pub;
                $line->post_type   = 'post';
                $line->blog_id     = 'default';

                $line->drop('post_titre_url', 'post_content_wiki', 'post_chapo', 'post_chapo_wiki', 'post_pub');

                break;
            case 'post_meta':
                $line->drop('meta_id');
                $line->substitute('meta_key', 'meta_type');
                $line->substitute('meta_value', 'meta_id');
                $line->__name  = 'meta';
                $line->blog_id = 'default';
                break;
            case 'comment':
                $line->substitute('comment_auteur', 'comment_author');
                if ($line->comment_site != '' && !preg_match('!^http://.*$!', $line->comment_site, $m)) {
                    $line->comment_site = 'http://' . $line->comment_site;
                }
                $line->comment_status = (integer) $line->comment_pub;
                $line->drop('comment_pub');
                break;
        }

        # --BEHAVIOR-- importPrepareDC12
        $this->core->callBehavior('importPrepareDC12', $line, $this, $this->core);
    }
}
