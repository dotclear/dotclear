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
    public $args;

    protected function getHomeType()
    {
        return dcCore::app()->blog->settings->system->static_home ? 'static' : 'default';
    }

    public function isHome($type)
    {
        return $type == $this->getHomeType();
    }

    public function getURLFor($type, $value = '')
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

    public function register($type, $url, $representation, $handler)
    {
        $t = new ArrayObject([$type, $url, $representation, $handler]);
        dcCore::app()->callBehavior('publicRegisterURL', $t);
        parent::register($t[0], $t[1], $t[2], $t[3]);
    }

    public static function p404()
    {
        throw new Exception('Page not found', 404);
    }

    public static function default404($args, $type, $e)
    {
        if ($e->getCode() != 404) {
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

    protected static function serveDocument($tpl, $content_type = 'text/html', $http_cache = true, $http_etag = true)
    {
        if (dcCore::app()->ctx->nb_entry_per_page === null) {
            dcCore::app()->ctx->nb_entry_per_page = dcCore::app()->blog->settings->system->nb_post_per_page;
        }
        if (dcCore::app()->ctx->nb_entry_first_page === null) {
            dcCore::app()->ctx->nb_entry_first_page = dcCore::app()->ctx->nb_entry_per_page;
        }

        $tpl_file = dcCore::app()->tpl->getFilePath($tpl);

        if (!$tpl_file) {
            throw new Exception('Unable to find template ');
        }

        $result = new ArrayObject();

        dcCore::app()->ctx->current_tpl  = $tpl;
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

        $result['content']      = dcCore::app()->tpl->getData(dcCore::app()->ctx->current_tpl);
        $result['content_type'] = dcCore::app()->ctx->content_type;
        $result['tpl']          = dcCore::app()->ctx->current_tpl;
        $result['blogupddt']    = dcCore::app()->blog->upddt;
        $result['headers']      = headers_list();

        # --BEHAVIOR-- urlHandlerServeDocument
        dcCore::app()->callBehavior('urlHandlerServeDocument', $result);

        if (dcCore::app()->ctx->http_cache && dcCore::app()->ctx->http_etag) {
            http::etag($result['content'], http::getSelfURI());
        }
        echo $result['content'];
    }

    public function getDocument()
    {
        $type = '';

        if ($this->mode == 'path_info') {
            $part = substr($_SERVER['PATH_INFO'], 1);
        } else {
            $part = '';

            $qs = $this->parseQueryString();

            # Recreates some _GET and _REQUEST pairs
            if (!empty($qs)) {
                foreach ($_GET as $k => $v) {
                    if (isset($_REQUEST[$k])) {
                        unset($_REQUEST[$k]);
                    }
                }
                $_GET     = $qs;
                $_REQUEST = array_merge($qs, $_REQUEST);

                foreach ($qs as $k => $v) {
                    if ($v === null) {
                        $part = $k;
                        unset($_GET[$k], $_REQUEST[$k]);
                    }

                    break;
                }
            }
        }

        $_SERVER['URL_REQUEST_PART'] = $part;

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

    public static function home($args)
    {
        // Page number may have been set by self::lang() which ends with a call to self::home(null)
        $n = $args ? self::getPageNumber($args) : dcCore::app()->public->getPageNumber();

        if ($args && !$n) {
            # Then specified URL went unrecognized by all URL handlers and
            # defaults to the home page, but is not a page number.
            self::p404();
        } else {
            dcCore::app()->url->type = 'default';
            if ($n) {
                dcCore::app()->public->setPageNumber($n);
                if ($n > 1) {
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

    public static function static_home($args)
    {
        dcCore::app()->url->type = 'static';

        if (empty($_GET['q'])) {
            self::serveDocument('static.html');
            dcCore::app()->blog->publishScheduledEntries();
        } else {
            self::search();
        }
    }

    public static function search()
    {
        if (dcCore::app()->blog->settings->system->no_search) {

            # Search is disabled for this blog.
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

    public static function lang($args)
    {
        $n      = self::getPageNumber($args);
        $params = new ArrayObject([
            'lang' => $args, ]);

        dcCore::app()->callBehavior('publicLangBeforeGetLangs', $params, $args);

        dcCore::app()->ctx->langs = dcCore::app()->blog->getLangs($params);

        if (dcCore::app()->ctx->langs->isEmpty()) {
            # The specified language does not exist.
            self::p404();
        } else {
            if ($n) {
                dcCore::app()->public->setPageNumber($n);
            }
            dcCore::app()->ctx->cur_lang = $args;
            self::home(null);
        }
    }

    public static function category($args)
    {
        $n = self::getPageNumber($args);

        if ($args == '' && !$n) {
            # No category was specified.
            self::p404();
        } else {
            $params = new ArrayObject([
                'cat_url'       => $args,
                'post_type'     => 'post',
                'without_empty' => false, ]);

            dcCore::app()->callBehavior('publicCategoryBeforeGetCategories', $params, $args);

            dcCore::app()->ctx->categories = dcCore::app()->blog->getCategories($params);

            if (dcCore::app()->ctx->categories->isEmpty()) {
                # The specified category does no exist.
                self::p404();
            } else {
                if ($n) {
                    dcCore::app()->public->setPageNumber($n);
                }
                self::serveDocument('category.html');
            }
        }
    }

    public static function archive($args)
    {
        # Nothing or year and month
        if ($args == '') {
            self::serveDocument('archive.html');
        } elseif (preg_match('|^/(\d{4})/(\d{2})$|', $args, $m)) {
            $params = new ArrayObject([
                'year'  => $m[1],
                'month' => $m[2],
                'type'  => 'month', ]);

            dcCore::app()->callBehavior('publicArchiveBeforeGetDates', $params, $args);

            dcCore::app()->ctx->archives = dcCore::app()->blog->getDates($params);

            if (dcCore::app()->ctx->archives->isEmpty()) {
                # There is no entries for the specified period.
                self::p404();
            } else {
                self::serveDocument('archive_month.html');
            }
        } else {
            # The specified URL is not a date.
            self::p404();
        }
    }

    public static function post($args)
    {
        if ($args == '') {
            # No entry was specified.
            self::p404();
        } else {
            dcCore::app()->blog->withoutPassword(false);

            $params = new ArrayObject([
                'post_url' => $args, ]);

            dcCore::app()->callBehavior('publicPostBeforeGetPosts', $params, $args);

            dcCore::app()->ctx->posts = dcCore::app()->blog->getPosts($params);

            dcCore::app()->ctx->comment_preview               = new ArrayObject();
            dcCore::app()->ctx->comment_preview['content']    = '';
            dcCore::app()->ctx->comment_preview['rawcontent'] = '';
            dcCore::app()->ctx->comment_preview['name']       = '';
            dcCore::app()->ctx->comment_preview['mail']       = '';
            dcCore::app()->ctx->comment_preview['site']       = '';
            dcCore::app()->ctx->comment_preview['preview']    = false;
            dcCore::app()->ctx->comment_preview['remember']   = false;

            dcCore::app()->blog->withoutPassword(true);

            if (dcCore::app()->ctx->posts->isEmpty()) {
                # The specified entry does not exist.
                self::p404();
            } else {
                $post_id       = dcCore::app()->ctx->posts->post_id;
                $post_password = dcCore::app()->ctx->posts->post_password;

                # Password protected entry
                if ($post_password != '' && !dcCore::app()->ctx->preview) {
                    # Get passwords cookie
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

                    # Check for match
                    # Note: We must prefix post_id key with '#'' in pwd_cookie array in order to avoid integer conversion
                    # because MyArray["12345"] is treated as MyArray[12345]
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

                # Posting a comment
                if ($post_comment) {
                    # Spam trap
                    if (!empty($_POST['f_mail'])) {
                        http::head(412, 'Precondition Failed');
                        header('Content-Type: text/plain');
                        echo 'So Long, and Thanks For All the Fish';
                        # Exits immediately the application to preserve the server.
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
                        # Post the comment
                        $cur                  = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'comment');
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

                # The entry
                if (dcCore::app()->ctx->posts->trackbacksActive()) {
                    header('X-Pingback: ' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('xmlrpc', dcCore::app()->blog->id));
                    header('Link: <' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('webmention') . '>; rel="webmention"');
                }
                self::serveDocument('post.html');
            }
        }
    }

    public static function preview($args)
    {
        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', $args, $m)) {
            # The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!dcCore::app()->auth->checkUser($user_id, null, $user_key)) {
                # The user has no access to the entry.
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

    public static function feed($args)
    {
        $type     = null;
        $comments = false;
        $cat_url  = false;
        $post_id  = null;
        $subtitle = '';

        $mime = 'application/xml';

        if (preg_match('!^([a-z]{2}(-[a-z]{2})?)/(.*)$!', $args, $m)) {
            $params = new ArrayObject(['lang' => $m[1]]);

            $args = $m[3];

            dcCore::app()->callBehavior('publicFeedBeforeGetLangs', $params, $args);

            dcCore::app()->ctx->langs = dcCore::app()->blog->getLangs($params);

            if (dcCore::app()->ctx->langs->isEmpty()) {
                # The specified language does not exist.
                self::p404();

                return;
            }
            dcCore::app()->ctx->cur_lang = $m[1];
        }

        if (preg_match('#^rss2/xslt$#', $args, $m)) {
            # RSS XSLT stylesheet
            http::$cache_max_age = 60 * 60; // 1 hour cache for feed
            self::serveDocument('rss2.xsl', 'text/xml');

            return;
        } elseif (preg_match('#^(atom|rss2)/comments/(\d+)$#', $args, $m)) {
            # Post comments feed
            $type     = $m[1];
            $comments = true;
            $post_id  = (int) $m[2];
        } elseif (preg_match('#^(?:category/(.+)/)?(atom|rss2)(/comments)?$#', $args, $m)) {
            # All posts or comments feed
            $type     = $m[2];
            $comments = !empty($m[3]);
            if (!empty($m[1])) {
                $cat_url = $m[1];
            }
        } else {
            # The specified Feed URL is malformed.
            self::p404();

            return;
        }

        if ($cat_url) {
            $params = new ArrayObject([
                'cat_url'   => $cat_url,
                'post_type' => 'post', ]);

            dcCore::app()->callBehavior('publicFeedBeforeGetCategories', $params, $args);

            dcCore::app()->ctx->categories = dcCore::app()->blog->getCategories($params);

            if (dcCore::app()->ctx->categories->isEmpty()) {
                # The specified category does no exist.
                self::p404();

                return;
            }

            $subtitle = ' - ' . dcCore::app()->ctx->categories->cat_title;
        } elseif ($post_id) {
            $params = new ArrayObject([
                'post_id'   => $post_id,
                'post_type' => '', ]);

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
            $tpl .= '-comments';
            dcCore::app()->ctx->nb_comment_per_page = dcCore::app()->blog->settings->system->nb_comment_per_feed;
        } else {
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
            dcCore::app()->blog->publishScheduledEntries();
        }
    }

    public static function trackback($args)
    {
        if (!preg_match('/^\d+$/', $args)) {
            # The specified trackback URL is not an number
            self::p404();
        } else {
            // Save locally post_id from args
            $post_id = (int) $args;

            if (!is_array($args)) {
                $args = [];
            }

            $args['post_id'] = $post_id;
            $args['type']    = 'trackback';

            # --BEHAVIOR-- publicBeforeReceiveTrackback
            dcCore::app()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

            $tb = new dcTrackback(dcCore::app());
            $tb->receiveTrackback($post_id);
        }
    }

    public static function webmention($args)
    {
        if (!is_array($args)) {
            $args = [];
        }

        $args['type'] = 'webmention';

        # --BEHAVIOR-- publicBeforeReceiveTrackback
        dcCore::app()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

        $tb = new dcTrackback(dcCore::app());
        $tb->receiveWebmention();
    }

    public static function rsd($args)
    {
        http::cache(dcCore::app()->cache['mod_files'], dcCore::app()->cache['mod_ts']);

        header('Content-Type: text/xml; charset=UTF-8');
        echo
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">' . "\n" .
        "<service>\n" .
        "  <engineName>Dotclear</engineName>\n" .
        "  <engineLink>https://dotclear.org/</engineLink>\n" .
        '  <homePageLink>' . html::escapeHTML(dcCore::app()->blog->url) . "</homePageLink>\n";

        if (dcCore::app()->blog->settings->system->enable_xmlrpc) {
            $u = sprintf(DC_XMLRPC_URL, dcCore::app()->blog->url, dcCore::app()->blog->id);

            echo
                "  <apis>\n" .
                '    <api name="WordPress" blogID="1" preferred="true" apiLink="' . $u . '"/>' . "\n" .
                '    <api name="Movable Type" blogID="1" preferred="false" apiLink="' . $u . '"/>' . "\n" .
                '    <api name="MetaWeblog" blogID="1" preferred="false" apiLink="' . $u . '"/>' . "\n" .
                '    <api name="Blogger" blogID="1" preferred="false" apiLink="' . $u . '"/>' . "\n" .
                "  </apis>\n";
        }

        echo
            "</service>\n" .
            "</rsd>\n";
    }

    public static function xmlrpc($args)
    {
        $blog_id = preg_replace('#^([^/]*).*#', '$1', $args);
        $server  = new dcXmlRpc(dcCore::app(), $blog_id);
        $server->serve();
    }

    public static function wpfaker($args)
    {
        // Rick Roll script kiddies
        http::redirect('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        exit;
    }
}
