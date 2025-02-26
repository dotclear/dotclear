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
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Plugin\antispam\Antispam;
use Dotclear\Plugin\blogroll\Blogroll;
use UnhandledMatchError;

/**
 * @brief   The module flat import handler.
 * @ingroup importExport
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
     * @var array<string, Cursor>
     */
    protected array $cur_extra;

    /**
     * @var        array<string, mixed>
     */
    public $old_ids = [
        'category' => [],
        'post'     => [],
        'media'    => [],
    ];

    /**
     * @var        array<string, mixed>
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

        $first_line = fgets($this->fp);
        if ($first_line === false) {
            throw new Exception(__('Unable to read the DotClear backup file.'));
        }

        if (!str_starts_with($first_line, '///DOTCLEAR|')) {
            throw new Exception(__('File is not a DotClear backup.'));
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

        $this->con    = App::con();
        $this->prefix = App::con()->prefix();

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

        $rs                    = new MetaRecord($this->con->select('SELECT MAX(cat_id) FROM ' . $this->prefix . App::blog()->categories()::CATEGORY_TABLE_NAME));
        $this->stack['cat_id'] = ((int) $rs->f(0)) + 1;

        $rs                     = new MetaRecord($this->con->select('SELECT MAX(link_id) FROM ' . $this->prefix . Blogroll::LINK_TABLE_NAME));
        $this->stack['link_id'] = ((int) $rs->f(0)) + 1;

        $rs                     = new MetaRecord($this->con->select('SELECT MAX(post_id) FROM ' . $this->prefix . App::blog()::POST_TABLE_NAME));
        $this->stack['post_id'] = ((int) $rs->f(0)) + 1;

        $rs                      = new MetaRecord($this->con->select('SELECT MAX(media_id) FROM ' . $this->prefix . App::postMedia()::MEDIA_TABLE_NAME));
        $this->stack['media_id'] = ((int) $rs->f(0)) + 1;

        $rs                        = new MetaRecord($this->con->select('SELECT MAX(comment_id) FROM ' . $this->prefix . App::blog()::COMMENT_TABLE_NAME));
        $this->stack['comment_id'] = ((int) $rs->f(0)) + 1;

        $rs                    = new MetaRecord($this->con->select('SELECT MAX(log_id) FROM ' . $this->prefix . App::log()::LOG_TABLE_NAME));
        $this->stack['log_id'] = ((int) $rs->f(0)) + 1;

        $rs = new MetaRecord($this->con->select(
            'SELECT MAX(cat_rgt) AS cat_rgt FROM ' . $this->prefix . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
            "WHERE blog_id = '" . $this->con->escapeStr(App::blog()->id()) . "'"
        ));

        if ((int) $rs->cat_rgt > 0) {
            $this->has_categories                      = true;
            $this->stack['cat_lft'][App::blog()->id()] = (int) $rs->cat_rgt + 1;
        }

        $this->con->begin();

        $line = false;

        try {
            $last_line_name = '';
            $constrained    = ['post', 'meta', 'post_media', 'ping', 'comment'];

            while (($line = $this->getLine()) !== false) {
                # import DC 1.2.x, we fix lines before insert
                if ($this->dc_major_version == '1.2') {
                    $this->prepareDC12line($line);
                }

                if ($last_line_name != $line->__name) {
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
            @fclose($this->fp);
            $this->con->rollback();

            throw new Exception($e->getMessage() . ' - ' . sprintf(__('Error raised at line %s'), $line?->__line), (int) $e->getCode(), $e); // @phpstan-ignore-line
        }
        @fclose($this->fp);
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

        $line = false;

        try {
            while (($line = $this->getLine()) !== false) {
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
            @fclose($this->fp);
            $this->con->rollback();

            throw new Exception($e->getMessage() . ' - ' . sprintf(__('Error raised at line %s'), $line?->__line), (int) $e->getCode(), $e); // @phpstan-ignore-line
        }
        @fclose($this->fp);
        $this->con->commit();
    }

    private function insertBlog(mixed $blog): void
    {
        $this->cur_blog->clean();

        $this->cur_blog->blog_id     = (string) $blog->blog_id;
        $this->cur_blog->blog_uid    = (string) $blog->blog_uid;
        $this->cur_blog->blog_creadt = (string) $blog->blog_creadt;
        $this->cur_blog->blog_upddt  = (string) $blog->blog_upddt;
        $this->cur_blog->blog_url    = (string) $blog->blog_url;
        $this->cur_blog->blog_name   = (string) $blog->blog_name;
        $this->cur_blog->blog_desc   = (string) $blog->blog_desc;

        $this->cur_blog->blog_status = $blog->exists('blog_status') ? (int) $blog->blog_status : App::status()->blog()::ONLINE;

        $this->cur_blog->insert();
    }

    private function insertCategory(mixed $category): void
    {
        $this->cur_category->clean();

        $this->cur_category->cat_id    = (string) $category->cat_id;
        $this->cur_category->blog_id   = (string) $category->blog_id;
        $this->cur_category->cat_title = (string) $category->cat_title;
        $this->cur_category->cat_url   = (string) $category->cat_url;
        $this->cur_category->cat_desc  = (string) $category->cat_desc;

        if (!$this->has_categories && $category->exists('cat_lft') && $category->exists('cat_rgt')) {
            $this->cur_category->cat_lft = (int) $category->cat_lft;
            $this->cur_category->cat_rgt = (int) $category->cat_rgt;
        } else {
            if (!isset($this->stack['cat_lft'][$category->blog_id])) {
                $this->stack['cat_lft'][$category->blog_id] = 2;
            }
            $this->cur_category->cat_lft = $this->stack['cat_lft'][$category->blog_id]++;
            $this->cur_category->cat_rgt = $this->stack['cat_lft'][$category->blog_id]++;
        }

        $this->cur_category->insert();
    }

    private function insertLink(mixed $link): void
    {
        $this->cur_link->clean();

        $this->cur_link->link_id       = (int) $link->link_id;
        $this->cur_link->blog_id       = (string) $link->blog_id;
        $this->cur_link->link_href     = (string) $link->link_href;
        $this->cur_link->link_title    = (string) $link->link_title;
        $this->cur_link->link_desc     = (string) $link->link_desc;
        $this->cur_link->link_lang     = (string) $link->link_lang;
        $this->cur_link->link_xfn      = (string) $link->link_xfn;
        $this->cur_link->link_position = (int) $link->link_position;

        $this->cur_link->insert();
    }

    private function insertSetting(mixed $setting): void
    {
        $this->cur_setting->clean();

        $this->cur_setting->setting_id    = (string) $setting->setting_id;
        $this->cur_setting->blog_id       = $setting->blog_id ? (string) $setting->blog_id : null;
        $this->cur_setting->setting_ns    = (string) $setting->setting_ns;
        $this->cur_setting->setting_value = (string) $setting->setting_value;
        $this->cur_setting->setting_type  = (string) $setting->setting_type;
        $this->cur_setting->setting_label = (string) $setting->setting_label;

        $this->cur_setting->insert();
    }

    private function insertPref(mixed $pref): void
    {
        if ($this->prefExists($pref->pref_ws, $pref->pref_id, $pref->user_id)) {
            return;
        }

        $this->cur_pref->clean();

        $this->cur_pref->pref_id    = (string) $pref->pref_id;
        $this->cur_pref->user_id    = $pref->user_id ? (string) $pref->user_id : null;
        $this->cur_pref->pref_ws    = (string) $pref->pref_ws;
        $this->cur_pref->pref_value = (string) $pref->pref_value;
        $this->cur_pref->pref_type  = (string) $pref->pref_type;
        $this->cur_pref->pref_label = (string) $pref->pref_label;

        $this->cur_pref->insert();
    }

    private function insertUser(mixed $user): void
    {
        if ($this->userExists($user->user_id)) {
            return;
        }

        $this->cur_user->clean();

        $this->cur_user->user_id           = (string) $user->user_id;
        $this->cur_user->user_super        = (int) $user->user_super;
        $this->cur_user->user_pwd          = (string) $user->user_pwd;
        $this->cur_user->user_recover_key  = (string) $user->user_recover_key;
        $this->cur_user->user_name         = (string) $user->user_name;
        $this->cur_user->user_firstname    = (string) $user->user_firstname;
        $this->cur_user->user_displayname  = (string) $user->user_displayname;
        $this->cur_user->user_email        = (string) $user->user_email;
        $this->cur_user->user_url          = (string) $user->user_url;
        $this->cur_user->user_default_blog = $user->user_default_blog ? (string) $user->user_default_blog : null;
        $this->cur_user->user_lang         = (string) $user->user_lang;
        $this->cur_user->user_tz           = (string) $user->user_tz;
        $this->cur_user->user_post_status  = (int) $user->user_post_status;
        $this->cur_user->user_creadt       = (string) $user->user_creadt;
        $this->cur_user->user_upddt        = (string) $user->user_upddt;

        $this->cur_user->user_desc    = $user->exists('user_desc') ? (string) $user->user_desc : null;
        $this->cur_user->user_options = $user->exists('user_options') ? (string) $user->user_options : null;
        $this->cur_user->user_status  = $user->exists('user_status') ? (int) $user->user_status : App::status()->user()::ENABLED;

        $this->cur_user->insert();

        $this->stack['users'][$user->user_id] = true;
    }

    private function insertPermissions(mixed $permissions): void
    {
        $this->cur_permissions->clean();

        $this->cur_permissions->user_id     = (string) $permissions->user_id;
        $this->cur_permissions->blog_id     = (string) $permissions->blog_id;
        $this->cur_permissions->permissions = (string) $permissions->permissions;

        $this->cur_permissions->insert();
    }

    private function insertPost(mixed $post): void
    {
        $this->cur_post->clean();

        $cat_id = (int) $post->cat_id;
        if ($cat_id === 0) {
            $cat_id = null;
        }

        $post_password = $post->post_password ? (string) $post->post_password : null;

        $this->cur_post->post_id            = (int) $post->post_id;
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
        $this->cur_post->post_status        = (int) $post->post_status;
        $this->cur_post->post_selected      = (int) $post->post_selected;
        $this->cur_post->post_open_comment  = (int) $post->post_open_comment;
        $this->cur_post->post_open_tb       = (int) $post->post_open_tb;
        $this->cur_post->nb_comment         = (int) $post->nb_comment;
        $this->cur_post->nb_trackback       = (int) $post->nb_trackback;
        $this->cur_post->post_position      = (int) $post->post_position;
        $this->cur_post->post_firstpub      = (int) $post->post_firstpub;

        $this->cur_post->post_tz = $post->exists('post_tz') ? (string) $post->post_tz : 'UTC';

        $this->cur_post->insert();
    }

    private function insertMeta(mixed $meta): void
    {
        $this->cur_meta->clean();

        $this->cur_meta->meta_id   = (string) $meta->meta_id;
        $this->cur_meta->meta_type = (string) $meta->meta_type;
        $this->cur_meta->post_id   = (int) $meta->post_id;

        $this->cur_meta->insert();
    }

    private function insertMedia(mixed $media): void
    {
        $this->cur_media->clean();

        $this->cur_media->media_id      = (int) $media->media_id;
        $this->cur_media->user_id       = (string) $media->user_id;
        $this->cur_media->media_path    = (string) $media->media_path;
        $this->cur_media->media_title   = (string) $media->media_title;
        $this->cur_media->media_file    = (string) $media->media_file;
        $this->cur_media->media_meta    = (string) $media->media_meta;
        $this->cur_media->media_dt      = (string) $media->media_dt;
        $this->cur_media->media_creadt  = (string) $media->media_creadt;
        $this->cur_media->media_upddt   = (string) $media->media_upddt;
        $this->cur_media->media_private = (int) $media->media_private;

        $this->cur_media->media_dir = $media->exists('media_dir') ? (string) $media->media_dir : dirname((string) $media->media_file);

        if (!$this->mediaExists()) {
            $this->cur_media->insert();
        }
    }

    private function insertPostMedia(mixed $post_media): void
    {
        $this->cur_post_media->clean();

        $this->cur_post_media->media_id = (int) $post_media->media_id;
        $this->cur_post_media->post_id  = (int) $post_media->post_id;

        $this->cur_post_media->insert();
    }

    private function insertLog(mixed $log): void
    {
        $this->cur_log->clean();

        $this->cur_log->log_id    = (int) $log->log_id;
        $this->cur_log->user_id   = (string) $log->user_id;
        $this->cur_log->log_table = (string) $log->log_table;
        $this->cur_log->log_dt    = (string) $log->log_dt;
        $this->cur_log->log_ip    = (string) $log->log_ip;
        $this->cur_log->log_msg   = (string) $log->log_msg;

        $this->cur_log->insert();
    }

    private function insertPing(mixed $ping): void
    {
        $this->cur_ping->clean();

        $this->cur_ping->post_id  = (int) $ping->post_id;
        $this->cur_ping->ping_url = (string) $ping->ping_url;
        $this->cur_ping->ping_dt  = (string) $ping->ping_dt;

        $this->cur_ping->insert();
    }

    private function insertComment(mixed $comment): void
    {
        $this->cur_comment->clean();

        $this->cur_comment->comment_id          = (int) $comment->comment_id;
        $this->cur_comment->post_id             = (int) $comment->post_id;
        $this->cur_comment->comment_dt          = (string) $comment->comment_dt;
        $this->cur_comment->comment_upddt       = (string) $comment->comment_upddt;
        $this->cur_comment->comment_author      = (string) $comment->comment_author;
        $this->cur_comment->comment_email       = (string) $comment->comment_email;
        $this->cur_comment->comment_site        = (string) $comment->comment_site;
        $this->cur_comment->comment_content     = (string) $comment->comment_content;
        $this->cur_comment->comment_words       = (string) $comment->comment_words;
        $this->cur_comment->comment_ip          = (string) $comment->comment_ip;
        $this->cur_comment->comment_status      = (int) $comment->comment_status;
        $this->cur_comment->comment_spam_status = (string) $comment->comment_spam_status;
        $this->cur_comment->comment_trackback   = (int) $comment->comment_trackback;

        $this->cur_comment->comment_tz          = $comment->exists('comment_tz') ? (string) $comment->comment_tz : 'UTC';
        $this->cur_comment->comment_spam_filter = $comment->exists('comment_spam_filter') ? (string) $comment->comment_spam_filter : null;

        $this->cur_comment->insert();
    }

    private function insertSpamRule(mixed $spamrule): void
    {
        $this->cur_spamrule->clean();

        $this->cur_spamrule->rule_id      = (int) $spamrule->rule_id;
        $this->cur_spamrule->blog_id      = $spamrule->blog_id ? (string) $spamrule->blog_id : null;
        $this->cur_spamrule->rule_type    = (string) $spamrule->rule_type;
        $this->cur_spamrule->rule_content = (string) $spamrule->rule_content;

        $this->cur_spamrule->insert();
    }

    private function insertCategorySingle(mixed $category): void
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

        $this->old_ids['category'][(int) $old_id] = $cat_id;
    }

    private function insertLinkSingle(mixed $link): void
    {
        $link->blog_id = $this->blog_id;
        $link->link_id = $this->stack['link_id'];

        $this->insertLink($link);
        $this->stack['link_id']++;
    }

    private function insertPostSingle(mixed $post): void
    {
        if (!$post->cat_id || isset($this->old_ids['category'][(int) $post->cat_id])) {
            $post_id                                     = $this->stack['post_id'];
            $this->old_ids['post'][(int) $post->post_id] = $post_id;

            $cat_id = $post->cat_id ? $this->old_ids['category'][(int) $post->cat_id] : null;

            $post->post_id = $post_id;
            $post->cat_id  = $cat_id;
            $post->blog_id = $this->blog_id;

            $post->post_url = App::blog()->getPostURL(
                $post->post_url,
                $post->post_dt,
                $post->post_title,
                $post->post_id
            );

            $this->insertPost($post);
            $this->stack['post_id']++;
        } else {
            $this->throwIdError($post->__name, $post->__line, 'category');
        }
    }

    private function insertMetaSingle(mixed $meta): void
    {
        if (isset($this->old_ids['post'][(int) $meta->post_id])) {
            $meta->post_id = $this->old_ids['post'][(int) $meta->post_id];
            $this->insertMeta($meta);
        } else {
            $this->throwIdError($meta->__name, $meta->__line, 'post');
        }
    }

    private function insertMediaSingle(mixed $media): void
    {
        $media_id = $this->stack['media_id'];
        $old_id   = $media->media_id;

        $media->media_id   = $media_id;
        $media->media_path = App::blog()->settings()->system->public_path;
        $media->user_id    = $this->getUserId($media->user_id);

        $this->insertMedia($media);
        $this->stack['media_id']++;
        $this->old_ids['media'][(int) $old_id] = $media_id;
    }

    private function insertPostMediaSingle(mixed $post_media): void
    {
        if (isset($this->old_ids['media'][(int) $post_media->media_id]) && isset($this->old_ids['post'][(int) $post_media->post_id])) {
            $post_media->media_id = $this->old_ids['media'][(int) $post_media->media_id];
            $post_media->post_id  = $this->old_ids['post'][(int) $post_media->post_id];

            $this->insertPostMedia($post_media);
        } elseif (!isset($this->old_ids['media'][(int) $post_media->media_id])) {
            $this->throwIdError($post_media->__name, $post_media->__line, 'media');
        } else {
            $this->throwIdError($post_media->__name, $post_media->__line, 'post');
        }
    }

    private function insertPingSingle(mixed $ping): void
    {
        if (isset($this->old_ids['post'][(int) $ping->post_id])) {
            $ping->post_id = $this->old_ids['post'][(int) $ping->post_id];

            $this->insertPing($ping);
        } else {
            $this->throwIdError($ping->__name, $ping->__line, 'post');
        }
    }

    private function insertCommentSingle(mixed $comment): void
    {
        if (isset($this->old_ids['post'][(int) $comment->post_id])) {
            $comment_id = $this->stack['comment_id'];

            $comment->comment_id = $comment_id;
            $comment->post_id    = $this->old_ids['post'][(int) $comment->post_id];

            $this->insertComment($comment);
            $this->stack['comment_id']++;
        } else {
            $this->throwIdError($comment->__name, $comment->__line, 'post');
        }
    }

    private function throwIdError(string $name, string $line, string $related): mixed
    {
        throw new Exception(sprintf(
            __('ID of "%3$s" does not match on record "%1$s" at line %2$s of backup file.'),
            Html::escapeHTML($name),
            Html::escapeHTML($line),
            Html::escapeHTML($related)
        ));
    }

    public function searchCategory(mixed $rs, mixed $url): mixed
    {
        while ($rs->fetch()) {
            if ($rs->cat_url == $url) {
                return $rs->cat_id;
            }
        }

        return false;
    }

    public function getUserId(mixed $user_id): ?string
    {
        if (!$this->userExists($user_id)) {
            if (App::auth()->isSuperAdmin()) {
                # Sanitizes user_id and create a lambda user
                $user_id = (string) preg_replace('/[^A-Za-z0-9]$/', '', (string) $user_id);
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
        $strReq = 'SELECT media_id ' .
        'FROM ' . $this->prefix . App::postMedia()::MEDIA_TABLE_NAME . ' ' .
        "WHERE media_path = '" . $this->con->escapeStr($this->cur_media->media_path) . "' " .
        "AND media_file = '" . $this->con->escapeStr($this->cur_media->media_file) . "' ";

        $rs = new MetaRecord($this->con->select($strReq));

        return !$rs->isEmpty();
    }

    private function prepareDC12line(mixed &$line): void
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
                $line->post_title         = Html::decodeEntities($line->post_title);
                $line->post_url           = date('Y/m/d/', (int) strtotime((string) $line->post_dt)) . $line->post_id . '-' . $line->post_titre_url;
                $line->post_url           = substr($line->post_url, 0, 255);
                $line->post_format        = $line->post_content_wiki == '' ? 'xhtml' : 'wiki';
                $line->post_content_xhtml = $line->post_content;
                $line->post_excerpt_xhtml = $line->post_chapo;

                if ($line->post_format === 'wiki') {
                    $line->post_content = $line->post_content_wiki;
                    $line->post_excerpt = $line->post_chapo_wiki;
                } else {
                    $line->post_excerpt = $line->post_chapo;
                }

                $line->post_status = (int) $line->post_pub;
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
                if ($line->comment_site != '' && !preg_match('!^http(s)?://.*$!', (string) $line->comment_site, $m)) {
                    $line->comment_site = 'http://' . $line->comment_site;
                }
                $line->comment_status = (int) $line->comment_pub;
                $line->drop('comment_pub');

                break;
        }

        # --BEHAVIOR-- importPrepareDC12 -- line, FlatBackup
        App::behavior()->callBehavior('importPrepareDC12V2', $line, $this);
    }
}
