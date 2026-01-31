<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Exception;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Database\ConnectionInterface;
use Dotclear\Plugin\antispam\Antispam;
use Dotclear\Plugin\blogroll\Blogroll;
use UnhandledMatchError;

/**
 * @brief   The module flat import handler.
 * @ingroup importExport
 *
 * @todo switch to SqlStatement
 */
class FlatImportV2 extends FlatBackup
{
    private readonly ConnectionInterface $con;
    private readonly string $prefix;

    private ?string $dc_version       = null;
    private ?string $dc_major_version = null;
    private string $mode;

    private string $blog_id;

    private readonly Cursor $cur_blog;
    private readonly Cursor $cur_category;
    private readonly Cursor $cur_link;
    private readonly Cursor $cur_setting;
    private readonly Cursor $cur_user;
    private readonly Cursor $cur_pref;
    private readonly Cursor $cur_permissions;
    private readonly Cursor $cur_post;
    private readonly Cursor $cur_meta;
    private readonly Cursor $cur_media;
    private readonly Cursor $cur_post_media;
    private readonly Cursor $cur_log;
    private readonly Cursor $cur_ping;
    private readonly Cursor $cur_comment;
    private readonly Cursor $cur_spamrule;

    /**
     * @var array<string, Cursor>   $cur_extra
     */
    protected array $cur_extra;

    /**
     * @var array<string, array<array-key, int>>    $old_ids
     */
    public $old_ids = [
        'category' => [],
        'post'     => [],
        'media'    => [],
    ];

    /**
     * @var array{categories: ?MetaRecord, cat_id: int, cat_lft: array<string, int>, post_id: int, media_id: int, comment_id: int, link_id: int, log_id: int, users: array<string, bool>}    $stack
     */
    public $stack = [
        'categories' => null,
        'cat_id'     => 1,
        'cat_lft'    => [],
        'post_id'    => 1,
        'media_id'   => 1,
        'comment_id' => 1,
        'link_id'    => 1,
        'log_id'     => 1,
        'users'      => [],
    ];

    public bool $has_categories = false;

    /**
     * Constructs a new instance.
     *
     * @param      string     $file   The file
     *
     * @throws     Exception
     */
    public function __construct(string $file)
    {
        parent::__construct($file);

        if (!is_resource($this->fp)) {
            throw new Exception(__('Unable to open the Dotclear backup file.'));
        }

        $first_line = fgets($this->fp);
        if ($first_line === false) {
            throw new Exception(__('Unable to read the Dotclear backup file.'));
        }

        if (!str_starts_with($first_line, '///DOTCLEAR|')) {
            throw new Exception(__('File is not a Dotclear backup.'));
        }

        @set_time_limit(300);

        $l = explode('|', $first_line);

        if (isset($l[1])) {
            $this->dc_version = $l[1];
        }

        $this->mode = isset($l[2]) ? strtolower(trim($l[2])) : 'single';
        if ($this->mode !== 'full' && $this->mode !== 'single') {
            $this->mode = 'single';
        }

        if (version_compare('1.2', (string) $this->dc_version, '<=') && version_compare('1.3', (string) $this->dc_version, '>')) {
            $this->dc_major_version = '1.2';
        } else {
            $this->dc_major_version = '2.0';
        }

        $this->con    = App::db()->con();
        $this->prefix = App::db()->con()->prefix();

        $this->cur_blog        = App::blog()->openBlogCursor();
        $this->cur_category    = App::blog()->categories()->openCategoryCursor();
        $this->cur_link        = $this->con->openCursor($this->prefix . Blogroll::LINK_TABLE_NAME);
        $this->cur_setting     = App::blogWorkspace()->openBlogWorkspaceCursor();
        $this->cur_user        = App::auth()->openUserCursor();
        $this->cur_pref        = App::userWorkspace()->openUserWorkspaceCursor();
        $this->cur_permissions = App::auth()->openPermCursor();
        $this->cur_post        = App::blog()->openPostCursor();
        $this->cur_meta        = App::meta()->openMetaCursor();
        $this->cur_media       = App::media()->openMediaCursor();
        $this->cur_post_media  = App::postMedia()->openPostMediaCursor();
        $this->cur_log         = App::log()->openLogCursor();
        $this->cur_ping        = App::trackback()->openTrackbackCursor();
        $this->cur_comment     = App::blog()->openCommentCursor();
        $this->cur_spamrule    = $this->con->openCursor($this->prefix . Antispam::SPAMRULE_TABLE_NAME);

        # --BEHAVIOR-- importInit -- FlatBackup
        App::behavior()->callBehavior('importInitV2', $this);
    }

    /**
     * Sets the extra cursor.
     *
     * @param      string   $name   The name
     * @param      Cursor   $cur    The cursor
     */
    public function setExtraCursor(string $name, Cursor $cur): void
    {
        $this->cur_extra[$name] = $cur;
    }

