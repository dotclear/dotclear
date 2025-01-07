<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use ArrayObject;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Exception\ConflictException;
use Dotclear\Exception\BadRequestException;
use Dotclear\Exception\UnauthorizedException;

/**
 * @brief   Blog handler interface.
 *
 * @since   2.28, an instance of BLog can be set without a defined blog
 */
interface BlogInterface
{
    /**
     * Blog table name.
     *
     * @var    string  BLOG_TABLE_NAME
     */
    public const BLOG_TABLE_NAME = 'blog';

    /**
     * Post table name .
     *
     * @var    string  POST_TABLE_NAME
     */
    public const POST_TABLE_NAME = 'post';

    /**
     * Comment table name.
     *
     * @var    string  COMMENT_TABLE_NAME
     */
    public const COMMENT_TABLE_NAME = 'comment';

    /**
     * Blog statuses : blog online.
     *
     * @deprecated since 2.33, use App::status()->blog()::ONLINE instead
     *
     * @var    int  BLOG_ONLINE
     */
    public const BLOG_ONLINE = 1;

    /**
     * Blog statuses : blog offline.
     *
     * @deprecated since 2.33, use App::status()->blog()::OFFLINE instead
     *
     * @var    int  BLOG_OFFLINE
     */
    public const BLOG_OFFLINE = 0;

    /**
     * Blog statuses : blog removed.
     *
     * @deprecated since 2.33, use App::status()->blog()::REMOVED instead
     *
     * @var    int     BLOG_REMOVED
     */
    public const BLOG_REMOVED = -1;

    /**
     * Blog statuses : blog removed.
     *
     * @deprecated since 2.33, use App::status()->blog()::UNDEFINED instead
     *
     * @var    int     BLOG_UNDEFINED
     */
    public const BLOG_UNDEFINED = -2;

    /**
     * Post statuses : post pending.
     *
     * @deprecated since 2.33, use App::status()->post()::PENDING instead
     *
     * @var    int     POST_PENDING
     */
    public const POST_PENDING = -2;

    /**
     * Post statuses : post scheduled.
     *
     * @deprecated since 2.33, use App::status()->post()::SCHEDULED instead
     *
     * @var    int     POST_SCHEDULED
     */
    public const POST_SCHEDULED = -1;

    /**
     * Post statuses : post unpublished.
     *
     * @deprecated since 2.33, use App::status()->post()::UNPUBLISHED instead
     *
     * @var    int     POST_UNPUBLISHED
     */
    public const POST_UNPUBLISHED = 0;

    /**
     * Post statuses : post published.
     *
     * @deprecated since 2.33, use App::status()->post()::PUBLISHED instead
     *
     * @var    int     POST_PUBLISHED
     */
    public const POST_PUBLISHED = 1;

    /**
     * Comment statuses : comment junk.
     *
     * @deprecated since 2.33, use App::status()->comment()::JUNK instead
     *
     * @var    int     COMMENT_JUNK
     */
    public const COMMENT_JUNK = -2;

    /**
     * Comment statuses : comment pending.
     *
     * @deprecated since 2.33, use App::status()->comment()::PENDING instead
     *
     * @var    int     COMMENT_PENDING
     */
    public const COMMENT_PENDING = -1;

    /**
     * Comment statuses : comment unpublished.
     *
     * @deprecated since 2.33, use App::status()->comment()::UNPUBLISHED instead
     *
     * @var    int     COMMENT_UNPUBLISHED
     */
    public const COMMENT_UNPUBLISHED = 0;

    /**
     * Comment statuses : comment published.
     *
     * @deprecated since 2.33, use App::status()->comment()::PUBLISHED instead
     *
     * @var    int     COMMENT_PUBLISHED */
    public const COMMENT_PUBLISHED = 1;

    /// @name Class public methods
    //@{

    /**
     * Load a blog definition.
     *
     * This load a blog in current instance.
     * Use empty blog ID to unload blog.
     *
     * @param   string  $blog_id    The blog ID
     *
     * @return  BlogInterface   The blog instance
     */
    public function loadFromBlog(string $blog_id): BlogInterface;

