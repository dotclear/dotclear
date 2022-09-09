<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcUrlHandlers extends urlHandler
{
    /**
     * URI arguments (depends on URL representation)
     */
    public $args;

    /**
     * Gets the home type set for the blog.
     *
     * @return     string  The home type (static or default)
     */
    protected function getHomeType(): string
    {
        return dcCore::app()->blog->settings->system->static_home ? 'static' : 'default';
    }

    /**
     * Determines whether the specified type is blog home page.
     *
     * @param      string  $type   The type
     *
     * @return     bool    True if the specified type is home, False otherwise.
     */
    public function isHome(string $type): bool
    {
        return $type === $this->getHomeType();
    }

    /**
     * Gets the URL for a specified type.
     *
     * @param      string  $type   The type
     * @param      string  $value  The value
     *
     * @return     string
     */
    public function getURLFor(string $type, string $value = ''): string
    {
        $url = dcCore::app()->callBehavior('publicGetURLFor', $type, $value);
        if (!$url) {
            $url = $this->getBase($type);
            if ($value !== '') {
                if ($url) {
                    $url .= '/';
                }
                $url .= $value;
            }
        }

        return $url;
    }

    /**
     * Register an URL handler
     *
     * @param      string           $type            The type
     * @param      string           $url             The url
     * @param      string           $representation  The representation
     * @param      callable|array   $handler         The handler
     */
    public function register(string $type, string $url, string $representation, $handler): void
    {
        $url_handler = new ArrayObject([$type, $url, $representation, $handler]);
        dcCore::app()->callBehavior('publicRegisterURL', $url_handler);
        parent::register($url_handler[0], $url_handler[1], $url_handler[2], $url_handler[3]);
    }

    /**
     * Throws a 404 (page not found) exception
     *
     * @throws     Exception
     */
    public static function p404(): void
    {
        throw new Exception('Page not found', 404);
    }

    /**
     * Gets the page number from URI arguments.
     *
     * @param      mixed     $args   The arguments
     *
     * @return     false|int  The page number or false if none found.
     */
    protected static function getPageNumber(&$args)
    {
        if (preg_match('#(^|/)page/(\d+)$#', $args, $m)) {
            $n = (int) $m[2];
            if ($n > 0) {
                $args = preg_replace('#(^|/)page/(\d+)$#', '', $args);

                return $n;
            }
        }

        return false;
    }

    /**
     * Serve a page using a template file
     *
     * @param      string     $tpl_name           The template file
     * @param      string     $content_type  The content type
     * @param      bool       $http_cache    The http cache
     * @param      bool       $http_etag     The http etag
     *
     * @throws     Exception
     */
    protected static function serveDocument(string $tpl_name, string $content_type = 'text/html', bool $http_cache = true, bool $http_etag = true): void
    {
        if (dcCore::app()->ctx->nb_entry_per_page === null) {
            dcCore::app()->ctx->nb_entry_per_page = dcCore::app()->blog->settings->system->nb_post_per_page;
        }
        if (dcCore::app()->ctx->nb_entry_first_page === null) {
            dcCore::app()->ctx->nb_entry_first_page = dcCore::app()->ctx->nb_entry_per_page;
        }

        $tpl_file = dcCore::app()->tpl->getFilePath($tpl_name);

        if (!$tpl_file) {
            throw new Exception('Unable to find template ');
        }

        $result = new ArrayObject();

        dcCore::app()->ctx->current_tpl  = $tpl_name;
        dcCore::app()->ctx->content_type = $content_type;
        dcCore::app()->ctx->http_cache   = $http_cache;
        dcCore::app()->ctx->http_etag    = $http_etag;

        dcCore::app()->callBehavior('urlHandlerBeforeGetData', dcCore::app()->ctx);

        if (dcCore::app()->ctx->http_cache) {
            dcCore::app()->cache['mod_files'][] = $tpl_file;
            http::cache(dcCore::app()->cache['mod_files'], dcCore::app()->cache['mod_ts']);
        }

        header('Content-Type: ' . dcCore::app()->ctx->content_type . '; charset=UTF-8');

        // Additional headers
        $headers = new ArrayObject();
        if (dcCore::app()->blog->settings->system->prevents_clickjacking) {
            if (dcCore::app()->ctx->exists('xframeoption')) {
                $url    = parse_url(dcCore::app()->ctx->xframeoption);
                $header = sprintf(
                    'X-Frame-Options: %s',
                    is_array($url) ? ('ALLOW-FROM ' . $url['scheme'] . '://' . $url['host']) : 'SAMEORIGIN'
                );
            } else {
                // Prevents Clickjacking as far as possible
                $header = 'X-Frame-Options: SAMEORIGIN'; // FF 3.6.9+ Chrome 4.1+ IE 8+ Safari 4+ Opera 10.5+
            }
            $headers->append($header);
        }

        # --BEHAVIOR-- urlHandlerServeDocumentHeaders
        dcCore::app()->callBehavior('urlHandlerServeDocumentHeaders', $headers);

        // Send additional headers if any
        foreach ($headers as $header) {
            header($header);
        }

        $result = [
            'content'      => dcCore::app()->tpl->getData(dcCore::app()->ctx->current_tpl),
            'content_type' => dcCore::app()->ctx->content_type,
            'tpl'          => dcCore::app()->ctx->current_tpl,
            'blogupddt'    => dcCore::app()->blog->upddt,
            'headers'      => headers_list(),
        ];

        # --BEHAVIOR-- urlHandlerServeDocument
        dcCore::app()->callBehavior('urlHandlerServeDocument', $result);

        if (dcCore::app()->ctx->http_cache && dcCore::app()->ctx->http_etag) {
            http::etag($result['content'], http::getSelfURI());
        }
        echo $result['content'];
    }

    /**
     * Gets the appropriate page based on requested URI.
     */
    public function getDocument(): void
    {
        $type = '';

        if ($this->mode == 'path_info') {
            $part = substr($_SERVER['PATH_INFO'], 1);
        } else {
            $part = '';

            $query_string = $this->parseQueryString();

            # Recreates some _GET and _REQUEST pairs
            if (!empty($query_string)) {
                foreach ($_GET as $key => $value) {
                    if (isset($_REQUEST[$key])) {
                        unset($_REQUEST[$key]);
                    }
                }
                $_GET     = $query_string;
                $_REQUEST = array_merge($query_string, $_REQUEST);

                foreach ($query_string as $parameter => $value) {
                    if ($value === null) {
                        $part = $parameter;
                        unset($_GET[$parameter], $_REQUEST[$parameter]);
                    }

                    break;
                }
            }
        }

        $_SERVER['URL_REQUEST_PART'] = $part;

        // Determine URI type and optional arguments
        $this->getArgs($part, $type, $this->args);

        # --BEHAVIOR-- urlHandlerGetArgsDocument
        dcCore::app()->callBehavior('urlHandlerGetArgsDocument', $this);

        if (!$type) {
            $this->type = $this->getHomeType();
            $this->callDefaultHandler($this->args);
        } else {
            $this->type = $type;
            $this->callHandler($type, $this->args);
        }
    }

    /**
     * Output 404 (not found) page
     *
     * @param      null|string  $args   The arguments
     * @param      string       $type   The type
     * @param      Exception    $e      The exception
     */
    public static function default404(?string $args, string $type, Exception $e): void
    {
        if ($e->getCode() !== 404) {
            throw $e;
        }

        header('Content-Type: text/html; charset=UTF-8');
        http::head(404, 'Not Found');

        dcCore::app()->url->type         = '404';
        dcCore::app()->ctx->current_tpl  = '404.html';
        dcCore::app()->ctx->content_type = 'text/html';

        echo dcCore::app()->tpl->getData(dcCore::app()->ctx->current_tpl);

        # --BEHAVIOR-- publicAfterDocument
        dcCore::app()->callBehavior('publicAfterDocumentV2');
        exit;
    }

    /**
     * Output the Home page (last posts, paginated)
     *
     * @param      null|string  $args   The arguments
     */
    public static function home(?string $args): void
    {
        // Page number may have been set by self::lang() which ends with a call to self::home(null)
        $page_number = $args ? self::getPageNumber($args) : dcCore::app()->public->getPageNumber();

        if ($args && !$page_number) {
            // Then specified URL went unrecognized by all URL handlers and
            // defaults to the home page, but is not a page number.
            self::p404();
        } else {
            dcCore::app()->url->type = 'default';
            if ($page_number) {
                dcCore::app()->public->setPageNumber($page_number);
                if ($page_number > 1) {
                    dcCore::app()->url->type = 'default-page';
                }
            }

            if (empty($_GET['q'])) {
                if (dcCore::app()->blog->settings->system->nb_post_for_home !== null) {
                    dcCore::app()->ctx->nb_entry_first_page = dcCore::app()->blog->settings->system->nb_post_for_home;
                }
                self::serveDocument('home.html');
                dcCore::app()->blog->publishScheduledEntries();
            } else {
                self::search();
            }
        }
    }

    /**
     * Output the Static home page
     *
     * @param      null|string  $args   The arguments
     */
    public static function static_home(?string $args): void
    {
        dcCore::app()->url->type = 'static';

        if (empty($_GET['q'])) {
            self::serveDocument('static.html');
            dcCore::app()->blog->publishScheduledEntries();
        } else {
            self::search();
        }
    }

    /**
     * Output the Search page (found posts, paginated)
     *
     * Note: This handler is not called directly by the URL handler,
     *       but if necessary by one of them, so no need to set page number here.
     */
    public static function search(): void
    {
        if (dcCore::app()->blog->settings->system->no_search) {

            // Search is disabled for this blog.
            self::p404();
        } else {
            dcCore::app()->url->type = 'search';

            dcCore::app()->public->search = !empty($_GET['q']) ? html::escapeHTML(rawurldecode($_GET['q'])) : '';
            if (dcCore::app()->public->search) {
                $params = new ArrayObject(['search' => dcCore::app()->public->search]);
                dcCore::app()->callBehavior('publicBeforeSearchCount', $params);
                dcCore::app()->public->search_count = dcCore::app()->blog->getPosts($params, true)->f(0);
            }

            self::serveDocument('search.html');
        }
    }

    /**
     * Output the Home page (last posts, paginated) for a specified language
     *
     * @param      null|string  $args   The arguments
     */
    public static function lang(?string $args): void
    {
        $page_number = self::getPageNumber($args);
        $params      = new ArrayObject(
            [
                'lang' => $args,
            ]
        );
        dcCore::app()->callBehavior('publicLangBeforeGetLangs', $params, $args);
        dcCore::app()->ctx->langs = dcCore::app()->blog->getLangs($params);

        if (dcCore::app()->ctx->langs->isEmpty()) {
            # The specified language does not exist.
            self::p404();
        } else {
            if ($page_number) {
                dcCore::app()->public->setPageNumber($page_number);
            }
            dcCore::app()->ctx->cur_lang = $args;
            self::home(null);
        }
    }

    /**
     * Output the Category page (last posts of category, paginated)
     *
     * @param      null|string  $args   The arguments
     */
    public static function category(?string $args): void
    {
        $page_number = self::getPageNumber($args);

        if ($args == '' && !$page_number) {
            // No category was specified.
            self::p404();
        } else {
            $params = new ArrayObject(
                [
                    'cat_url'       => $args,
                    'post_type'     => 'post',
                    'without_empty' => false,
                ]
            );
            dcCore::app()->callBehavior('publicCategoryBeforeGetCategories', $params, $args);
            dcCore::app()->ctx->categories = dcCore::app()->blog->getCategories($params);

            if (dcCore::app()->ctx->categories->isEmpty()) {
                // The specified category does no exist.
                self::p404();
            } else {
                if ($page_number) {
                    dcCore::app()->public->setPageNumber($page_number);
                }
                self::serveDocument('category.html');
            }
        }
    }

    /**
     * Output the Archive page
     *
     * @param      null|string  $args   The arguments
     */
    public static function archive(?string $args): void
    {
        // Nothing or year and month
        if ($args == '') {
            self::serveDocument('archive.html');
        } elseif (preg_match('|^/(\d{4})/(\d{2})$|', $args, $m)) {
            $params = new ArrayObject(
                [
                    'year'  => $m[1],
                    'month' => $m[2],
                    'type'  => 'month',
                ]
            );
            dcCore::app()->callBehavior('publicArchiveBeforeGetDates', $params, $args);
            dcCore::app()->ctx->archives = dcCore::app()->blog->getDates($params);

            if (dcCore::app()->ctx->archives->isEmpty()) {
                // There is no entries for the specified month.
                self::p404();
            } else {
                self::serveDocument('archive_month.html');
            }
        } else {
            // The specified URL is not a month.
            self::p404();
        }
    }

    /**
     * Output the Post page
     *
     * @param      null|string  $args   The arguments
     */
    public static function post(?string $args): void
    {
        if ($args == '') {
            // No entry was specified.
            self::p404();
        } else {
            dcCore::app()->blog->withoutPassword(false);

            $params = new ArrayObject(
                [
                    'post_url' => $args,
                ]
            );
            dcCore::app()->callBehavior('publicPostBeforeGetPosts', $params, $args);
            dcCore::app()->ctx->posts = dcCore::app()->blog->getPosts($params);

            $init_preview = [
                'content'    => '',
                'rawcontent' => '',
                'name'       => '',
                'mail'       => '',
                'site'       => '',
                'preview'    => false,
                'remember'   => false,
            ];
            dcCore::app()->ctx->comment_preview = new ArrayObject($init_preview);

            dcCore::app()->blog->withoutPassword(true);

            if (dcCore::app()->ctx->posts->isEmpty()) {
                // The specified entry does not exist.
                self::p404();
            } else {
                $post_id       = dcCore::app()->ctx->posts->post_id;
                $post_password = dcCore::app()->ctx->posts->post_password;

                // Password protected entry
                if ($post_password != '' && !dcCore::app()->ctx->preview) {
                    // Get passwords cookie
                    if (isset($_COOKIE['dc_passwd'])) {
                        $pwd_cookie = json_decode($_COOKIE['dc_passwd']);
                        if ($pwd_cookie === null) {
                            $pwd_cookie = [];
                        } else {
                            $pwd_cookie = (array) $pwd_cookie;
                        }
                    } else {
                        $pwd_cookie = [];
                    }

                    // Check for match
                    //
                    // Note: We must prefix post_id key with '#'' in pwd_cookie array in order to avoid integer conversion
                    // because MyArray["12345"] is treated as MyArray[12345] by PHP
                    if ((!empty($_POST['password']) && $_POST['password'] == $post_password)
                        || (isset($pwd_cookie['#' . $post_id]) && $pwd_cookie['#' . $post_id] == $post_password)) {
                        $pwd_cookie['#' . $post_id] = $post_password;
                        setcookie('dc_passwd', json_encode($pwd_cookie), 0, '/');
                    } else {
                        self::serveDocument('password-form.html', 'text/html', false);

                        return;
                    }
                }

                $post_comment = isset($_POST['c_name']) && isset($_POST['c_mail']) && isset($_POST['c_site']) && isset($_POST['c_content']) && dcCore::app()->ctx->posts->commentsActive();

                // Posting a comment
                if ($post_comment) {
                    // Spam honeypot
                    if (!empty($_POST['f_mail'])) {
                        http::head(412, 'Precondition Failed');
                        header('Content-Type: text/plain');
                        echo 'So Long, and Thanks For All the Fish';
                        // Exits immediately the application to preserve the server.
                        exit;
                    }

                    $name    = $_POST['c_name'];
                    $mail    = $_POST['c_mail'];
                    $site    = $_POST['c_site'];
                    $content = $_POST['c_content'];
                    $preview = !empty($_POST['preview']);

                    if ($content != '') {
                        # --BEHAVIOR-- publicBeforeCommentTransform
                        $buffer = dcCore::app()->callBehavior('publicBeforeCommentTransform', $content);
                        if ($buffer != '') {
                            $content = $buffer;
                        } else {
                            if (dcCore::app()->blog->settings->system->wiki_comments) {
                                dcCore::app()->initWikiComment();
                            } else {
                                dcCore::app()->initWikiSimpleComment();
                            }
                            $content = dcCore::app()->wikiTransform($content);
                        }
                        $content = dcCore::app()->HTMLfilter($content);
                    }

                    dcCore::app()->ctx->comment_preview['content']    = $content;
                    dcCore::app()->ctx->comment_preview['rawcontent'] = $_POST['c_content'];
                    dcCore::app()->ctx->comment_preview['name']       = $name;
                    dcCore::app()->ctx->comment_preview['mail']       = $mail;
                    dcCore::app()->ctx->comment_preview['site']       = $site;

                    if ($preview) {
                        # --BEHAVIOR-- publicBeforeCommentPreview
                        dcCore::app()->callBehavior('publicBeforeCommentPreview', dcCore::app()->ctx->comment_preview);

                        dcCore::app()->ctx->comment_preview['preview'] = true;
                    } else {
                        // Post the comment
                        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

                        $cur->comment_author  = $name;
                        $cur->comment_site    = html::clean($site);
                        $cur->comment_email   = html::clean($mail);
                        $cur->comment_content = $content;
                        $cur->post_id         = dcCore::app()->ctx->posts->post_id;
                        $cur->comment_status  = dcCore::app()->blog->settings->system->comments_pub ? dcBlog::COMMENT_PUBLISHED : dcBlog::COMMENT_PENDING;
                        $cur->comment_ip      = http::realIP();

                        $redir = dcCore::app()->ctx->posts->getURL();
                        $redir .= dcCore::app()->blog->settings->system->url_scan == 'query_string' ? '&' : '?';

                        try {
                            if (!text::isEmail($cur->comment_email)) {
                                throw new Exception(__('You must provide a valid email address.'));
                            }

                            # --BEHAVIOR-- publicBeforeCommentCreate
                            dcCore::app()->callBehavior('publicBeforeCommentCreate', $cur);
                            if ($cur->post_id) {
                                $comment_id = dcCore::app()->blog->addComment($cur);

                                # --BEHAVIOR-- publicAfterCommentCreate
                                dcCore::app()->callBehavior('publicAfterCommentCreate', $cur, $comment_id);
                            }

                            if ($cur->comment_status == dcBlog::COMMENT_PUBLISHED) {
                                $redir_arg = 'pub=1';
                            } else {
                                $redir_arg = 'pub=0';
                            }

                            $redir_arg .= filter_var(dcCore::app()->callBehavior('publicBeforeCommentRedir', $cur), FILTER_SANITIZE_URL);

                            header('Location: ' . $redir . $redir_arg);
                        } catch (Exception $e) {
                            dcCore::app()->ctx->form_error = $e->getMessage();
                        }
                    }
                }

                // The entry
                if (dcCore::app()->ctx->posts->trackbacksActive()) {
                    // Send additional headers if pingbacks/webmentions are allowed
                    header('X-Pingback: ' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('xmlrpc', dcCore::app()->blog->id));
                    header('Link: <' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('webmention') . '>; rel="webmention"');
                }
                self::serveDocument('post.html');
            }
        }
    }

    /**
     * Output the Post preview page
     *
     * @param      null|string  $args   The arguments
     */
    public static function preview(?string $args): void
    {
        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', $args, $m)) {
            // The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!dcCore::app()->auth->checkUser($user_id, null, $user_key)) {
                // The user has no access to the entry.
                self::p404();
            } else {
                dcCore::app()->ctx->preview = true;
                if (defined('DC_ADMIN_URL')) {
                    dcCore::app()->ctx->xframeoption = DC_ADMIN_URL;
                }
                self::post($post_url);
            }
        }
    }

    /**
     * Output the Feed page
     *
     * @param      null|string  $args   The arguments
     */
    public static function feed(?string $args): void
    {
        $type     = null;
        $comments = false;
        $cat_url  = false;
        $post_id  = null;
        $subtitle = '';

        $mime = 'application/xml';

        if (preg_match('!^([a-z]{2}(-[a-z]{2})?)/(.*)$!', $args, $matches)) {
            // Specific language feed
            $params = new ArrayObject(
                [
                    'lang' => $matches[1],
                ]
            );
            $args = $matches[3];
            dcCore::app()->callBehavior('publicFeedBeforeGetLangs', $params, $args);
            dcCore::app()->ctx->langs = dcCore::app()->blog->getLangs($params);

            if (dcCore::app()->ctx->langs->isEmpty()) {
                // The specified language does not exist.
                self::p404();

                return;
            }
            dcCore::app()->ctx->cur_lang = $matches[1];
        }

        if (preg_match('#^rss2/xslt$#', $args, $matches)) {
            // RSS XSLT stylesheet
            http::$cache_max_age = 60 * 60 * 24 * 7; // One week cache for XSLT
            self::serveDocument('rss2.xsl', 'text/xml');

            return;
        } elseif (preg_match('#^(atom|rss2)/comments/(\d+)$#', $args, $matches)) {
            // Post comments feed
            $type     = $matches[1];
            $comments = true;
            $post_id  = (int) $matches[2];
        } elseif (preg_match('#^(?:category/(.+)/)?(atom|rss2)(/comments)?$#', $args, $matches)) {
            // All posts or comments feed
            $type     = $matches[2];
            $comments = !empty($matches[3]);
            if (!empty($matches[1])) {
                $cat_url = $matches[1];
            }
        } else {
            // The specified Feed URL is malformed.
            self::p404();

            return;
        }

        if ($cat_url) {
            // Category feed
            $params = new ArrayObject(
                [
                    'cat_url'   => $cat_url,
                    'post_type' => 'post',
                ]
            );
            dcCore::app()->callBehavior('publicFeedBeforeGetCategories', $params, $args);
            dcCore::app()->ctx->categories = dcCore::app()->blog->getCategories($params);

            if (dcCore::app()->ctx->categories->isEmpty()) {
                // The specified category does no exist.
                self::p404();

                return;
            }

            $subtitle = ' - ' . dcCore::app()->ctx->categories->cat_title;
        } elseif ($post_id) {
            // Specific post
            $params = new ArrayObject(
                [
                    'post_id'   => $post_id,
                    'post_type' => '',
                ]
            );
            dcCore::app()->callBehavior('publicFeedBeforeGetPosts', $params, $args);
            dcCore::app()->ctx->posts = dcCore::app()->blog->getPosts($params);

            if (dcCore::app()->ctx->posts->isEmpty()) {
                # The specified post does not exist.
                self::p404();

                return;
            }

            $subtitle = ' - ' . dcCore::app()->ctx->posts->post_title;
        }

        $tpl = $type;
        if ($comments) {
            // Comments feed
            $tpl .= '-comments';
            dcCore::app()->ctx->nb_comment_per_page = dcCore::app()->blog->settings->system->nb_comment_per_feed;
        } else {
            // Posts feed
            dcCore::app()->ctx->nb_entry_per_page = dcCore::app()->blog->settings->system->nb_post_per_feed;
            dcCore::app()->ctx->short_feed_items  = dcCore::app()->blog->settings->system->short_feed_items;
        }
        $tpl .= '.xml';

        if ($type == 'atom') {
            $mime = 'application/atom+xml';
        }

        dcCore::app()->ctx->feed_subtitle = $subtitle;

        header('X-Robots-Tag: ' . context::robotsPolicy(dcCore::app()->blog->settings->system->robots_policy, ''));
        http::$cache_max_age = 60 * 60; // 1 hour cache for feed
        self::serveDocument($tpl, $mime);
        if (!$comments && !$cat_url) {
            // Check if some entries must be published
            dcCore::app()->blog->publishScheduledEntries();
        }
    }

    /**
     * Cope with incoming Trackbacks
     *
     * @param      null|string  $args   The arguments
     */
    public static function trackback(?string $args): void
    {
        if (!preg_match('/^\d+$/', $args)) {
            // The specified trackback URL is not an number
            self::p404();
        } else {
            // Save locally post_id from args
            $post_id = (int) $args;

            $args = [
                'post_id' => $post_id,
                'type'    => 'trackback',
            ];

            # --BEHAVIOR-- publicBeforeReceiveTrackback
            dcCore::app()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

            $tb = new dcTrackback(dcCore::app());
            $tb->receiveTrackback($post_id);
        }
    }

    /**
     * Cope with incoming Webmention
     *
     * @param      null|string  $args   The arguments
     */
    public static function webmention(?string $args): void
    {
        $args = [
            'type' => 'webmention',
        ];

        # --BEHAVIOR-- publicBeforeReceiveTrackback
        dcCore::app()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

        $tb = new dcTrackback(dcCore::app());
        $tb->receiveWebmention();
    }

    /**
     * Cope with XML-RPC services URLs
     *
     * Limited to pingbacks only
     *
     * @param      null|string  $args   The arguments
     */
    public static function xmlrpc(?string $args): void
    {
        $blog_id = preg_replace('#^([^/]*).*#', '$1', $args);
        $server  = new dcXmlRpc($blog_id);
        $server->serve();
    }

    /**
     * Rick Roll script kiddies which try the administrative page wordpress URLs on the blog :-)
     *
     * https://example.com/wp-admin and https://example.com/wp-login (see inc/prepend.php)
     *
     * @param      null|string  $args   The arguments
     */
    public static function wpfaker(?string $args): void
    {
        // Rick Roll script kiddies
        http::redirect('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        exit;
    }
}