    /**
     * Gets the extra cursor.
     *
     * @param      string       $name   The name
     *
     * @return     Cursor|null  The extra cursor.
     */
    public function getExtraCursor(string $name): ?Cursor
    {
        return $this->cur_extra[$name] ?? null;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function importSingle(): void
    {
        if ($this->mode !== 'single') {
            throw new Exception(__('File is not a single blog export.'));
        }

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            throw new Exception(__('Permission denied.'));
        }

        $this->blog_id = App::blog()->id();

        $this->stack['categories'] = new MetaRecord($this->con->select(
            'SELECT cat_id, cat_title, cat_url ' .  // @phpXstan-ignore-line
            'FROM ' . $this->prefix . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
            "WHERE blog_id = '" . $this->con->escapeStr($this->blog_id) . "' "
        ));

        $rs = new MetaRecord($this->con->select('SELECT MAX(cat_id) FROM ' . $this->prefix . App::blog()->categories()::CATEGORY_TABLE_NAME));

        $cat_id                = is_numeric($cat_id = $rs->f(0)) ? (int) $cat_id : 0;
        $this->stack['cat_id'] = $cat_id + 1;

        $rs = new MetaRecord($this->con->select('SELECT MAX(link_id) FROM ' . $this->prefix . Blogroll::LINK_TABLE_NAME));

        $link_id                = is_numeric($link_id = $rs->f(0)) ? (int) $link_id : 0;
        $this->stack['link_id'] = $link_id + 1;

        $rs = new MetaRecord($this->con->select('SELECT MAX(post_id) FROM ' . $this->prefix . App::blog()::POST_TABLE_NAME));

        $post_id                = is_numeric($post_id = $rs->f(0)) ? (int) $post_id : 0;
        $this->stack['post_id'] = $post_id + 1;

        $rs = new MetaRecord($this->con->select('SELECT MAX(media_id) FROM ' . $this->prefix . App::postMedia()::MEDIA_TABLE_NAME));

        $media_id                = is_numeric($media_id = $rs->f(0)) ? (int) $media_id : 0;
        $this->stack['media_id'] = $media_id + 1;

        $rs = new MetaRecord($this->con->select('SELECT MAX(comment_id) FROM ' . $this->prefix . App::blog()::COMMENT_TABLE_NAME));

        $comment_id                = is_numeric($comment_id = $rs->f(0)) ? (int) $comment_id : 0;
        $this->stack['comment_id'] = $comment_id + 1;

        $rs = new MetaRecord($this->con->select('SELECT MAX(log_id) FROM ' . $this->prefix . App::log()::LOG_TABLE_NAME));

        $log_id                = is_numeric($log_id = $rs->f(0)) ? (int) $log_id : 0;
        $this->stack['log_id'] = $log_id + 1;

        $rs = new MetaRecord($this->con->select(
            'SELECT MAX(cat_rgt) AS cat_rgt FROM ' . $this->prefix . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
            "WHERE blog_id = '" . $this->con->escapeStr(App::blog()->id()) . "'"
        ));

        $cat_rgt = is_numeric($cat_rgt = $rs->cat_rgt) ? (int) $cat_rgt : 0;
        if ($cat_rgt > 0) {
            $this->has_categories                      = true;
            $this->stack['cat_lft'][App::blog()->id()] = $cat_rgt + 1;
        }

        $this->con->begin();

        $line        = false;
        $line_number = 0;

        try {
            $last_line_name = '';
            $constrained    = ['post', 'meta', 'post_media', 'ping', 'comment'];

            while (($line = $this->getLine()) instanceof FlatBackupItem) {
                $line_number = $line->__line;

                # import DC 1.2.x, we fix lines before insert
                if ($this->dc_major_version == '1.2') {
                    $this->prepareDC12line($line);
                }

                if ($last_line_name !== $line->__name) {
                    if (in_array($last_line_name, $constrained)) {
                        # UNDEFER
                        if ($this->con->syntax() === 'mysql') {
                            $this->con->execute('SET foreign_key_checks = 1');
                        }

                        if ($this->con->syntax() === 'postgresql') {
                            $this->con->execute('SET CONSTRAINTS ALL DEFERRED');
                        }
                    }

                    if (in_array($line->__name, $constrained)) {
                        # DEFER
                        if ($this->con->syntax() === 'mysql') {
                            $this->con->execute('SET foreign_key_checks = 0');
                        }

                        if ($this->con->syntax() === 'postgresql') {
                            $this->con->execute('SET CONSTRAINTS ALL IMMEDIATE');
                        }
                    }

                    $last_line_name = $line->__name;
                }

                try {
                    match ($line->__name) {
                        'category'   => $this->insertCategorySingle($line),
                        'link'       => $this->insertLinkSingle($line),
                        'post'       => $this->insertPostSingle($line),
                        'meta'       => $this->insertMetaSingle($line),
                        'media'      => $this->insertMediaSingle($line),
                        'post_media' => $this->insertPostMediaSingle($line),
                        'ping'       => $this->insertPingSingle($line),
                        'comment'    => $this->insertCommentSingle($line),
                    };
                } catch (UnhandledMatchError) {
                }

                # --BEHAVIOR-- importSingle -- FlatBackupItem, FlatImportV2
                App::behavior()->callBehavior('importSingleV2', $line, $this);
            }

            if ($this->con->syntax() === 'mysql') {
                $this->con->execute('SET foreign_key_checks = 1');
            }

            if ($this->con->syntax() === 'postgresql') {
                $this->con->execute('SET CONSTRAINTS ALL DEFERRED');
            }
        } catch (Exception $e) {
            if (is_resource($this->fp)) {
                @fclose($this->fp);
            }
            $this->con->rollback();

            $message = $e->getMessage() . ' - ' . sprintf(__('Error raised at line %s'), $line_number);

            throw new Exception($message, (int) $e->getCode(), $e);
        }
        if (is_resource($this->fp)) {
            @fclose($this->fp);
        }
        $this->con->commit();
    }