    /**
     * Set authentication handler.
     *
     * This is a bad way to avoid circular reference for Auth class in constructor.
     */
    public function setAuth(AuthInterface $auth): void;

    /**
     * Get authentication instance.
     *
     * Used by Users class and Blogs class to avoir circular reference in constructor.
     *
     * @return  AuthInterface   The authentication instance
     */
    public function auth(): AuthInterface;

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The blog database table cursor
     */
    public function openBlogCursor(): Cursor;

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The blog post database table cursor
     */
    public function openPostCursor(): Cursor;

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The blog comment database table cursor
     */
    public function openCommentCursor(): Cursor;

    /**
     * Check if a blog is loaded.
     */
    public function isDefined(): bool;

    //@}

    /// @name properties access methods
    //@{

    /**
     * Get blog ID.
     */
    public function id(): string;

    /**
     * Get blog UID.
     */
    public function uid(): string;

    /**
     * Get blog name.
     */
    public function name(): string;

    /**
     * Get blog description.
     */
    public function desc(): string;

    /**
     * Get blog URL.
     */
    public function url(): string;

    /**
     * Get blog host.
     */
    public function host(): string;

    /**
     * Get blog creation date.
     */
    public function creadt(): int;

    /**
     * Get blog last update date.
     */
    public function upddt(): int;

    /**
     * Get blog status.
     */
    public function status(): int;

    /**
     * Get blog settings instance.
     */
    public function settings(): BlogSettingsInterface;

    /**
     * Get blog themes path.
     */
    public function themesPath(): string;

    /**
     * Get blog public path.
     */
    public function publicPath(): string;

    //@}

    /// @name Common public methods
    //@{

    /**
     * Returns blog URL ending with a question mark.
     */
    public function getQmarkURL(): string;

    /**
     * Gets the jQuery version.
     */
    public function getJsJQuery(): string;

    /**
     * Returns public URL of specified plugin file.
     *
     * @param   string  $pf             plugin file
     * @param   bool    $strip_host     Strip host in URL
     */
    public function getPF(string $pf, bool $strip_host = true): string;

    /**
     * Returns public URL of specified var file.
     *
     * @param   string  $vf             var file
     * @param   bool    $strip_host     Strip host in URL
     */
    public function getVF(string $vf, bool $strip_host = true): string;

    /**
     * Returns an entry status name given to a code.
     *
     * @deprecated since 2.33, use App::status()->post()->name($status) instead
     *
     * @param   int     $status     The status code
     */
    public function getPostStatus(int $status): string;

    /**
     * Returns an array of available entry status codes and names.
     *
     * @deprecated since 2.33, use App::status()->post()->statuses() instead
     *
     * @return  array<int, string>   Simple array with int codes in keys and string names in value.
     */
    public function getAllPostStatus(): array;

    /**
     * Returns an array of available comment status codes and names.
     *
     * @deprecated since 2.33, use App::status()->comment()->statuses() instead
     *
     * @return  array<int, string>   Simple array with int codes in keys and string names in value
     */
    public function getAllCommentStatus(): array;

    /**
     * Disallows entries password protection.
     *
     * You need to set it to <var>false</var> while serving a public blog.
     *
     * @param   null|bool   $value Null to only read value
     *
     * @return  bool    False for public blog
     */
    public function withoutPassword(?bool $value = null): bool;

    //@}

    /// @name Triggers methods
    //@{

    /**
     * Updates blog last update date.
     *
     * Should be called every time you change
     * an element related to the blog.
     */
    public function triggerBlog(): void;

    /**
     * Updates comment and trackback counters in post table.
     *
     * Should be called every time a comment or trackback is added,
     * removed or changed its status.
     *
     * @param   int     $id     The comment identifier
     * @param   bool    $del    If comment is deleted, set this to true
     */
    public function triggerComment(int $id, bool $del = false): void;

    /**
     * Updates comments and trackbacks counters in post table.
     *
     * Should be called every time comments or trackbacks are added,
     * removed or changed their status.
     *
     * @param   mixed   $ids                The identifiers
     * @param   bool    $del                If comment is delete, set this to true
     * @param   mixed   $affected_posts     The affected posts IDs
     */
    public function triggerComments($ids, bool $del = false, $affected_posts = null): void;
    //@}

