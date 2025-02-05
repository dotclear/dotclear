<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Exception\BadRequestException;
use Dotclear\Exception\UnauthorizedException;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\DeprecatedInterface;

/**
 * @brief   Blogs handler.
 *
 * @since   2.28, blogs features have been grouped in this class
 */
class Blogs implements BlogsInterface
{
    /**
     * Constructor.
     *
     * @param   BlogInterface           $blog           The blog instance
     * @param   ConnectionInterface     $con            The database connection instance
     * @param   DeprecatedInterface     $deprecated     The database connection instance
     */
    public function __construct(
        protected BlogInterface $blog,
        protected ConnectionInterface $con,
        protected DeprecatedInterface $deprecated
    ) {
    }

    /**
     * @deprecated  since 2.33, use App::status()->blog()->statuses() instead
     */
    public function getAllBlogStatus(): array
    {
        $this->deprecated->set('App::status()->blog()->statuses()', '2.33');

        return App::status()->blog()->statuses();
    }

    /**
     * @deprecated  since 2.33, use App::status()->blog()->name($s) instead
     */
    public function getBlogStatus(int $s): string
    {
        $this->deprecated->set('App::status()->blog()->status($s)', '2.33');

        return App::status()->blog()->name($s);
    }

    /**
     * Gets the blog permissions.
     *
     * @param      string  $id          The identifier
     * @param      bool    $with_super  The with super
     *
     * @return     array<int|string, array<string, mixed>>   The blog permissions.
     */
    public function getBlogPermissions(string $id, bool $with_super = true): array
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                'U.user_id as user_id',
                'user_super',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
                'permissions',
            ])
            ->from($sql->as($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME, 'U'))
            ->join((new JoinStatement())
                ->from($sql->as($this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME, 'P'))
                ->on('U.user_id = P.user_id')
                ->statement())
            ->where('blog_id = ' . $sql->quote($id));

        if ($with_super) {
            $sql->union(
                (new SelectStatement())
                ->columns([
                    'U.user_id as user_id',
                    'user_super',
                    'user_name',
                    'user_firstname',
                    'user_displayname',
                    'user_email',
                    'NULL AS permissions',
                ])
                ->from($sql->as($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME, 'U'))
                ->where('user_super = 1')
                ->statement()
            );
        }

        $rs = $sql->select();

        $res = [];

        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $res[$rs->user_id] = [
                    'name'        => $rs->user_name,
                    'firstname'   => $rs->user_firstname,
                    'displayname' => $rs->user_displayname,
                    'email'       => $rs->user_email,
                    'super'       => (bool) $rs->user_super,
                    'p'           => $this->blog->auth()->parsePermissions($rs->permissions),
                ];
            }
        }

        return $res;
    }

    public function getBlog(string $id): MetaRecord
    {
        return $this->getBlogs(['blog_id' => $id]);
    }

    public function getBlogs(array|ArrayObject $params = [], bool $count_only = false): MetaRecord
    {
        $sql = new SelectStatement();

        if ($count_only) {
            $sql
                ->column($sql->count('B.blog_id'))
                ->from($sql->as($this->con->prefix() . $this->blog::BLOG_TABLE_NAME, 'B'))
                ->where('NULL IS NULL')
            ;
        } else {
            $sql
                ->columns([
                    'B.blog_id',
                    'blog_uid',
                    'blog_url',
                    'blog_name',
                    'blog_desc',
                    'blog_creadt',
                    'blog_upddt',
                    'blog_status',
                ])
                ->from($sql->as($this->con->prefix() . $this->blog::BLOG_TABLE_NAME, 'B'))
                ->where('NULL IS NULL')
            ;

            if (!empty($params['columns'])) {
                $sql->columns($params['columns']);
            }

            $sql->order(empty($params['order']) ? 'B.blog_id ASC' : $sql->escape($params['order']));

            if (!empty($params['limit'])) {
                $sql->limit($params['limit']);
            }
        }

        if ($this->blog->auth()->userID() && !$this->blog->auth()->isSuperAdmin()) {
            $sql
                ->join(
                    (new JoinStatement())
                        ->inner()
                        ->from($sql->as($this->con->prefix() . $this->blog->auth()::PERMISSIONS_TABLE_NAME, 'PE'))
                        ->on('B.blog_id = PE.blog_id')
                        ->statement()
                )
                ->and('PE.user_id = ' . $sql->quote($this->blog->auth()->userID()))
                ->and($sql->orGroup([
                    $sql->like('permissions', '%|' . $this->blog->auth()::PERMISSION_USAGE . '|%'),
                    $sql->like('permissions', '%|' . $this->blog->auth()::PERMISSION_ADMIN . '|%'),
                    $sql->like('permissions', '%|' . $this->blog->auth()::PERMISSION_CONTENT_ADMIN . '|%'),
                ]))
                ->and('blog_status >= ' . App::status()->blog()->threshold())
            ;
        } elseif (!$this->blog->auth()->userID()) {
            $sql->and('blog_status >= ' . App::status()->blog()->threshold());
        }

        if (isset($params['blog_status']) && $params['blog_status'] !== '' && $this->blog->auth()->isSuperAdmin()) {
            $sql->and('blog_status = ' . (int) $params['blog_status']);
        }

        if (isset($params['blog_id']) && $params['blog_id'] !== '') {
            $sql->and('B.blog_id' . $sql->in($params['blog_id']));
        }

        if (!empty($params['q'])) {
            $params['q'] = strtolower((string) str_replace('*', '%', $params['q']));    // @phpstan-ignore-line
            $sql->and($sql->orGroup([
                $sql->like('LOWER(B.blog_id)', $sql->escape($params['q'])),
                $sql->like('LOWER(B.blog_name)', $sql->escape($params['q'])),
                $sql->like('LOWER(B.blog_url)', $sql->escape($params['q'])),
            ]));
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }

    public function addBlog(Cursor $cur): void
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        $this->fillBlogCursor($cur);

        $cur->blog_creadt = date('Y-m-d H:i:s');
        $cur->blog_upddt  = date('Y-m-d H:i:s');
        $cur->blog_uid    = md5(uniqid());

        $cur->insert();
    }

    public function updBlog(string $id, Cursor $cur): void
    {
        $this->fillBlogCursor($cur);

        $cur->blog_upddt = date('Y-m-d H:i:s');

        $cur->update("WHERE blog_id = '" . $this->con->escapeStr($id) . "'");
    }

    /**
     * Clean up blog cursor.
     *
     * @throws  BadRequestException
     *
     * @param   Cursor  $cur    The blog cursor
     */
    private function fillBlogCursor(Cursor $cur): void
    {
        if (($cur->blog_id !== null
            && !preg_match('/^[A-Za-z0-9._-]{2,}$/', (string) $cur->blog_id)) || (!$cur->blog_id)) {
            throw new BadRequestException(__('Blog ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if (($cur->blog_name !== null && $cur->blog_name == '') || (!$cur->blog_name)) {
            throw new BadRequestException(__('No blog name'));
        }

        if (($cur->blog_url !== null && $cur->blog_url == '') || (!$cur->blog_url)) {
            throw new BadRequestException(__('No blog URL'));
        }
    }

    public function delBlog(string $id): void
    {
        if (!$this->blog->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not an administrator'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->con->prefix() . $this->blog::BLOG_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id))
            ->delete();
    }

    public function blogExists(string $id): bool
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->column('blog_id')
            ->from($this->con->prefix() . $this->blog::BLOG_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id))
            ->select();

        return $rs instanceof MetaRecord && !$rs->isEmpty();
    }

    public function countBlogPosts(string $id, ?string $type = null): int
    {
        $sql = new SelectStatement();
        $sql
            ->column($sql->count('post_id'))
            ->from($this->con->prefix() . $this->blog::POST_TABLE_NAME)
            ->where('blog_id = ' . $sql->quote($id));

        if ($type) {
            $sql->and('post_type = ' . $sql->quote($type));
        }

        return (int) $sql->select()?->f(0);
    }

    /**
     * Creates default settings for active blog.
     *
     * Optionnal parameter <var>defaults</var> replaces default params while needed.
     *
     * @param   null|array<array{0:string, 1:string, 2:mixed, 3:string}>  $defaults   The defaults settings
     *
     * @since 2.33
     */
    public function blogDefaults(?array $defaults = null): void
    {
        if (!is_array($defaults)) {
            $defaults = [
                ['allow_comments', App::blogWorkspace()::NS_BOOL, true,
                    'Allow comments on blog', ],
                ['allow_trackbacks', App::blogWorkspace()::NS_BOOL, true,
                    'Allow trackbacks on blog', ],
                ['blog_timezone', App::blogWorkspace()::NS_STRING, 'Europe/London',
                    'Blog timezone', ],
                ['comments_nofollow', App::blogWorkspace()::NS_BOOL, true,
                    'Add rel="nofollow" to comments URLs', ],
                ['comments_pub', App::blogWorkspace()::NS_BOOL, true,
                    'Publish comments immediately', ],
                ['comments_ttl', App::blogWorkspace()::NS_INT, 0,
                    'Number of days to keep comments open (0 means no ttl)', ],
                ['copyright_notice', App::blogWorkspace()::NS_STRING, '',
                    'Copyright notice (simple text)', ],
                ['date_format', App::blogWorkspace()::NS_STRING, '%A, %B %e %Y',
                    'Date format. See PHP strftime function for patterns', ],
                ['editor', App::blogWorkspace()::NS_STRING, '',
                    'Person responsible of the content', ],
                ['enable_html_filter', App::blogWorkspace()::NS_BOOL, 0,
                    'Enable HTML filter', ],
                ['lang', App::blogWorkspace()::NS_STRING, 'en',
                    'Default blog language', ],
                ['media_exclusion', App::blogWorkspace()::NS_STRING, '/\.(phps?|pht(ml)?|phl|phar|.?html?|inc|xml|js|htaccess)[0-9]*$/i',
                    'File name exclusion pattern in media manager. (PCRE value)', ],
                ['media_img_m_size', App::blogWorkspace()::NS_INT, 448,
                    'Image medium size in media manager', ],
                ['media_img_s_size', App::blogWorkspace()::NS_INT, 240,
                    'Image small size in media manager', ],
                ['media_img_t_size', App::blogWorkspace()::NS_INT, 100,
                    'Image thumbnail size in media manager', ],
                ['media_img_title_pattern', App::blogWorkspace()::NS_STRING, 'Description ;; Date(%b %Y) ;; separator(, )',
                    'Pattern to set image legend when you insert it in a post', ],
                ['media_video_width', App::blogWorkspace()::NS_INT, 400,
                    'Video width in media manager', ],
                ['media_video_height', App::blogWorkspace()::NS_INT, 300,
                    'Video height in media manager', ],
                ['nb_post_for_home', App::blogWorkspace()::NS_INT, 20,
                    'Number of entries on first home page', ],
                ['nb_post_per_page', App::blogWorkspace()::NS_INT, 20,
                    'Number of entries on home pages and category pages', ],
                ['nb_post_per_feed', App::blogWorkspace()::NS_INT, 20,
                    'Number of entries on feeds', ],
                ['nb_comment_per_feed', App::blogWorkspace()::NS_INT, 20,
                    'Number of comments on feeds', ],
                ['post_url_format', App::blogWorkspace()::NS_STRING, '{y}/{m}/{d}/{t}',
                    'Post URL format. {y}: year, {m}: month, {d}: day, {id}: post id, {t}: entry title', ],
                ['public_path', App::blogWorkspace()::NS_STRING, 'public',
                    'Path to public directory, begins with a / for a full system path', ],
                ['public_url', App::blogWorkspace()::NS_STRING, '/public',
                    'URL to public directory', ],
                ['robots_policy', App::blogWorkspace()::NS_STRING, 'INDEX,FOLLOW',
                    'Search engines robots policy', ],
                ['short_feed_items', App::blogWorkspace()::NS_BOOL, false,
                    'Display short feed items', ],
                ['theme', App::blogWorkspace()::NS_STRING, App::config()->defaultTheme(),
                    'Blog theme', ],
                ['themes_path', App::blogWorkspace()::NS_STRING, 'themes',
                    'Themes root path', ],
                ['themes_url', App::blogWorkspace()::NS_STRING, '/themes',
                    'Themes root URL', ],
                ['time_format', App::blogWorkspace()::NS_STRING, '%H:%M',
                    'Time format. See PHP strftime function for patterns', ],
                ['tpl_allow_php', App::blogWorkspace()::NS_BOOL, false,
                    'Allow PHP code in templates', ],
                ['tpl_use_cache', App::blogWorkspace()::NS_BOOL, true,
                    'Use template caching', ],
                ['trackbacks_pub', App::blogWorkspace()::NS_BOOL, true,
                    'Publish trackbacks immediately', ],
                ['trackbacks_ttl', App::blogWorkspace()::NS_INT, 0,
                    'Number of days to keep trackbacks open (0 means no ttl)', ],
                ['url_scan', App::blogWorkspace()::NS_STRING, 'query_string',
                    'URL handle mode (path_info or query_string)', ],
                ['no_public_css', App::blogWorkspace()::NS_BOOL, false,
                    'Don\'t use generic public.css stylesheet', ],
                ['use_smilies', App::blogWorkspace()::NS_BOOL, false,
                    'Show smilies on entries and comments', ],
                ['no_search', App::blogWorkspace()::NS_BOOL, false,
                    'Disable search', ],
                ['inc_subcats', App::blogWorkspace()::NS_BOOL, false,
                    'Include sub-categories in category page and category posts feed', ],
                ['wiki_comments', App::blogWorkspace()::NS_BOOL, false,
                    'Allow commenters to use a subset of wiki syntax', ],
                ['import_feed_url_control', App::blogWorkspace()::NS_BOOL, true,
                    'Control feed URL before import', ],
                ['import_feed_no_private_ip', App::blogWorkspace()::NS_BOOL, true,
                    'Prevent import feed from private IP', ],
                ['import_feed_ip_regexp', App::blogWorkspace()::NS_STRING, '',
                    'Authorize import feed only from this IP regexp', ],
                ['import_feed_port_regexp', App::blogWorkspace()::NS_STRING, '/^(80|443)$/',
                    'Authorize import feed only from this port regexp', ],
                ['jquery_needed', App::blogWorkspace()::NS_BOOL, true,
                    'Load jQuery library', ],
                ['legacy_needed', App::blogWorkspace()::NS_BOOL, true,
                    'Load Legacy JS library', ],
                ['sleepmode_timeout', App::blogWorkspace()::NS_INT, 31536000,
                    'Sleep mode timeout', ],
                ['store_plugin_url', App::blogWorkspace()::NS_STRING, 'https://update.dotaddict.org/dc2/plugins.xml',
                    'Plugins XML feed location', ],
                ['store_theme_url', App::blogWorkspace()::NS_STRING, 'https://update.dotaddict.org/dc2/themes.xml',
                    'Themes XML feed location', ],
            ];
        }

        $settings = App::blogSettings();

        foreach ($defaults as $v) {
            $settings->system->put($v[0], $v[2], $v[1], $v[3], false, true);
        }
    }
}