    public function importFull(): void
    {
        if ($this->mode !== 'full') {
            throw new Exception(__('File is not a full export.'));
        }

        if (!App::auth()->isSuperAdmin()) {
            throw new Exception(__('Permission denied.'));
        }

        $this->con->begin();
        $this->con->execute('DELETE FROM ' . $this->prefix . App::blog()::BLOG_TABLE_NAME);
        $this->con->execute('DELETE FROM ' . $this->prefix . App::postMedia()::MEDIA_TABLE_NAME);
        $this->con->execute('DELETE FROM ' . $this->prefix . Antispam::SPAMRULE_TABLE_NAME);
        $this->con->execute('DELETE FROM ' . $this->prefix . App::blogWorkspace()::NS_TABLE_NAME);
        $this->con->execute('DELETE FROM ' . $this->prefix . App::log()::LOG_TABLE_NAME);

        $line        = false;
        $line_number = 0;

        try {
            while (($line = $this->getLine()) !== false) {
                $line_number = $line->__line;

                try {
                    match ($line->__name) {
                        'blog'        => $this->insertBlog($line),
                        'category'    => $this->insertCategory($line),
                        'link'        => $this->insertLink($line),
                        'setting'     => $this->insertSetting($line),
                        'user'        => $this->insertUser($line),
                        'pref'        => $this->insertPref($line),
                        'permissions' => $this->insertPermissions($line),
                        'post'        => $this->insertPost($line),
                        'meta'        => $this->insertMeta($line),
                        'media'       => $this->insertMedia($line),
                        'post_media'  => $this->insertPostMedia($line),
                        'log'         => $this->insertLog($line),
                        'ping'        => $this->insertPing($line),
                        'comment'     => $this->insertComment($line),
                        'spamrule'    => $this->insertSpamRule($line),
                    };
                } catch (UnhandledMatchError) {
                }
                # --BEHAVIOR-- importFull -- FlatBackupItem, FlatImportV2
                App::behavior()->callBehavior('importFullV2', $line, $this);
            }
        } catch (Exception $e) {
            if (is_resource($this->fp)) {
                @fclose($this->fp);
            }
            $this->con->rollback();

            $message = $e->getMessage() . ' - ' . sprintf(__('Error raised at line %s'), $line_number);

            throw new Exception($message, (int) $e->getCode(), $e);
        }
        if (is_resource($this->fp)) {
            @fclose($this->fp);
        }
        $this->con->commit();
    }

    private function insertBlog(FlatBackupItem $blog): void
    {
        $this->cur_blog->clean();

        $this->cur_blog->blog_id     = is_string($blog->blog_id) ? $blog->blog_id : '';
        $this->cur_blog->blog_uid    = is_string($blog->blog_uid) ? $blog->blog_uid : '';
        $this->cur_blog->blog_creadt = is_string($blog->blog_creadt) ? $blog->blog_creadt : '';
        $this->cur_blog->blog_upddt  = is_string($blog->blog_upddt) ? $blog->blog_upddt : '';
        $this->cur_blog->blog_url    = is_string($blog->blog_url) ? $blog->blog_url : '';
        $this->cur_blog->blog_name   = is_string($blog->blog_name) ? $blog->blog_name : '';
        $this->cur_blog->blog_desc   = is_string($blog->blog_desc) ? $blog->blog_desc : '';

        $this->cur_blog->blog_status = $blog->exists('blog_status') && is_numeric($blog->blog_status) ? (int) $blog->blog_status : App::status()->blog()::ONLINE;

        $this->cur_blog->insert();
    }

    private function insertCategory(FlatBackupItem $category): void
    {
        $this->cur_category->clean();

        $this->cur_category->cat_id    = is_string($category->cat_id) ? $category->cat_id : '';
        $this->cur_category->blog_id   = is_string($category->blog_id) ? $category->blog_id : '';
        $this->cur_category->cat_title = is_string($category->cat_title) ? $category->cat_title : '';
        $this->cur_category->cat_url   = is_string($category->cat_url) ? $category->cat_url : '';
        $this->cur_category->cat_desc  = is_string($category->cat_desc) ? $category->cat_desc : '';

        if (!$this->has_categories && $category->exists('cat_lft') && $category->exists('cat_rgt')) {
            $this->cur_category->cat_lft = is_numeric($category->cat_lft) ? (int) $category->cat_lft : 0;
            $this->cur_category->cat_rgt = is_numeric($category->cat_rgt) ? (int) $category->cat_rgt : 0;
        } else {
            $blog_id = is_string($blog_id = $category->blog_id) ? $blog_id : '';
            if ($blog_id !== '') {
                if (!isset($this->stack['cat_lft'][$blog_id])) {
                    $this->stack['cat_lft'][$blog_id] = 2;
                }
                $this->cur_category->cat_lft = $this->stack['cat_lft'][$blog_id]++;
                $this->cur_category->cat_rgt = $this->stack['cat_lft'][$blog_id]++;
            }
        }

        $this->cur_category->insert();
    }