    /// @name Categories management methods
    //@{

    /**
     * Get Categories instance.
     */
    public function categories(): CategoriesInterface;

    /**
     * Retrieves categories.
     *
     * <var>$params</var> is an associative array which can
     * take the following parameters:
     *
     * - post_type: Get only entries with given type (default "post")
     * - cat_url: filter on cat_url field
     * - cat_id: filter on cat_id field
     * - start: start with a given category
     * - level: categories level to retrieve
     *
     * @param   array<string, mixed>|ArrayObject<string, mixed>   $params     The parameters
     *
     * @return  MetaRecord  The categories.
     */
    public function getCategories($params = []): MetaRecord;

    /**
     * Gets the category by its ID.
     *
     * @param   int     $id     The category identifier
     *
     * @return  MetaRecord  The category.
     */
    public function getCategory(?int $id): MetaRecord;

    /**
     * Gets the category parents.
     *
     * @param   int     $id     The category identifier
     *
     * @return  MetaRecord  The category parents.
     */
    public function getCategoryParents(?int $id): MetaRecord;

    /**
     * Gets the category first parent.
     *
     * @param   int     $id     The category identifier
     *
     * @return  MetaRecord  The category parent.
     */
    public function getCategoryParent(?int $id): MetaRecord;

    /**
     * Gets all category's first children.
     *
     * @param   int     $id     The category identifier
     *
     * @return  MetaRecord  The category first children.
     */
    public function getCategoryFirstChildren(int $id): MetaRecord;

    /**
     * Returns true if a given category if in a given category's subtree
     *
     * @param   string  $cat_url    The cat url
     * @param   string  $start_url  The top cat url
     *
     * @return  bool    true if cat_url is in given start_url cat subtree
     */
    public function IsInCatSubtree(string $cat_url, string $start_url): bool;

    /**
     * Adds a new category. Takes a Cursor as input and returns the new category ID.
     *
     * @param   Cursor  $cur        The category Cursor
     * @param   int     $parent     The parent category ID
     *
     * @throws  UnauthorizedException
     *
     * @return  int     New category ID
     */
    public function addCategory(Cursor $cur, int $parent = 0): int;

    /**
     * Updates an existing category.
     *
     * @param   int     $id     The category ID
     * @param   Cursor  $cur    The category Cursor
     *
     * @throws  UnauthorizedException
     */
    public function updCategory(int $id, Cursor $cur): void;

    /**
     * Set category position.
     *
     * @param   int     $id     The category ID
     * @param   int     $left   The category ID before
     * @param   int     $right  The category ID after
     */
    public function updCategoryPosition(int $id, int $left, int $right): void;

    /**
     * Sets the category parent.
     *
     * @param   int     $id         The category ID
     * @param   int     $parent     The parent category ID
     */
    public function setCategoryParent(int $id, int $parent): void;

    /**
     * Sets the category position.
     *
     * @param   int     $id         The category ID
     * @param   int     $sibling    The sibling category ID
     * @param   string  $move       The move (before|after)
     */
    public function setCategoryPosition(int $id, int $sibling, string $move): void;

    /**
     * Delete a category.
     *
     * @param   int     $id     The category ID
     *
     * @throws  UnauthorizedException|ConflictException
     */
    public function delCategory(int $id): void;

    /**
     * Reset categories order and relocate them to first level.
     *
     * @throws  UnauthorizedException
     */
    public function resetCategoriesOrder(): void;

    //@}

    /// @name Entries management methods
    //@{

