<?php
/**
 * @brief Trackbacks/Pingbacks sender and server
 *
 * Sends and receives trackbacks/pingbacks.
 * Also handles trackbacks/pingbacks auto discovery.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\Text;

class dcTrackback
{
    // Constants

    /**
     * Trackbacks table name
     *
     * @var        string
     */
    public const PING_TABLE_NAME = 'ping';

    /**
     * Pings table name
     */
    public $table;

    /**
     * Object constructor
     */
    public function __construct()
    {
        $this->table = dcCore::app()->prefix . self::PING_TABLE_NAME;
    }

    /// @name Send
    //@{
    /**
     * Get all pings sent for a given post.
     *
     * @param      integer  $post_id  The post identifier
     *
     * @return     dcRecord   The post pings.
     */
    public function getPostPings(int $post_id)
    {
        $strReq = 'SELECT ping_url, ping_dt ' .
        'FROM ' . $this->table . ' ' .
        'WHERE post_id = ' . (int) $post_id;

        return new dcRecord(dcCore::app()->con->select($strReq));
    }

    /**
     * Sends a ping to given <var>$url</var>.
     *
     * @param      string     $url           The url
     * @param      int        $post_id       The post identifier
     * @param      string     $post_title    The post title
     * @param      string     $post_excerpt  The post excerpt
     * @param      string     $post_url      The post url
     *
     * @throws     Exception
     *
     * @return     mixed    false if error
     */
    public function ping(string $url, int $post_id, string $post_title, string $post_excerpt, string $post_url)
    {
        if (dcCore::app()->blog === null) {
            return false;
        }

        # Check for previously done trackback
        $strReq = 'SELECT post_id, ping_url FROM ' . $this->table . ' ' .
        'WHERE post_id = ' . $post_id . ' ' .
        "AND ping_url = '" . dcCore::app()->con->escape($url) . "' ";

        $rs = new dcRecord(dcCore::app()->con->select($strReq));

        if (!$rs->isEmpty()) {
            throw new Exception(sprintf(__('%s has still been pinged'), $url));
        }

        $ping_parts = explode('|', $url);
        $ping_msg   = '';

        # Maybe a webmention
        if (count($ping_parts) == 3) {
            $payload = http_build_query([
                'source' => $post_url,
                'target' => $ping_parts[1],
            ]);

            try {
                $path = '';
                $http = self::initHttp($ping_parts[0], $path);
                $http->setMoreHeader('Content-Type: application/x-www-form-urlencoded');
                $http->post($path, $payload, 'UTF-8');

                # Read response status
                $status     = $http->getStatus();
                $ping_error = '0';
            } catch (Exception $e) {
                throw new Exception(__('Unable to ping URL'));
            }

            if (!in_array($status, ['200', '201', '202'])) {
                $ping_error = $http->getStatus();
                $ping_msg   = __('Bad server response code');
            }
        }
        # No, let's walk by the trackback way
        elseif (count($ping_parts) < 2) {
            $data = [
                'title'     => $post_title,
                'excerpt'   => $post_excerpt,
                'url'       => $post_url,
                'blog_name' => trim(html::escapeHTML(html::clean(dcCore::app()->blog->name))),
                //,'__debug' => false
            ];

            # Ping
            try {
                $path = '';
                $http = self::initHttp($url, $path);
                $http->post($path, $data, 'UTF-8');
                $res = $http->getContent();
            } catch (Exception $e) {
                throw new Exception(__('Unable to ping URL'));
            }

            $pattern = '|<response>.*<error>(.*)</error>(.*)' .
                '(<message>(.*)</message>(.*))?' .
                '</response>|msU';

            if (!preg_match($pattern, $res, $match)) {
                throw new Exception(sprintf(__('%s is not a ping URL'), $url));
            }

            $ping_error = trim((string) $match[1]);
            $ping_msg   = (!empty($match[4])) ? $match[4] : '';
        }
        # Damnit ! Let's play pingback
        else {
            try {
                $xmlrpc     = new xmlrpcClient($ping_parts[0]);
                $res        = $xmlrpc->query('pingback.ping', $post_url, $ping_parts[1]);
                $ping_error = '0';
            } catch (xmlrpcException $e) {
                $ping_error = $e->getCode();
                $ping_msg   = $e->getMessage();
            } catch (Exception $e) {
                throw new Exception(__('Unable to ping URL'));
            }
        }

        if ($ping_error != '0') {
            throw new Exception(sprintf(__('%s, ping error:'), $url) . ' ' . $ping_msg);
        }
        # Notify ping result in database
        $cur           = dcCore::app()->con->openCursor($this->table);
        $cur->post_id  = $post_id;
        $cur->ping_url = $url;
        $cur->ping_dt  = date('Y-m-d H:i:s');

        $cur->insert();
    }
    //@}

    /// @name Receive
    //@{
    /**
     * Receives a trackback and insert it as a comment of given post.
     *
     * @param      int      $post_id  The post identifier
     */
    public function receiveTrackback(int $post_id)
    {
        header('Content-Type: text/xml; charset=UTF-8');
        if (empty($_POST)) {
            http::head(405, 'Method Not Allowed');
            echo
                '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
                "<response>\n" .
                "  <error>1</error>\n" .
                "  <message>POST request needed</message>\n" .
                '</response>';

            return;
        }

        $post_id = (int) $post_id;

        $title     = !empty($_POST['title']) ? $_POST['title'] : '';
        $excerpt   = !empty($_POST['excerpt']) ? $_POST['excerpt'] : '';
        $url       = !empty($_POST['url']) ? $_POST['url'] : '';
        $blog_name = !empty($_POST['blog_name']) ? $_POST['blog_name'] : '';
        $charset   = '';
        $comment   = '';

        $err = false;
        $msg = '';

        if (dcCore::app()->blog === null) {
            $err = true;
            $msg = 'No blog.';
        } elseif ($url == '') {
            $err = true;
            $msg = 'URL parameter is required.';
        } elseif ($blog_name == '') {
            $err = true;
            $msg = 'Blog name is required.';
        }

        if (!$err) {
            $post = dcCore::app()->blog->getPosts(['post_id' => $post_id, 'post_type' => '']);

            if ($post->isEmpty()) {
                $err = true;
                $msg = 'No such post.';
            } elseif (!$post->trackbacksActive()) {
                $err = true;
                $msg = 'Trackbacks are not allowed for this post or weblog.';
            }

            $url = trim(html::clean($url));
            if ($this->pingAlreadyDone($post->post_id, $url)) {
                $err = true;
                $msg = 'The trackback has already been registered';
            }
        }

        if (!$err) {
            $charset = self::getCharsetFromRequest();

            if (!$charset) {
                $charset = self::detectCharset($title . ' ' . $excerpt . ' ' . $blog_name);
            }

            if (strtolower($charset) != 'utf-8') {
                $title     = iconv($charset, 'UTF-8', $title);
                $excerpt   = iconv($charset, 'UTF-8', $excerpt);
                $blog_name = iconv($charset, 'UTF-8', $blog_name);
            }

            $title = trim(html::clean($title));
            $title = html::decodeEntities($title);
            $title = html::escapeHTML($title);
            $title = Text::cutString($title, 60);

            $excerpt = trim(html::clean($excerpt));
            $excerpt = html::decodeEntities($excerpt);
            $excerpt = preg_replace('/\s+/ms', ' ', $excerpt);
            $excerpt = Text::cutString($excerpt, 252);
            $excerpt = html::escapeHTML($excerpt) . '...';

            $blog_name = trim(html::clean($blog_name));
            $blog_name = html::decodeEntities($blog_name);
            $blog_name = html::escapeHTML($blog_name);
            $blog_name = Text::cutString($blog_name, 60);

            try {
                $this->addBacklink($post_id, $url, $blog_name, $title, $excerpt, $comment);
            } catch (Exception $e) {
                $err = 1;
                $msg = 'Something went wrong : ' . $e->getMessage();
            }
        }

        $resp = '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
        "<response>\n" .
        '  <error>' . (int) $err . "</error>\n";

        if ($msg) {
            $resp .= '  <message>' . $msg . "</message>\n";
        }

        if (!empty($_POST['__debug'])) {
            $resp .= "  <debug>\n" .
                '    <title>' . $title . "</title>\n" .
                '    <excerpt>' . $excerpt . "</excerpt>\n" .
                '    <url>' . $url . "</url>\n" .
                '    <blog_name>' . $blog_name . "</blog_name>\n" .
                '    <charset>' . $charset . "</charset>\n" .
                '    <comment>' . $comment . "</comment>\n" .
                "  </debug>\n";
        }

        echo $resp . '</response>';
    }

    /**
     * Receives a pingback and insert it as a comment of given post.
     *
     * @param      string     $from_url  Source URL
     * @param      string     $to_url    Target URL
     *
     * @throws     Exception
     *
     * @return     string
     */
    public function receivePingback(string $from_url, string $to_url): string
    {
        try {
            $posts = $this->getTargetPost($to_url);

            if ($this->pingAlreadyDone($posts->post_id, $from_url)) {
                throw new Exception(__('Don\'t repeat yourself, please.'), 48);
            }

            $remote_content = $this->getRemoteContent($from_url);

            # We want a title...
            if (!preg_match('!<title>([^<].*?)</title>!mis', $remote_content, $m)) {
                throw new Exception(__('Where\'s your title?'), 0);
            }
            $title = trim(html::clean($m[1]));
            $title = html::decodeEntities($title);
            $title = html::escapeHTML($title);
            $title = Text::cutString($title, 60);

            $blog_name = $this->getSourceName($remote_content);

            preg_match('!<body[^>]*?>(.*)?</body>!msi', $remote_content, $m);
            $source = $m[1];
            $source = preg_replace('![\r\n\s]+!ms', ' ', $source);
            $source = preg_replace("/<\/*(h\d|p|th|td|li|dt|dd|pre|caption|input|textarea|button)[^>]*>/", "\n\n", $source);
            $source = strip_tags($source, '<a>');
            $source = explode("\n\n", $source);

            $excerpt = '';
            foreach ($source as $line) {
                if (strpos($line, $to_url) !== false) {
                    if (preg_match('!<a[^>]+?' . $to_url . '[^>]*>([^>]+?)</a>!', $line, $m)) {
                        $excerpt = strip_tags($line);

                        break;
                    }
                }
            }
            if ($excerpt) {
                $excerpt = '(&#8230;) ' . Text::cutString(html::escapeHTML($excerpt), 200) . ' (&#8230;)';
            } else {
                $excerpt = '(&#8230;)';
            }

            $comment = '';
            $this->addBacklink($posts->post_id, $from_url, $blog_name, $title, $excerpt, $comment);
        } catch (Exception $e) {
            throw new Exception(__('Sorry, an internal problem has occured.'), 0);
        }

        return __('Thanks, mate. It was a pleasure.');
    }

    /**
     * Receives a webmention and insert it as a comment of given post.
     *
     * NB: plugin Fair Trackback check source content to find url.
     *
     * @throws     Exception
     */
    public function receiveWebmention()
    {
        $err = $post_id = false;
        header('Content-Type: text/html; charset=UTF-8');

        try {
            # Check if post and target are valid URL
            if (empty($_POST['source']) || empty($_POST['target'])) {
                throw new Exception('Source or target is not valid', 0);
            }

            $from_url = urldecode($_POST['source']);
            $to_url   = urldecode($_POST['target']);

            self::checkURLs($from_url, $to_url);

            # Try to find post
            $posts   = $this->getTargetPost($to_url);
            $post_id = $posts->post_id;

            # Check if it's an updated mention
            if ($this->pingAlreadyDone($post_id, $from_url)) {
                $this->delBacklink($post_id, $from_url);
            }

            # Create a comment for received webmention
            $remote_content = $this->getRemoteContent($from_url);

            # We want a title...
            if (!preg_match('!<title>([^<].*?)</title>!mis', $remote_content, $m)) {
                throw new Exception(__('Where\'s your title?'), 0);
            }
            $title = trim(html::clean($m[1]));
            $title = html::decodeEntities($title);
            $title = html::escapeHTML($title);
            $title = Text::cutString($title, 60);

            $blog_name = $this->getSourceName($remote_content);

            preg_match('!<body[^>]*?>(.*)?</body>!msi', $remote_content, $m);
            $source = $m[1];
            $source = preg_replace('![\r\n\s]+!ms', ' ', $source);
            $source = preg_replace("/<\/*(h\d|p|th|td|li|dt|dd|pre|caption|input|textarea|button)[^>]*>/", "\n\n", $source);
            $source = strip_tags($source, '<a>');
            $source = explode("\n\n", $source);

            $excerpt = '';
            foreach ($source as $line) {
                if (strpos($line, $to_url) !== false) {
                    if (preg_match('!<a[^>]+?' . $to_url . '[^>]*>([^>]+?)</a>!', $line, $m)) {
                        $excerpt = strip_tags($line);

                        break;
                    }
                }
            }
            if ($excerpt) {
                $excerpt = '(&#8230;) ' . Text::cutString(html::escapeHTML($excerpt), 200) . ' (&#8230;)';
            } else {
                $excerpt = '(&#8230;)';
            }

            $comment = '';
            $this->addBacklink($post_id, $from_url, $blog_name, $title, $excerpt, $comment);

            # All done, thanks
            $code = dcCore::app()->blog->settings->system->trackbacks_pub ? 200 : 202;
            http::head($code);

            return;
        } catch (Exception $e) {
            $err = $e->getMessage();
        }

        http::head(400);
        echo $err ?: 'Something went wrong.';
    }

    /**
     * Check if a post previously received a ping a from an URL.
     *
     * @param      int      $post_id   The post identifier
     * @param      string   $from_url  The from url
     *
     * @return     bool
     */
    private function pingAlreadyDone(int $post_id, string $from_url): bool
    {
        $params = [
            'post_id'           => $post_id,
            'comment_site'      => $from_url,
            'comment_trackback' => 1,
        ];

        $rs = dcCore::app()->blog->getComments($params, true);
        if (!$rs->isEmpty()) {
            return ($rs->f(0));
        }

        return false;
    }

    /**
     * Create a comment marked as trackback for a given post.
     *
     * @param      int      $post_id    The post identifier
     * @param      string   $url        The url
     * @param      string   $blog_name  The blog name
     * @param      string   $title      The title
     * @param      string   $excerpt    The excerpt
     * @param      string   $comment    The comment
     */
    private function addBacklink(int $post_id, string $url, string $blog_name, string $title, string $excerpt, string &$comment)
    {
        if (empty($blog_name)) {
            // Let use title as text link for this backlink
            $blog_name = ($title ?: 'Anonymous blog');
        }

        $comment = "<!-- TB -->\n" .
            '<p><strong>' . ($title ?: $blog_name) . "</strong></p>\n" .
            '<p>' . $excerpt . '</p>';

        $cur                    = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);
        $cur->comment_author    = (string) $blog_name;
        $cur->comment_site      = (string) $url;
        $cur->comment_content   = (string) $comment;
        $cur->post_id           = $post_id;
        $cur->comment_trackback = 1;
        $cur->comment_status    = dcCore::app()->blog->settings->system->trackbacks_pub ? dcBlog::COMMENT_PUBLISHED : dcBlog::COMMENT_PENDING;
        $cur->comment_ip        = http::realIP();

        # --BEHAVIOR-- publicBeforeTrackbackCreate
        dcCore::app()->callBehavior('publicBeforeTrackbackCreate', $cur);
        if ($cur->post_id) {
            $comment_id = dcCore::app()->blog->addComment($cur);

            # --BEHAVIOR-- publicAfterTrackbackCreate
            dcCore::app()->callBehavior('publicAfterTrackbackCreate', $cur, $comment_id);
        }
    }

    /**
     * Delete previously received comment made from an URL for a given post.
     *
     * @param      int      $post_id  The post identifier
     * @param      string   $url      The url
     */
    private function delBacklink(int $post_id, string $url)
    {
        dcCore::app()->con->execute(
            'DELETE FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' ' .
            'WHERE post_id = ' . ((int) $post_id) . ' ' .
            "AND comment_site = '" . dcCore::app()->con->escape((string) $url) . "' " .
            'AND comment_trackback = 1 '
        );
    }

    /**
     * Gets the charset from HTTP headers.
     *
     * @param      string  $header  The header
     *
     * @return     mixed   The charset from request.
     */
    private static function getCharsetFromRequest(string $header = '')
    {
        if (!$header && isset($_SERVER['CONTENT_TYPE'])) {
            $header = $_SERVER['CONTENT_TYPE'];
        }

        if ($header) {
            if (preg_match('|charset=([a-zA-Z0-9-]+)|', $header, $m)) {
                return $m[1];
            }
        }
    }

    /**
     * Detect encoding.
     *
     * @param      string  $content  The content
     *
     * @return     string
     */
    private static function detectCharset(string $content): string
    {
        return mb_detect_encoding(
            $content,
            'UTF-8,ISO-8859-1,ISO-8859-2,ISO-8859-3,' .
            'ISO-8859-4,ISO-8859-5,ISO-8859-6,ISO-8859-7,ISO-8859-8,' .
            'ISO-8859-9,ISO-8859-10,ISO-8859-13,ISO-8859-14,ISO-8859-15'
        );
    }

    /**
     * Retrieve local post from a given URL.
     *
     * @param      string     $to_url  To url
     *
     * @throws     Exception
     *
     * @return     dcRecord     The target post.
     */
    private function getTargetPost(string $to_url): dcRecord
    {
        $reg = '!^' . preg_quote(dcCore::app()->blog->url) . '(.*)!';

        # Are you dumb?
        if (!preg_match($reg, $to_url, $m)) {
            throw new Exception(__('Any chance you ping one of my contents? No? Really?'), 0);
        }

        # Does the targeted URL look like a registered post type?
        $url_part   = $m[1];
        $p_type     = '';
        $post_types = dcCore::app()->getPostTypes();
        $post_url   = '';
        foreach ($post_types as $k => $v) {
            $reg = '!^' . preg_quote(str_replace('%s', '', $v['public_url'])) . '(.*)!';
            if (preg_match($reg, $url_part, $n)) {
                $p_type   = $k;
                $post_url = $n[1];

                break;
            }
        }

        if (empty($p_type)) {
            throw new Exception(__('Sorry but you can not ping this type of content.'), 33);
        }

        # Time to see if we've got a winner...
        $params = [
            'post_type' => $p_type,
            'post_url'  => $post_url,
        ];
        $posts = dcCore::app()->blog->getPosts($params);

        # Missed!
        if ($posts->isEmpty()) {
            throw new Exception(__('Oops. Kinda "not found" stuff. Please check the target URL twice.'), 33);
        }

        # Nice try. But, sorry, no.
        if (!$posts->trackbacksActive()) {
            throw new Exception(__('Sorry, dude. This entry does not accept pingback at the moment.'), 33);
        }

        return $posts;
    }

    /**
     * Returns content of a distant page.
     *
     * @param      string     $from_url  Target URL
     *
     * @throws     Exception
     *
     * @return     string
     */
    private function getRemoteContent(string $from_url): string
    {
        $from_path = '';
        $http      = self::initHttp($from_url, $from_path);

        # First round : just to be sure the ping comes from an acceptable resource type.
        $http->setHeadersOnly(true);
        $http->get($from_path);
        $c_type = explode(';', $http->getHeader('content-type'));

        # Bad luck. Bye, bye...
        if (!in_array($c_type[0], ['text/html', 'application/xhtml+xml'])) {
            throw new Exception(__('Your source URL does not look like a supported content type. Sorry. Bye, bye!'), 0);
        }

        # Second round : let's go fetch and parse the remote content
        $http->setHeadersOnly(false);
        $http->get($from_path);
        $remote_content = $http->getContent();

        # Convert content charset
        $charset = self::getCharsetFromRequest($http->getHeader('content-type'));
        if (!$charset) {
            $charset = self::detectCharset($remote_content);
        }
        if (strtolower($charset) != 'utf-8') {
            $remote_content = iconv($charset, 'UTF-8', $remote_content);
        }

        return $remote_content;
    }
    //@}

    /// @name Discover
    //@{
    /**
     * Returns an array containing all discovered trackbacks URLs in <var>$text</var>.
     *
     * @param      string  $text   The text
     *
     * @return     array
     */
    public function discover(string $text): array
    {
        $res = [];

        foreach ($this->getTextLinks($text) as $link) {
            if (($url = $this->getPingURL($link)) !== null) {
                $res[] = $url;
            }
        }

        return $res;
    }

    /**
     * Try to find source blog name or author from remote HTML page content
     * Used when receive a webmention or a pingback
     *
     * @param      string  $content  The content
     *
     * @return  string
     */
    private function getSourceName(string $content): string
    {
        // Clean text utility function
        $clean = fn ($text, $size = 255) => Text::cutString(html::escapeHTML(html::decodeEntities(html::clean(trim($text)))), $size);

        // First step: look for site name
        // ------------------------------

        // Try to find social media metadata
        // Facebook
        if (preg_match('!<meta\sproperty="og:site_name"\scontent="([^<].*?)"\s?\/?>!msi', $content, $m)) {
            return $clean($m[1]);
        }

        // Second step: look for author
        // ----------------------------

        // Try to find social media metadata
        // Twitter
        if (preg_match('!<meta\sname="twitter:site"\scontent="([^<].*?)"\s?\/?>!msi', $content, $m)) {
            return $clean($m[1]);
        }

        // Try to find a <meta name="author" content="???" />
        if (preg_match('!<meta\sname="author"\scontent="([^<].*?)"\s?\/?>!msi', $content, $m)) {
            return $clean($m[1]);
        }

        return '';
    }

    /**
     * Find links into a text.
     *
     * @param      string  $text   The text
     *
     * @return     array  The text links.
     */
    private function getTextLinks(string $text): array
    {
        $res = [];

        # href attribute on "a" tags
        if (preg_match_all('/<a ([^>]+)>/ms', $text, $match, PREG_SET_ORDER)) {
            for ($i = 0; $i < count($match); $i++) {
                if (preg_match('/href="((https?:\/)?\/[^"]+)"/ms', $match[$i][1], $matches)) {
                    $res[$matches[1]] = 1;
                }
            }
        }
        unset($match);

        # cite attributes on "blockquote" and "q" tags
        if (preg_match_all('/<(blockquote|q) ([^>]+)>/ms', $text, $match, PREG_SET_ORDER)) {
            for ($i = 0; $i < count($match); $i++) {
                if (preg_match('/cite="((https?:\/)?\/[^"]+)"/ms', $match[$i][2], $matches)) {
                    $res[$matches[1]] = 1;
                }
            }
        }

        return array_keys($res);
    }

    /**
     * Check remote header/content to find API trace.
     *
     * @param      string  $url    The url
     *
     * @return     mixed   The ping url.
     */
    private function getPingURL(string $url)
    {
        if (strpos($url, '/') === 0) {
            $url = http::getHost() . $url;
        }

        try {
            $path = '';
            $http = self::initHttp($url, $path);
            $http->get($path);
            $page_content = $http->getContent();
            $pb_url       = $http->getHeader('x-pingback');
            $wm_url       = $http->getHeader('link');
        } catch (Exception $e) {
            return false;
        }

        # Let's check for an elderly trackback data chunk...
        $pattern_rdf = '/<rdf:RDF.*?>.*?' .
            '<rdf:Description\s+(.*?)\/>' .
            '.*?<\/rdf:RDF>' .
            '/msi';

        preg_match_all($pattern_rdf, $page_content, $rdf_all, PREG_SET_ORDER);

        $url_path      = parse_url($url, PHP_URL_PATH);
        $sanitized_url = str_replace($url_path, html::sanitizeURL($url_path), $url);

        for ($i = 0; $i < count($rdf_all); $i++) {
            $rdf = $rdf_all[$i][1];
            if (preg_match('/dc:identifier="' . preg_quote($url, '/') . '"/msi', $rdf) || preg_match('/dc:identifier="' . preg_quote($sanitized_url, '/') . '"/msi', $rdf)) {
                if (preg_match('/trackback:ping="(.*?)"/msi', $rdf, $tb_link)) {
                    return $tb_link[1];
                }
            }
        }

        # No trackback ? OK, let see if we've got a X-Pingback header and it's a valid URL, it will be enough
        if ($pb_url && filter_var($pb_url, FILTER_VALIDATE_URL) && preg_match('!^https?:!', $pb_url)) {
            return $pb_url . '|' . $url;
        }

        # No X-Pingback header. A link rel=pingback, maybe ?
        $pattern_pingback = '!<link rel="pingback" href="(.*?)"( /)?>!msi';

        if (preg_match($pattern_pingback, $page_content, $m)) {
            $pb_url = $m[1];
            if (filter_var($pb_url, FILTER_VALIDATE_URL) && preg_match('!^https?:!', $pb_url)) {
                return $pb_url . '|' . $url;
            }
        }

        # Nothing, let's try webmention. Only support x/html content
        if ($wm_url) {
            $type = explode(';', $http->getHeader('content-type'));
            if (!in_array($type[0], ['text/html', 'application/xhtml+xml'])) {
                $wm_url = false;
            }
        }

        # Check HTTP headers for a Link: <ENDPOINT_URL>; rel="webmention"
        $wm_api = false;
        if ($wm_url) {
            if (preg_match('~<((?:https?://)?[^>]+)>; rel="?(?:https?://webmention.org/?|webmention)"?~', $wm_url, $match)) {
                if (filter_var($match[1], FILTER_VALIDATE_URL) && preg_match('!^https?:!', $match[1])) {
                    $wm_api = $match[1];
                }
            }
        }

        # Else check content for <link href="ENDPOINT_URL" rel="webmention" />
        if ($wm_url && !$wm_api) {
            $content = preg_replace('/<!--(.*)-->/Us', '', $page_content);
            if (preg_match('/<(?:link|a)[ ]+href="([^"]*)"[ ]+rel="[^" ]* ?webmention ?[^" ]*"[ ]*\/?>/i', $content, $match)
                || preg_match('/<(?:link|a)[ ]+rel="[^" ]* ?webmention ?[^" ]*"[ ]+href="([^"]*)"[ ]*\/?>/i', $content, $match)) {
                $wm_api = $match[1];
            }
        }

        # We have a winner, let's add some tricks to make diference
        if ($wm_api) {
            return $wm_api . '|' . $url . '|webmention';
        }
    }
    //@}

    /**
     * HTTP helper.
     *
     * @param      string  $url    The url
     * @param      string  $path   The path
     *
     * @return     false|netHttp
     */
    private static function initHttp(string $url, string &$path)
    {
        $client = netHttp::initClient($url, $path);
        $client->setTimeout(DC_QUERY_TIMEOUT);
        $client->setUserAgent('Dotclear - https://dotclear.org/');
        $client->useGzip(false);
        $client->setPersistReferers(false);

        return $client;
    }

    /**
     * URL helper.
     *
     * @param      string     $from_url  The from url
     * @param      string     $to_url    To url
     *
     * @throws     Exception
     */
    public static function checkURLs(string $from_url, string $to_url): void
    {
        if (!(filter_var($from_url, FILTER_VALIDATE_URL) && preg_match('!^https?://!', $from_url))) {
            throw new Exception(__('No valid source URL provided? Try again!'), 0);
        }

        if (!(filter_var($to_url, FILTER_VALIDATE_URL) && preg_match('!^https?://!', $to_url))) {
            throw new Exception(__('No valid target URL provided? Try again!'), 0);
        }

        if (html::sanitizeURL(urldecode($from_url)) == html::sanitizeURL(urldecode($to_url))) {
            throw new Exception(__('LOL!'), 0);
        }
    }
}