    private function insertLink(FlatBackupItem $link): void
    {
        $this->cur_link->clean();

        $this->cur_link->link_id       = is_numeric($link->link_id) ? (int) $link->link_id : 0;
        $this->cur_link->blog_id       = is_string($link->blog_id) ? $link->blog_id : '';
        $this->cur_link->link_href     = is_string($link->link_href) ? $link->link_href : '';
        $this->cur_link->link_title    = is_string($link->link_title) ? $link->link_title : '';
        $this->cur_link->link_desc     = is_string($link->link_desc) ? $link->link_desc : '';
        $this->cur_link->link_lang     = is_string($link->link_lang) ? $link->link_lang : '';
        $this->cur_link->link_xfn      = is_string($link->link_xfn) ? $link->link_xfn : '';
        $this->cur_link->link_position = is_numeric($link->link_position) ? (int) $link->link_position : 0;

        $this->cur_link->insert();
    }

    private function insertSetting(FlatBackupItem $setting): void
    {
        $this->cur_setting->clean();

        $this->cur_setting->setting_id    = is_string($setting->setting_id) ? $setting->setting_id : '';
        $this->cur_setting->blog_id       = $setting->blog_id && is_string($setting->blog_id) ? $setting->blog_id : null;
        $this->cur_setting->setting_ns    = is_string($setting->setting_ns) ? $setting->setting_ns : '';
        $this->cur_setting->setting_value = is_string($setting->setting_value) ? $setting->setting_value : '';
        $this->cur_setting->setting_type  = is_string($setting->setting_type) ? $setting->setting_type : '';
        $this->cur_setting->setting_label = is_string($setting->setting_label) ? $setting->setting_label : '';

        $this->cur_setting->insert();
    }

    private function insertPref(FlatBackupItem $pref): void
    {
        $pref_ws = is_string($pref_ws = $pref->pref_ws) ? $pref_ws : '';
        $pref_id = is_string($pref_id = $pref->pref_id) ? $pref_id : '';
        $user_id = is_string($user_id = $pref->user_id) ? $user_id : null;
        if ($this->prefExists($pref_ws, $pref_id, $user_id)) {
            return;
        }

        $this->cur_pref->clean();

        $this->cur_pref->pref_id    = $pref_id;
        $this->cur_pref->user_id    = $user_id;
        $this->cur_pref->pref_ws    = $pref_ws;
        $this->cur_pref->pref_value = is_string($pref->pref_value) ? $pref->pref_value : '';
        $this->cur_pref->pref_type  = is_string($pref->pref_type) ? $pref->pref_type : '';
        $this->cur_pref->pref_label = is_string($pref->pref_label) ? $pref->pref_label : '';

        $this->cur_pref->insert();
    }

    private function insertUser(FlatBackupItem $user): void
    {
        if (!is_string($user->user_id) || $this->userExists($user->user_id)) {
            return;
        }

        $this->cur_user->clean();

        $this->cur_user->user_id           = $user->user_id;
        $this->cur_user->user_super        = is_numeric($user->user_super) ? (int) $user->user_super : 0;
        $this->cur_user->user_pwd          = is_string($user->user_pwd) ? $user->user_pwd : '';
        $this->cur_user->user_recover_key  = is_string($user->user_recover_key) ? $user->user_recover_key : '';
        $this->cur_user->user_name         = is_string($user->user_name) ? $user->user_name : '';
        $this->cur_user->user_firstname    = is_string($user->user_firstname) ? $user->user_firstname : '';
        $this->cur_user->user_displayname  = is_string($user->user_displayname) ? $user->user_displayname : '';
        $this->cur_user->user_email        = is_string($user->user_email) ? $user->user_email : '';
        $this->cur_user->user_url          = is_string($user->user_url) ? $user->user_url : '';
        $this->cur_user->user_default_blog = $user->user_default_blog && is_string($user->user_default_blog) ? $user->user_default_blog : null;
        $this->cur_user->user_lang         = is_string($user->user_lang) ? $user->user_lang : '';
        $this->cur_user->user_tz           = is_string($user->user_tz) ? $user->user_tz : '';
        $this->cur_user->user_post_status  = is_numeric($user->user_post_status) ? (int) $user->user_post_status : 0;
        $this->cur_user->user_creadt       = is_string($user->user_creadt) ? $user->user_creadt : '';
        $this->cur_user->user_upddt        = is_string($user->user_upddt) ? $user->user_upddt : '';

        $this->cur_user->user_desc    = $user->exists('user_desc')    && is_string($user->user_desc) ? $user->user_desc : null;
        $this->cur_user->user_options = $user->exists('user_options') && is_string($user->user_options) ? $user->user_options : null;
        $this->cur_user->user_status  = $user->exists('user_status')  && is_numeric($user->user_status) ? (int) $user->user_status : App::status()->user()::ENABLED;

        $this->cur_user->insert();

        $this->stack['users'][$user->user_id] = true;
    }

