<?php

/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\UrlHandler;
use Dotclear\Helper\Text;
use Dotclear\Interface\Core\UrlInterface;
use Exception;

/**
 * URL Handler for public urls
 */
class Url extends UrlHandler implements UrlInterface
{
    /** @var string URI arguments (depends on URL representation) */
    public ?string $args = null;

    /**
     * Gets the home type set for the blog.
     *
     * @return     string  The home type (static or default)
     */
    public function getHomeType(): string
    {
        return App::blog()->settings()->system->static_home ? 'static' : 'default';
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
     */
    public function getURLFor(string $type, string $value = ''): string
    {
        # --BEHAVIOR-- publicGetURLFor -- string, string
        $url = App::behavior()->callBehavior('publicGetURLFor', $type, $value);
        if ($url === '') {
            $url = $this->getBase($type);
            if ($value !== '') {
                if ($url !== '') {
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
     * @param      callable         $handler         The handler
     */
    public function register(string $type, string $url, string $representation, $handler): void
    {
        $url_handler = new ArrayObject([$type, $url, $representation, $handler]);
        # --BEHAVIOR-- publicRegisterURL -- ArrayObject
        App::behavior()->callBehavior('publicRegisterURL', $url_handler);
        parent::register($url_handler[0], $url_handler[1], $url_handler[2], $url_handler[3]);   // @phpstan-ignore-line
    }

    /**
     * Throws a 404 (page not found) exception
     *
     * @throws     Exception
     */
    public static function p404(): never
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
    public static function getPageNumber(&$args): bool|int
    {
        if (preg_match('#(^|/)page/(\d+)$#', (string) $args, $m)) {
            $n = (int) $m[2];
            if ($n > 0) {
                $args = preg_replace('#(^|/)page/(\d+)$#', '', (string) $args);

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
    public static function serveDocument(string $tpl_name, string $content_type = 'text/html', bool $http_cache = true, bool $http_etag = true): void
    {
        if (App::frontend()->context()->nb_entry_per_page === null) {
            App::frontend()->context()->nb_entry_per_page = App::blog()->settings()->system->nb_post_per_page;
        }
        if (App::frontend()->context()->nb_entry_first_page === null) {
            App::frontend()->context()->nb_entry_first_page = App::frontend()->context()->nb_entry_per_page;
        }

        $tpl_file = App::frontend()->template()->getFilePath($tpl_name);

        if (!$tpl_file) {
            throw new Exception('Unable to find template ');
        }

        App::frontend()->context()->current_tpl  = $tpl_name;
        App::frontend()->context()->content_type = $content_type;
        App::frontend()->context()->http_cache   = $http_cache;
        App::frontend()->context()->http_etag    = $http_etag;

        # --BEHAVIOR-- urlHandlerBeforeGetData -- Ctx
        App::behavior()->callBehavior('urlHandlerBeforeGetData', App::frontend()->context());

        if (App::frontend()->context()->http_cache) {
            App::cache()->addFile($tpl_file);
            Http::cache(App::cache()->getFiles(), App::cache()->getTimes());
        }

        header('Content-Type: ' . App::frontend()->context()->content_type . '; charset=UTF-8');

        // Additional headers
        $headers = new ArrayObject();
        if (App::blog()->settings()->system->prevents_clickjacking) {
            // Prevents Clickjacking as far as possible
            $header = 'X-Frame-Options: SAMEORIGIN';
            if (App::frontend()->context()->exists('xframeoption')) {
                $url = parse_url(App::frontend()->context()->xframeoption);
                if (is_array($url) && isset($url['scheme']) && isset($url['host'])) {
                    $header = sprintf(
                        'Content-Security-Policy: frame-ancestors \'self\' %s',
                        $url['scheme'] . '://' . $url['host']
                    );
                }
            }

            $headers->append($header);
        }

        # --BEHAVIOR-- urlHandlerServeDocumentHeaders -- ArrayObject
        App::behavior()->callBehavior('urlHandlerServeDocumentHeaders', $headers);

        // Send additional headers if any
        foreach ($headers as $header) {
            header($header);
        }

        /**
         * @var        ArrayObject<string, mixed>
         */
        $result = new ArrayObject([
            'content'      => App::frontend()->template()->getData(App::frontend()->context()->current_tpl),
            'content_type' => App::frontend()->context()->content_type,
            'tpl'          => App::frontend()->context()->current_tpl,
            'blogupddt'    => App::blog()->upddt(),
            'headers'      => headers_list(),
        ]);

        # --BEHAVIOR-- urlHandlerServeDocument -- ArrayObject
        App::behavior()->callBehavior('urlHandlerServeDocument', $result);

        if (App::frontend()->context()->http_cache && App::frontend()->context()->http_etag) {
            Http::etag($result['content'], Http::getSelfURI());
        }
        echo $result['content'];
    }

    /**
     * Gets the appropriate page based on requested URI.
     */
    public function getDocument(): void
    {
        $type = '';

        if ($this->mode === 'path_info') {
            $part = substr((string) $_SERVER['PATH_INFO'], 1);
        } else {
            $part = '';

            $query_string = $this->parseQueryString();

            # Recreates some _GET and _REQUEST pairs
            if ($query_string !== []) {
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

        # --BEHAVIOR-- urlHandlerGetArgsDocument -- Urlhandler
        App::behavior()->callBehavior('urlHandlerGetArgsDocument', $this);

        if (!$type) {
            $this->setType($this->getHomeType());
            $this->callDefaultHandler($this->args);
        } else {
            $this->setType($type);
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
        Http::head(404, 'Not Found');

        App::url()->setType('404');
        App::frontend()->context()->current_tpl  = '404.html';
        App::frontend()->context()->content_type = 'text/html';

        echo App::frontend()->template()->getData(App::frontend()->context()->current_tpl);

        # --BEHAVIOR-- publicAfterDocument --
        App::behavior()->callBehavior('publicAfterDocumentV2');
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
        $page_number = $args ? self::getPageNumber($args) : App::frontend()->getPageNumber();

        if ($args && !$page_number) {
            // Then specified URL went unrecognized by all URL handlers and
            // defaults to the home page, but is not a page number.
            self::p404();
        } else {
            App::url()->setType('default');
            if ($page_number) {
                App::frontend()->setPageNumber($page_number);
                if ($page_number > 1) {
                    App::url()->setType('default-page');
                }
            }

            if (empty($_GET['q'])) {
                if (App::blog()->settings()->system->nb_post_for_home !== null) {
                    App::frontend()->context()->nb_entry_first_page = App::blog()->settings()->system->nb_post_for_home;
                }
                self::serveDocument('home.html');
                App::blog()->publishScheduledEntries();
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
        App::url()->setType('static');

        if (empty($_GET['q'])) {
            self::serveDocument('static.html');
            App::blog()->publishScheduledEntries();
        } else {
            if ($args && $page_number = self::getPageNumber($args)) {
                App::frontend()->setPageNumber($page_number);
            }

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
        if (App::blog()->settings()->system->no_search) {
            // Search is disabled for this blog.
            self::p404();
        } else {
            App::url()->setType('search');

            App::frontend()->search = empty($_GET['q']) ? '' : Html::escapeHTML(rawurldecode((string) $_GET['q']));
            if (App::frontend()->search) {
                $params = new ArrayObject(['search' => App::frontend()->search]);
                # --BEHAVIOR-- publicBeforeSearchCount -- ArrayObject
                App::behavior()->callBehavior('publicBeforeSearchCount', $params);
                App::frontend()->search_count = App::blog()->getPosts($params, true)->f(0);
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
        # --BEHAVIOR-- publicLangBeforeGetLangs -- ArrayObject, string|null
        App::behavior()->callBehavior('publicLangBeforeGetLangs', $params, $args);
        App::frontend()->context()->langs = App::blog()->getLangs($params);

        if (App::frontend()->context()->langs->isEmpty()) {
            # The specified language does not exist.
            self::p404();
        } else {
            if ($page_number) {
                App::frontend()->setPageNumber($page_number);
            }
            App::frontend()->context()->cur_lang = $args;
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
            # --BEHAVIOR-- publicCategoryBeforeGetCategories -- ArrayObject, string|null
            App::behavior()->callBehavior('publicCategoryBeforeGetCategories', $params, $args);
            App::frontend()->context()->categories = App::blog()->getCategories($params);

            if (App::frontend()->context()->categories->isEmpty()) {
                // The specified category does no exist.
                self::p404();
            } else {
                if ($page_number) {
                    App::frontend()->setPageNumber($page_number);
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
            # --BEHAVIOR-- publicArchiveBeforeGetDates -- ArrayObject, string|null
            App::behavior()->callBehavior('publicArchiveBeforeGetDates', $params, $args);
            App::frontend()->context()->archives = App::blog()->getDates($params);

            if (App::frontend()->context()->archives->isEmpty()) {
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
            App::blog()->withoutPassword(false);

            $params = new ArrayObject(
                [
                    'post_url' => $args,
                ]
            );
            # --BEHAVIOR-- publicPostBeforeGetPosts -- ArrayObject, string|null
            App::behavior()->callBehavior('publicPostBeforeGetPosts', $params, $args);
            App::frontend()->context()->posts = App::blog()->getPosts($params);

            $init_preview = [
                'content'    => '',
                'rawcontent' => '',
                'name'       => '',
                'mail'       => '',
                'site'       => '',
                'preview'    => false,
                'remember'   => false,
            ];
            App::frontend()->context()->comment_preview = new ArrayObject($init_preview);

            App::blog()->withoutPassword(true);

            if (App::frontend()->context()->posts->isEmpty()) {
                // The specified entry does not exist.
                self::p404();
            } else {
                $post_id       = App::frontend()->context()->posts->post_id;
                $post_password = App::frontend()->context()->posts->post_password;

                // Password protected entry
                if ($post_password != '' && !App::frontend()->context()->preview) {
                    // Get passwords cookie
                    if (isset($_COOKIE['dc_passwd'])) {
                        $pwd_cookie = json_decode((string) $_COOKIE['dc_passwd'], null, 512, JSON_THROW_ON_ERROR);
                        $pwd_cookie = $pwd_cookie === null ? [] : (array) $pwd_cookie;
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
                        setcookie('dc_passwd', json_encode($pwd_cookie, JSON_THROW_ON_ERROR), ['expires' => 0, 'path' => '/']);
                    } else {
                        self::serveDocument('password-form.html', 'text/html', false);

                        return;
                    }
                }

                $post_comment = isset($_POST['c_name']) && isset($_POST['c_mail']) && isset($_POST['c_site']) && isset($_POST['c_content']) && App::frontend()->context()->posts->commentsActive();

                // Posting a comment
                if ($post_comment) {
                    // Spam honeypot
                    if (!empty($_POST['f_mail'])) {
                        Http::head(412, 'Precondition Failed');
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
                        # --BEHAVIOR-- publicBeforeCommentTransform -- string
                        $buffer = App::behavior()->callBehavior('publicBeforeCommentTransform', $content);
                        if ($buffer !== '') {
                            $content = $buffer;
                        } else {
                            if (App::blog()->settings()->system->wiki_comments) {
                                App::filter()->initWikiComment();
                            } else {
                                App::filter()->initWikiSimpleComment();
                            }
                            $content = App::filter()->wikiTransform($content);
                        }
                        $content = App::filter()->HTMLfilter($content);
                    }

                    App::frontend()->context()->comment_preview['content']    = (string) $content;
                    App::frontend()->context()->comment_preview['rawcontent'] = $_POST['c_content'];
                    App::frontend()->context()->comment_preview['name']       = $name;
                    App::frontend()->context()->comment_preview['mail']       = $mail;
                    App::frontend()->context()->comment_preview['site']       = $site;

                    if ($preview) {
                        # --BEHAVIOR-- publicBeforeCommentPreview -- ArrayObject
                        App::behavior()->callBehavior('publicBeforeCommentPreview', App::frontend()->context()->comment_preview);

                        $content = (string) $content;
                        # --BEHAVIOR-- coreContentFilter -- string, array<int, array<int, string>> -- since 2.34
                        App::behavior()->callBehavior(
                            'coreContentFilter',
                            'comment',
                            [
                                [&$content, 'html'],
                            ]
                        );
                        App::frontend()->context()->comment_preview['content'] = $content;

                        App::frontend()->context()->comment_preview['preview'] = true;
                    } else {
                        // Post the comment
                        $cur = App::blog()->openCommentCursor();

                        $cur->comment_author  = Html::clean($name);
                        $cur->comment_site    = Html::clean($site);
                        $cur->comment_email   = Html::clean($mail);
                        $cur->comment_content = $content;
                        $cur->post_id         = App::frontend()->context()->posts->post_id;
                        $cur->comment_status  = App::blog()->settings()->system->comments_pub ? App::status()->comment()::PUBLISHED : App::status()->comment()::UNPUBLISHED;
                        $cur->comment_ip      = Http::realIP();

                        $redir = App::frontend()->context()->posts->getURL();
                        $redir .= App::blog()->settings()->system->url_scan == 'query_string' ? '&' : '?';

                        try {
                            if (!Text::isEmail($cur->comment_email)) {
                                throw new Exception(__('You must provide a valid email address.'));
                            }

                            # --BEHAVIOR-- publicBeforeCommentCreate - Cursor
                            App::behavior()->callBehavior('publicBeforeCommentCreate', $cur);
                            if ($cur->post_id) {
                                $comment_id = App::blog()->addComment($cur);

                                # --BEHAVIOR-- publicAfterCommentCreate - Cursor, int
                                App::behavior()->callBehavior('publicAfterCommentCreate', $cur, $comment_id);
                            }

                            $redir_arg = App::status()->comment()->isRestricted($cur->comment_status) ? 'pub=0' : 'pub=1';

                            # --BEHAVIOR-- publicBeforeCommentRedir -- Cursor
                            $redir_arg .= filter_var(App::behavior()->callBehavior('publicBeforeCommentRedir', $cur), FILTER_SANITIZE_URL);

                            header('Location: ' . $redir . $redir_arg);
                        } catch (Exception $e) {
                            App::frontend()->context()->form_error = $e->getMessage();
                        }
                    }
                }

                // The entry
                if (App::frontend()->context()->posts->trackbacksActive()) {
                    // Send additional headers if pingbacks/webmentions are allowed
                    header('X-Pingback: ' . App::blog()->url() . App::url()->getURLFor('xmlrpc', App::blog()->id()));
                    header('Link: <' . App::blog()->url() . App::url()->getURLFor('webmention') . '>; rel="webmention"');
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
        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', (string) $args, $m)) {
            // The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!App::auth()->checkUser($user_id, null, $user_key)) {
                // The user has no access to the entry.
                self::p404();
            } else {
                App::frontend()->context()->preview = true;
                if (App::config()->adminUrl() !== '') {
                    App::frontend()->context()->xframeoption = App::config()->adminUrl();
                }
                self::post($post_url);
            }
        }
    }

    /**
     * Output the Theme preview page
     *
     * @param      null|string  $args   The arguments
     */
    public static function try(?string $args): void
    {
        $page_number = $args ? self::getPageNumber($args) : App::frontend()->getPageNumber();
        if ($page_number) {
            App::frontend()->setPageNumber($page_number);
        }

        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', (string) $args, $m)) {
            // The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $theme    = $m[3];
            if (!App::auth()->checkUser($user_id, null, $user_key)) {
                // The user has no access to the theme preview.
                self::p404();
            } else {
                $current = App::blog()->settings()->system->theme;

                // Switch to theme to try
                App::blog()->settings()->system->set('theme', $theme);
                App::frontend()->theme = $theme;

                // Simulate Utility\Frontend::process() for theme preview
                // ------------------------------------------------------
                App::frontend()->parent_theme = App::themes()->moduleInfo(App::frontend()->theme, 'parent');
                // Loading _public.php file for selected theme
                App::themes()->loadNsFile(App::frontend()->theme, 'public');
                // Loading translations for selected theme
                if (is_string(App::frontend()->parent_theme) && App::frontend()->parent_theme !== '') {
                    App::themes()->loadModuleL10N(App::frontend()->parent_theme, App::lang()->getLang(), 'main');
                }
                App::themes()->loadModuleL10N(App::frontend()->theme, App::lang()->getLang(), 'main');
                // --BEHAVIOR-- publicPrepend --
                App::behavior()->callBehavior('publicPrependV2');
                // Prepare the HTTP cache thing
                App::cache()->resetFiles();
                App::cache()->addFiles(get_included_files());
                $tpl_path = [
                    App::blog()->themesPath() . '/' . App::frontend()->theme . '/tpl',
                ];
                if (App::frontend()->parent_theme) {
                    $tpl_path[] = App::blog()->themesPath() . '/' . App::frontend()->parent_theme . '/tpl';
                }
                $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
                $dir    = implode(DIRECTORY_SEPARATOR, [App::config()->dotclearRoot(), 'inc', 'public', Utility::TPL_ROOT, $tplset]);
                if (!empty($tplset) && is_dir($dir)) {
                    App::frontend()->template()->setPath(
                        $tpl_path,
                        $dir,
                        App::frontend()->template()->getPath()
                    );
                } else {
                    App::frontend()->template()->setPath(
                        $tpl_path,
                        App::frontend()->template()->getPath()
                    );
                }
                // ------------------------------------------------------

                // Don't use template cache
                App::frontend()->template()->use_cache = false;
                // Reset HTTP cache
                App::cache()->resetTimes();
                if (App::config()->adminUrl() !== '') {
                    App::frontend()->context()->xframeoption = App::config()->adminUrl();
                }

                // Then go to blog home page
                self::home(null);

                // And finally back to current theme
                App::blog()->settings()->system->set('theme', $current);
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

        if (preg_match('!^([a-z]{2}(-[a-z]{2})?)/(.*)$!', (string) $args, $matches)) {
            // Specific language feed
            $params = new ArrayObject(
                [
                    'lang' => $matches[1],
                ]
            );
            $args = $matches[3];
            # --BEHAVIOR-- publicFeedBeforeGetLangs -- ArrayObject, string|null
            App::behavior()->callBehavior('publicFeedBeforeGetLangs', $params, $args);
            App::frontend()->context()->langs = App::blog()->getLangs($params);

            if (App::frontend()->context()->langs->isEmpty()) {
                // The specified language does not exist.
                self::p404();
            }
            App::frontend()->context()->cur_lang = $matches[1];
        }

        if (preg_match('#^rss2/xslt$#', (string) $args, $matches)) {
            // RSS XSLT stylesheet
            Http::$cache_max_age = 60 * 60 * 24 * 7; // One week cache for XSLT
            self::serveDocument('rss2.xsl', 'text/xml');

            return;
        } elseif (preg_match('#^(atom|rss2)/comments/(\d+)$#', (string) $args, $matches)) {
            // Post comments feed
            $type     = $matches[1];
            $comments = true;
            $post_id  = (int) $matches[2];
        } elseif (preg_match('#^(?:category/(.+)/)?(atom|rss2)(/comments)?$#', (string) $args, $matches)) {
            // All posts or comments feed
            $type     = $matches[2];
            $comments = isset($matches[3]);
            if ($matches[1] !== '') {
                // There is a category
                $cat_url = $matches[1];
            }
        } else {
            // The specified Feed URL is malformed.
            self::p404();
        }

        if ($cat_url) {
            // Category feed
            $params = new ArrayObject(
                [
                    'cat_url'   => $cat_url,
                    'post_type' => 'post',
                ]
            );
            # --BEHAVIOR-- publicFeedBeforeGetCategories -- ArrayObject, string|null
            App::behavior()->callBehavior('publicFeedBeforeGetCategories', $params, $args);
            App::frontend()->context()->categories = App::blog()->getCategories($params);

            if (App::frontend()->context()->categories->isEmpty()) {
                // The specified category does no exist.
                self::p404();
            }

            $subtitle = ' - ' . App::frontend()->context()->categories->cat_title;
        } elseif ($post_id) {
            // Specific post
            $params = new ArrayObject(
                [
                    'post_id'   => $post_id,
                    'post_type' => '',
                ]
            );
            # --BEHAVIOR-- publicFeedBeforeGetPosts -- ArrayObject, string|null
            App::behavior()->callBehavior('publicFeedBeforeGetPosts', $params, $args);
            App::frontend()->context()->posts = App::blog()->getPosts($params);

            if (App::frontend()->context()->posts->isEmpty()) {
                # The specified post does not exist.
                self::p404();
            }

            $subtitle = ' - ' . App::frontend()->context()->posts->post_title;
        }

        $tpl = $type;
        if ($comments) {
            // Comments feed
            $tpl .= '-comments';
            App::frontend()->context()->nb_comment_per_page = App::blog()->settings()->system->nb_comment_per_feed;
        } else {
            // Posts feed
            App::frontend()->context()->nb_entry_per_page = App::blog()->settings()->system->nb_post_per_feed;
            App::frontend()->context()->short_feed_items  = App::blog()->settings()->system->short_feed_items;
        }
        $tpl .= '.xml';

        if ($type === 'atom') {
            $mime = 'application/atom+xml';
        }

        App::frontend()->context()->feed_subtitle = $subtitle;

        header('X-Robots-Tag: ' . Ctx::robotsPolicy(App::blog()->settings()->system->robots_policy, ''));
        Http::$cache_max_age = 60 * 60; // 1 hour cache for feed
        self::serveDocument($tpl, $mime);
        if (!$comments && !$cat_url) {
            // Check if some entries must be published
            App::blog()->publishScheduledEntries();
        }
    }

    /**
     * Cope with incoming Trackbacks
     *
     * @param      null|string  $args   The arguments
     */
    public static function trackback(?string $args): void
    {
        if (!preg_match('/^\d+$/', (string) $args)) {
            // The specified trackback URL is not an number
            self::p404();
        } else {
            // Save locally post_id from args
            $post_id = (int) $args;

            $args = [
                'post_id' => $post_id,
                'type'    => 'trackback',
            ];

            # --BEHAVIOR-- publicBeforeReceiveTrackback -- string|null
            App::behavior()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

            App::trackback()->receiveTrackback($post_id);
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

        # --BEHAVIOR-- publicBeforeReceiveTrackback -- string|null
        App::behavior()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

        App::trackback()->receiveWebmention();
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
        $blog_id = (string) preg_replace('#^([^/]*).*#', '$1', (string) $args);
        (new XmlRpc($blog_id))->serve();
    }

    /**
     * Rick Roll script kiddies which try the administrative page wordpress URLs on the blog :-)
     *
     * https://example.com/wp-admin and https://example.com/wp-login
     *
     * @param      null|string  $args   The arguments
     */
    public static function wpfaker(?string $args): never
    {
        // Rick Roll script kiddies
        Http::redirect('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        exit;
    }
}