    /**
     * Retrieves entries.
     *
     * <b>$params</b> is an array taking the following
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
     * - exclude_post_id : (integer or array) Exclude entries with given post_id
     *
     * Please note that on every cat_id or cat_url, you can add ?not to exclude
     * the category and ?sub to get subcategories.
     *
     * @param   array<string, mixed>|ArrayObject<string, mixed>     $params     Parameters
     * @param   bool                                                $count_only Only counts results
     * @param   SelectStatement                                     $ext_sql    Optional SelectStatement instance
     *
     * @return  MetaRecord  A record with some more capabilities
     */
    public function getPosts($params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord;

    /**
     * Returns a MetaRecord with post id, title and date for next or previous post
     * according to the post ID.
     *
     * $dir could be 1 (next post) or -1 (previous post).
     *
     * @param   MetaRecord  $post                   The post ID
     * @param   int         $dir                    The search direction
     * @param   bool        $restrict_to_category   Restrict to same category
     * @param   bool        $restrict_to_lang       Restrict to same language
     *
     * @return  MetaRecord|null   The next post.
     */
    public function getNextPost(MetaRecord $post, int $dir, bool $restrict_to_category = false, bool $restrict_to_lang = false): ?MetaRecord;

    /**
     * Retrieves different languages and post count on blog, based on post_lang
     * field.
     *
     * <var>$params</var> is an array taking the following optionnal
     * parameters:
     *
     * - post_type: Get only entries with given type (default "post", '' for no type)
     * - lang: retrieve post count for selected lang
     * - order: order statement (default post_lang DESC)
     *
     * @param   array<string, mixed>|ArrayObject<string, mixed>   $params     The parameters
     *
     * @return  MetaRecord  The langs.
     */
    public function getLangs($params = []): MetaRecord;

    /**
     * Returns a MetaRecord with all distinct blog dates and post count.
     *
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
     * @param   array<string, mixed>|ArrayObject<string, mixed>   $params     The parameters
     *
     * @return  MetaRecord  The dates.
     */
    public function getDates($params = []): MetaRecord;

    /**
     * Creates a new entry. Takes a Cursor as input and returns the new entry ID.
     *
     * @param   Cursor  $cur    The post Cursor
     *
     * @throws  UnauthorizedException
     */
    public function addPost(Cursor $cur): int;

    /**
     * Updates an existing post.
     *
     * @param   int     $id     The post identifier
     * @param   Cursor  $cur    The post Cursor
     *
     * @throws  UnauthorizedException|BadRequestException
     */
    public function updPost($id, Cursor $cur): void;

    /**
     * Update post status.
     *
     * @param   int     $id         The identifier
     * @param   int     $status     The status
     */
    public function updPostStatus($id, $status): void;

    /**
     * Updates posts status.
     *
     * @param   mixed   $ids        The identifiers
     * @param   int     $status     The status
     *
     * @throws  UnauthorizedException
     */
    public function updPostsStatus($ids, $status): void;

    /**
     * Updates posts first publication flag.
     *
     * @param   mixed   $ids        The identifiers
     * @param   int     $status     The flag
     *
     * @throws  UnauthorizedException
     */
    public function updPostsFirstPub($ids, int $status): void;

    /**
     * Updates post selection.
     *
     * @param   int     $id     The identifier
     * @param   mixed   $selected   The selected flag
     */
    public function updPostSelected($id, $selected): void;

    /**
     * Updates posts selection.
     *
     * @param   mixed   $ids        The identifiers
     * @param   mixed   $selected    The selected flag
     *
     * @throws  UnauthorizedException
     */
    public function updPostsSelected($ids, $selected): void;

    /**
     * Updates post category.
     *
     * <var>$cat_id</var> can be null.
     *
     * @param   int     $id         The identifier
     * @param   mixed   $cat_id     The cat identifier
     */
    public function updPostCategory($id, $cat_id): void;

    /**
     * Updates posts category.
     *
     * <var>$cat_id</var> can be null.
     *
     * @param   mixed   $ids        The identifiers
     * @param   mixed   $cat_id     The cat identifier
     *
     * @throws  UnauthorizedException
     */
    public function updPostsCategory($ids, $cat_id): void;

    /**
     * Updates posts category.
     *
     * <var>$new_cat_id</var> can be null.
     *
     * @param   mixed   $old_cat_id     The old cat identifier
     * @param   mixed   $new_cat_id     The new cat identifier
     *
     * @throws  UnauthorizedException
     */
    public function changePostsCategory($old_cat_id, $new_cat_id): void;

    /**
     * Deletes a post.
     *
     * @param   int     $id     The post identifier
     */
    public function delPost($id): void;

    /**
     * Deletes multiple posts.
     *
     * @param   mixed   $ids    The posts identifiers
     *
     * @throws  UnauthorizedException|BadRequestException
     */
    public function delPosts($ids): void;

    /**
     * Publishes all entries flaged as "scheduled".
     */
    public function publishScheduledEntries(): void;

    /**
     * First publication mecanism (on post create, update, publish, status)
     *
     * @param   mixed   $ids    The posts identifiers
     */
    public function firstPublicationEntries($ids): void;

    /**
     * Retrieves all users having posts on current blog.
     *
     * @param   string  $post_type post_type filter (post)
     */
    public function getPostsUsers(string $post_type = 'post'): MetaRecord;

    /**
     * Creates post HTML content, taking format and lang into account.
     *
     * @param   int     $post_id        The post identifier
     * @param   string  $format         The format
     * @param   string  $lang           The language
     * @param   string  $excerpt        The excerpt
     * @param   string  $excerpt_xhtml  The excerpt HTML
     * @param   string  $content        The content
     * @param   string  $content_xhtml  The content HTML
     */
    public function setPostContent($post_id, $format, $lang, &$excerpt, &$excerpt_xhtml, &$content, &$content_xhtml): void;

    /**
     * Returns URL for a post according to blog setting <var>post_url_format</var>.
     *
     * It will try to guess URL and append some figures if needed.
     *
     * @thrhow  BadRequestException
     *
     * @param   string  $url            The url
     * @param   string  $post_dt        The post dt
     * @param   string  $post_title     The post title
     * @param   int     $post_id        The post identifier
     *
     * @return  string  The post url.
     */
    public function getPostURL($url, $post_dt, $post_title, $post_id): string;
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
     *
     * @param   array<string, mixed>|ArrayObject<string, mixed>     $params         Parameters
     * @param   bool                                                $count_only     Only counts results
     * @param   SelectStatement                                     $ext_sql        Optional SelectStatement instance
     *
     * @return  MetaRecord  A record with some more capabilities
     */
    public function getComments($params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord;

    /**
     * Creates a new comment. Takes a Cursor as input and returns the new comment ID.
     *
     * @param   Cursor  $cur    The comment Cursor
     */
    public function addComment(Cursor $cur): int;

    /**
     * Updates an existing comment.
     *
     * @param   int     $id     The comment identifier
     * @param   Cursor  $cur    The comment Cursor
     *
     * @throws  UnauthorizedException|BadRequestException
     */
    public function updComment($id, Cursor $cur): void;

    /**
     * Updates comment status.
     *
     * @param   int     $id         The comment identifier
     * @param   mixed   $status      The comment status
     */
    public function updCommentStatus($id, $status): void;

    /**
     * Updates comments status.
     *
     * @param   mixed   $ids        The identifiers
     * @param   mixed   $status     The status
     *
     * @throws  UnauthorizedException
     */
    public function updCommentsStatus($ids, $status): void;

    /**
     * Delete a comment.
     *
     * @param   int     $id     The comment identifier
     */
    public function delComment($id): void;

    /**
     * Delete comments.
     *
     * @param   mixed   $ids    The comments identifiers
     *
     * @throws  UnauthorizedException|BadRequestException
     */
    public function delComments($ids): void;

    /**
     * Delete Junk comments.
     *
     * @throws  UnauthorizedException
     */
    public function delJunkComments(): void;

    /**
     * Check if a blog should switch in sleep mode.
     *
     * (close comments/trackbacks)
     *
     * @param   bool    $apply  False = test only, True = close comments/trackbacks if necessary
     *
     * @return  bool    True = period elapsed, False = no need to switch into sleep mode
     */
    public function checkSleepmodeTimeout(bool $apply = true): bool;
    //@}

    /**
     * Cleanup a list of IDs.
     *
     * @param   mixed   $ids    The identifiers
     *
     * @return  array<int,int>
     */
    public function cleanIds($ids): array;
}