    private function insertPermissions(FlatBackupItem $permissions): void
    {
        $this->cur_permissions->clean();

        $this->cur_permissions->user_id     = is_string($permissions->user_id) ? $permissions->user_id : '';
        $this->cur_permissions->blog_id     = is_string($permissions->blog_id) ? $permissions->blog_id : '';
        $this->cur_permissions->permissions = is_string($permissions->permissions) ? $permissions->permissions : '';

        $this->cur_permissions->insert();
    }

    private function insertPost(FlatBackupItem $post): void
    {
        $this->cur_post->clean();

        $cat_id = is_numeric($post->cat_id) ? (int) $post->cat_id : 0;
        if ($cat_id === 0) {
            $cat_id = null;
        }

        $post_password = $post->post_password && is_string($post->post_password) ? $post->post_password : null;

        $this->cur_post->post_id            = is_numeric($post->post_id) ? (int) $post->post_id : 0;
        $this->cur_post->blog_id            = is_string($post->blog_id) ? $post->blog_id : '';
        $this->cur_post->user_id            = (string) $this->getUserId($post->user_id);
        $this->cur_post->cat_id             = $cat_id;
        $this->cur_post->post_dt            = is_string($post->post_dt) ? $post->post_dt : '';
        $this->cur_post->post_creadt        = is_string($post->post_creadt) ? $post->post_creadt : '';
        $this->cur_post->post_upddt         = is_string($post->post_upddt) ? $post->post_upddt : '';
        $this->cur_post->post_password      = $post_password;
        $this->cur_post->post_type          = is_string($post->post_type) ? $post->post_type : '';
        $this->cur_post->post_format        = is_string($post->post_format) ? $post->post_format : '';
        $this->cur_post->post_url           = is_string($post->post_url) ? $post->post_url : '';
        $this->cur_post->post_lang          = is_string($post->post_lang) ? $post->post_lang : '';
        $this->cur_post->post_title         = is_string($post->post_title) ? $post->post_title : '';
        $this->cur_post->post_excerpt       = is_string($post->post_excerpt) ? $post->post_excerpt : '';
        $this->cur_post->post_excerpt_xhtml = is_string($post->post_excerpt_xhtml) ? $post->post_excerpt_xhtml : '';
        $this->cur_post->post_content       = is_string($post->post_content) ? $post->post_content : '';
        $this->cur_post->post_content_xhtml = is_string($post->post_content_xhtml) ? $post->post_content_xhtml : '';
        $this->cur_post->post_notes         = is_string($post->post_notes) ? $post->post_notes : '';
        $this->cur_post->post_words         = is_string($post->post_words) ? $post->post_words : '';
        $this->cur_post->post_meta          = is_string($post->post_meta) ? $post->post_meta : '';
        $this->cur_post->post_status        = is_numeric($post->post_status) ? (int) $post->post_status : 0;
        $this->cur_post->post_selected      = is_numeric($post->post_selected) ? (int) $post->post_selected : 0;
        $this->cur_post->post_open_comment  = is_numeric($post->post_open_comment) ? (int) $post->post_open_comment : 0;
        $this->cur_post->post_open_tb       = is_numeric($post->post_open_tb) ? (int) $post->post_open_tb : 0;
        $this->cur_post->nb_comment         = is_numeric($post->nb_comment) ? (int) $post->nb_comment : 0;
        $this->cur_post->nb_trackback       = is_numeric($post->nb_trackback) ? (int) $post->nb_trackback : 0;
        $this->cur_post->post_position      = is_numeric($post->post_position) ? (int) $post->post_position : 0;
        $this->cur_post->post_firstpub      = is_numeric($post->post_firstpub) ? (int) $post->post_firstpub : 0;

        $this->cur_post->post_tz = $post->exists('post_tz') && is_string($post->post_tz) ? $post->post_tz : 'UTC';

        $this->cur_post->insert();
    }

    private function insertMeta(FlatBackupItem $meta): void
    {
        $this->cur_meta->clean();

        $this->cur_meta->meta_id   = is_string($meta->meta_id) ? $meta->meta_id : '';
        $this->cur_meta->meta_type = is_string($meta->meta_type) ? $meta->meta_type : '';
        $this->cur_meta->post_id   = is_numeric($meta->post_id) ? (int) $meta->post_id : 0;

        $this->cur_meta->insert();
    }

    private function insertMedia(FlatBackupItem $media): void
    {
        $this->cur_media->clean();

        $this->cur_media->media_id      = is_numeric($media->media_id) ? (int) $media->media_id : 0;
        $this->cur_media->user_id       = is_string($media->user_id) ? $media->user_id : '';
        $this->cur_media->media_path    = is_string($media->media_path) ? $media->media_path : '';
        $this->cur_media->media_title   = is_string($media->media_title) ? $media->media_title : '';
        $this->cur_media->media_file    = is_string($media->media_file) ? $media->media_file : '';
        $this->cur_media->media_meta    = is_string($media->media_meta) ? $media->media_meta : '';
        $this->cur_media->media_dt      = is_string($media->media_dt) ? $media->media_dt : '';
        $this->cur_media->media_creadt  = is_string($media->media_creadt) ? $media->media_creadt : '';
        $this->cur_media->media_upddt   = is_string($media->media_upddt) ? $media->media_upddt : '';
        $this->cur_media->media_private = is_numeric($media->media_private) ? (int) $media->media_private : 0;

        if ($media->exists('media_dir') && is_string($media->media_dir)) {
            $media_dir = $media->media_dir;
        } else {
            $media_dir = is_string($media->media_file) ? dirname($media->media_file) : '';
        }
        $this->cur_media->media_dir = $media_dir;

        if (!$this->mediaExists()) {
            $this->cur_media->insert();
        }
    }

    private function insertPostMedia(FlatBackupItem $post_media): void
    {
        $this->cur_post_media->clean();

        $this->cur_post_media->media_id = is_numeric($post_media->media_id) ? (int) $post_media->media_id : 0;
        $this->cur_post_media->post_id  = is_numeric($post_media->post_id) ? (int) $post_media->post_id : 0;

        $this->cur_post_media->insert();
    }

    private function insertLog(FlatBackupItem $log): void
    {
        $this->cur_log->clean();

        $this->cur_log->log_id    = is_numeric($log->log_id) ? (int) $log->log_id : 0;
        $this->cur_log->user_id   = is_string($log->user_id) ? $log->user_id : '';
        $this->cur_log->log_table = is_string($log->log_table) ? $log->log_table : '';
        $this->cur_log->log_dt    = is_string($log->log_dt) ? $log->log_dt : '';
        $this->cur_log->log_ip    = is_string($log->log_ip) ? $log->log_ip : '';
        $this->cur_log->log_msg   = is_string($log->log_msg) ? $log->log_msg : '';

        $this->cur_log->insert();
    }

    private function insertPing(FlatBackupItem $ping): void
    {
        $this->cur_ping->clean();

        $this->cur_ping->post_id  = is_numeric($ping->post_id) ? (int) $ping->post_id : 0;
        $this->cur_ping->ping_url = is_string($ping->ping_url) ? $ping->ping_url : '';
        $this->cur_ping->ping_dt  = is_string($ping->ping_dt) ? $ping->ping_dt : '';

        $this->cur_ping->insert();
    }

    private function insertComment(FlatBackupItem $comment): void
    {
        $this->cur_comment->clean();

        $this->cur_comment->comment_id          = is_numeric($comment->comment_id) ? (int) $comment->comment_id : 0;
        $this->cur_comment->post_id             = is_numeric($comment->post_id) ? (int) $comment->post_id : 0;
        $this->cur_comment->comment_dt          = is_string($comment->comment_dt) ? $comment->comment_dt : '';
        $this->cur_comment->comment_upddt       = is_string($comment->comment_upddt) ? $comment->comment_upddt : '';
        $this->cur_comment->comment_author      = is_string($comment->comment_author) ? $comment->comment_author : '';
        $this->cur_comment->comment_email       = is_string($comment->comment_email) ? $comment->comment_email : '';
        $this->cur_comment->comment_site        = is_string($comment->comment_site) ? $comment->comment_site : '';
        $this->cur_comment->comment_content     = is_string($comment->comment_content) ? $comment->comment_content : '';
        $this->cur_comment->comment_words       = is_string($comment->comment_words) ? $comment->comment_words : '';
        $this->cur_comment->comment_ip          = is_string($comment->comment_ip) ? $comment->comment_ip : '';
        $this->cur_comment->comment_status      = is_numeric($comment->comment_status) ? (int) $comment->comment_status : 0;
        $this->cur_comment->comment_spam_status = is_string($comment->comment_spam_status) ? $comment->comment_spam_status : '';
        $this->cur_comment->comment_trackback   = is_numeric($comment->comment_trackback) ? (int) $comment->comment_trackback : 0;

        $this->cur_comment->comment_tz          = $comment->exists('comment_tz')          && is_string($comment->comment_tz) ? $comment->comment_tz : 'UTC';
        $this->cur_comment->comment_spam_filter = $comment->exists('comment_spam_filter') && is_string($comment->comment_spam_filter) ? $comment->comment_spam_filter : null;

        $this->cur_comment->insert();
    }

    private function insertSpamRule(FlatBackupItem $spamrule): void
    {
        $this->cur_spamrule->clean();

        $this->cur_spamrule->rule_id      = is_numeric($spamrule->rule_id) ? (int) $spamrule->rule_id : 0;
        $this->cur_spamrule->blog_id      = $spamrule->blog_id && is_string($spamrule->blog_id) ? $spamrule->blog_id : null;
        $this->cur_spamrule->rule_type    = is_string($spamrule->rule_type) ? $spamrule->rule_type : '';
        $this->cur_spamrule->rule_content = is_string($spamrule->rule_content) ? $spamrule->rule_content : '';

        $this->cur_spamrule->insert();
    }

    private function insertCategorySingle(FlatBackupItem $category): void
    {
        $this->cur_category->clean();

        $m = $this->stack['categories'] instanceof MetaRecord ? $this->searchCategory($this->stack['categories'], $category->cat_url) : false;

        $old_id = is_numeric($old_id = $category->cat_id) ? (int) $old_id : 0;
        if ($m !== false) {
            $cat_id = $m;
        } else {
            $cat_id            = $this->stack['cat_id'];
            $category->cat_id  = (string) $cat_id;
            $category->blog_id = $this->blog_id;

            $this->insertCategory($category);
            $this->stack['cat_id']++;
        }

        $this->old_ids['category'][$old_id] = $cat_id;
    }

    private function insertLinkSingle(FlatBackupItem $link): void
    {
        $link->blog_id = $this->blog_id;
        $link->link_id = (string) $this->stack['link_id'];

        $this->insertLink($link);
        $this->stack['link_id']++;
    }

    private function insertPostSingle(FlatBackupItem $post): void
    {
        $cat_id = is_numeric($cat_id = $post->cat_id) ? (int) $cat_id : 0;
        if ($cat_id === 0 || isset($this->old_ids['category'][$cat_id])) {
            $post_id     = is_numeric($post_id = $post->post_id) ? (int) $post->post_id : 0;
            $new_post_id = $this->stack['post_id'];

            $this->old_ids['post'][$post_id] = $new_post_id;

            $cat_id = $cat_id !== 0 ? $this->old_ids['category'][$cat_id] : null;

            $post->post_id = (string) $new_post_id;
            $post->cat_id  = (string) $cat_id;
            $post->blog_id = $this->blog_id;

            $post_url   = is_string($post_url = $post->post_url) ? $post_url : '';
            $post_dt    = is_string($post_dt = $post->post_dt) ? $post_dt : '';
            $post_title = is_string($post_title = $post->post_title) ? $post_title : '';

            $post->post_url = App::blog()->getPostURL(
                $post_url,
                $post_dt,
                $post_title,
                $new_post_id
            );

            $this->insertPost($post);
            $this->stack['post_id']++;
        } else {
            $this->throwIdError($post->__name, $post->__line, 'category');
        }
    }

    private function insertMetaSingle(FlatBackupItem $meta): void
    {
        $post_id = is_numeric($post_id = $meta->post_id) ? (int) $meta->post_id : 0;
        if ($post_id !== 0 && isset($this->old_ids['post'][$post_id])) {
            $meta->post_id = (string) $this->old_ids['post'][$post_id];
            $this->insertMeta($meta);
        } else {
            $this->throwIdError($meta->__name, $meta->__line, 'post');
        }
    }

    private function insertMediaSingle(FlatBackupItem $media): void
    {
        $media_id = $this->stack['media_id'];
        $old_id   = is_numeric($media->media_id) ? (int) $media->media_id : 0;

        $media_path = is_string($media_path = App::blog()->settings()->system->public_path) ? $media_path : '';

        $media->media_id   = (string) $media_id;
        $media->media_path = $media_path;
        $media->user_id    = $this->getUserId($media->user_id) ?? '';

        $this->insertMedia($media);
        $this->stack['media_id']++;
        $this->old_ids['media'][$old_id] = $media_id;
    }

    private function insertPostMediaSingle(FlatBackupItem $post_media): void
    {
        $post_id  = is_numeric($post_id = $post_media->post_id) ? (int) $post_media->post_id : 0;
        $media_id = is_numeric($media_id = $post_media->media_id) ? (int) $post_media->media_id : 0;

        if ($post_id !== 0 && $media_id !== 0 && isset($this->old_ids['media'][$media_id]) && isset($this->old_ids['post'][$post_id])) {
            $post_media->media_id = (string) $this->old_ids['media'][$media_id];
            $post_media->post_id  = (string) $this->old_ids['post'][$post_id];

            $this->insertPostMedia($post_media);
        } elseif ($media_id === 0 || !isset($this->old_ids['media'][$media_id])) {
            $this->throwIdError($post_media->__name, $post_media->__line, 'media');
        } else {
            $this->throwIdError($post_media->__name, $post_media->__line, 'post');
        }
    }

    private function insertPingSingle(FlatBackupItem $ping): void
    {
        $post_id = is_numeric($post_id = $ping->post_id) ? (int) $ping->post_id : 0;

        if ($post_id !== 0 && isset($this->old_ids['post'][$post_id])) {
            $ping->post_id = (string) $this->old_ids['post'][$post_id];

            $this->insertPing($ping);
        } else {
            $this->throwIdError($ping->__name, $ping->__line, 'post');
        }
    }

    private function insertCommentSingle(FlatBackupItem $comment): void
    {
        $post_id = is_numeric($post_id = $comment->post_id) ? (int) $comment->post_id : 0;

        if ($post_id !== 0 && isset($this->old_ids['post'][$post_id])) {
            $comment_id = $this->stack['comment_id'];

            $comment->comment_id = (string) $comment_id;
            $comment->post_id    = (string) $this->old_ids['post'][$post_id];

            $this->insertComment($comment);
            $this->stack['comment_id']++;
        } else {
            $this->throwIdError($comment->__name, $comment->__line, 'post');
        }
    }

    private function throwIdError(string $name, int $line, string $related): mixed
    {
        throw new Exception(sprintf(
            __('ID of "%3$s" does not match on record "%1$s" at line %2$s of backup file.'),
            Html::escapeHTML($name),
            Html::escapeHTML((string) $line),
            Html::escapeHTML($related)
        ));
    }

    public function searchCategory(MetaRecord $rs, mixed $url): false|int
    {
        while ($rs->fetch()) {
            $cat_url = is_string($cat_url = $rs->cat_url) ? $cat_url : '';
            if ($cat_url === $url) {
                return is_numeric($cat_id = $rs->cat_id) ? (int) $cat_id : 0;
            }
        }

        return false;
    }

    public function getUserId(mixed $user_id): ?string
    {
        if (!is_string($user_id)) {
            return null;
        }

        if (!$this->userExists($user_id)) {
            if (App::auth()->isSuperAdmin()) {
                # Sanitizes user_id and create a lambda user
                $user_id = (string) preg_replace('/[^A-Za-z0-9]$/', '', $user_id);
                $user_id .= strlen($user_id) < 2 ? '-a' : '';

                # We change user_id, we need to check again
                if (!$this->userExists($user_id)) {
                    $this->cur_user->clean();
                    $this->cur_user->user_id  = $user_id;
                    $this->cur_user->user_pwd = md5(uniqid());

                    App::users()->addUser($this->cur_user);

                    $this->stack['users'][$user_id] = true;
                }
            } else {
                # Returns current user id
                $user_id = App::auth()->userID();
            }
        }

        return $user_id;
    }

    private function userExists(mixed $user_id): bool
    {
        if (!is_string($user_id)) {
            return false;
        }

        if (isset($this->stack['users'][$user_id])) {
            return $this->stack['users'][$user_id];
        }

        $strReq = 'SELECT user_id ' .
        'FROM ' . $this->prefix . App::auth()::USER_TABLE_NAME . ' ' .
        "WHERE user_id = '" . $this->con->escapeStr($user_id) . "' ";

        $rs = new MetaRecord($this->con->select($strReq));

        $this->stack['users'][$user_id] = !$rs->isEmpty();

        return $this->stack['users'][$user_id];
    }

    private function prefExists(string $pref_ws, string $pref_id, ?string $user_id): bool
    {
        $strReq = 'SELECT pref_id,pref_ws,user_id ' .
        'FROM ' . $this->prefix . App::userWorkspace()::WS_TABLE_NAME . ' ' .
        "WHERE pref_id = '" . $this->con->escapeStr($pref_id) . "' " .
        "AND pref_ws = '" . $this->con->escapeStr($pref_ws) . "' ";
        if (!$user_id) {
            $strReq .= 'AND user_id IS NULL ';
        } else {
            $strReq .= "AND user_id = '" . $this->con->escapeStr($user_id) . "' ";
        }

        $rs = new MetaRecord($this->con->select($strReq));

        return !$rs->isEmpty();
    }

    private function mediaExists(): bool
    {
        $media_path = is_string($media_path = $this->cur_media->media_path) ? $media_path : '';
        $media_file = is_string($media_file = $this->cur_media->media_file) ? $media_file : '';

        $strReq = 'SELECT media_id ' .
        'FROM ' . $this->prefix . App::postMedia()::MEDIA_TABLE_NAME . ' ' .
        "WHERE media_path = '" . $this->con->escapeStr($media_path) . "' " .
        "AND media_file = '" . $this->con->escapeStr($media_file) . "' ";

        $rs = new MetaRecord($this->con->select($strReq));

        return !$rs->isEmpty();
    }

    private function prepareDC12line(FlatBackupItem &$line): void
    {
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

                $post_title        = is_string($post_title = $line->post_title) ? $post_title : '';
                $post_dt           = is_string($post_dt = $line->post_dt) ? $post_dt : '';
                $post_id           = is_string($post_id = $line->post_id) ? $post_id : '';
                $post_titre_url    = is_string($post_titre_url = $line->post_titre_url) ? $post_titre_url : '';
                $post_content_wiki = is_string($post_content_wiki = $line->post_content_wiki) ? $post_content_wiki : '';
                $post_content_html = is_string($post_content_html = $line->post_content) ? $post_content_html : '';
                $post_excerpt_wiki = is_string($post_excerpt_wiki = $line->post_chapo_wiki) ? $post_excerpt_wiki : '';
                $post_excerpt_html = is_string($post_excerpt_html = $line->post_chapo) ? $post_excerpt_html : '';
                $post_status       = is_numeric($line->post_pub) ? (int) $line->post_pub : 0;

                $line->post_title         = Html::decodeEntities($post_title);
                $line->post_url           = date('Y/m/d/', (int) strtotime($post_dt)) . $post_id . '-' . $post_titre_url;
                $line->post_url           = substr($line->post_url, 0, 255);
                $line->post_format        = $post_content_wiki === '' ? 'xhtml' : 'wiki';
                $line->post_content_xhtml = $post_content_html;
                $line->post_excerpt_xhtml = $post_excerpt_html;

                if ($line->post_format === 'wiki') {
                    $line->post_content = $post_content_wiki;
                    $line->post_excerpt = $post_excerpt_wiki;
                } else {
                    $line->post_excerpt = $post_excerpt_html;
                }

                $line->post_status = (string) $post_status;
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

                $comment_site   = is_string($comment_site = $line->comment_site) ? $comment_site : '';
                $comment_status = is_numeric($line->comment_pub) ? (int) $line->comment_pub : 0;

                if ($comment_site !== '' && !preg_match('!^http(s)?://.*$!', $comment_site, $m)) {
                    $line->comment_site = 'http://' . $comment_site;
                }
                $line->comment_status = (string) $comment_status;

                $line->drop('comment_pub');

                break;
        }

        # --BEHAVIOR-- importPrepareDC12 -- line, FlatBackup
        App::behavior()->callBehavior('importPrepareDC12V2', $line, $this);
    }
}
