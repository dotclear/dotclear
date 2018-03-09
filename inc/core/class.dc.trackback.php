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

if (!defined('DC_RC_PATH')) {return;}

class dcTrackback
{
    public $core;  ///< <b>dcCore</b> dcCore instance
    public $table; ///< <b>string</b> done pings table name

    /**
    Object constructor

    @param    core        <b>dcCore</b>        dcCore instance
     */
    public function __construct($core)
    {
        $this->core  = &$core;
        $this->con   = &$this->core->con;
        $this->table = $this->core->prefix . 'ping';
    }

    /// @name Send
    //@{
    /**
    Get all pings sent for a given post.

    @param    post_id    <b>integer</b>        Post ID
    @return    <b>record</b>
     */
    public function getPostPings($post_id)
    {
        $strReq = 'SELECT ping_url, ping_dt ' .
        'FROM ' . $this->table . ' ' .
        'WHERE post_id = ' . (integer) $post_id;

        return $this->con->select($strReq);
    }

    /**
    Sends a ping to given <var>$url</var>.

    @param    url            <b>string</b>        URL to ping
    @param    post_id        <b>integer</b>        Post ID
    @param    post_title    <b>string</b>        Post title
    @param    post_excerpt    <b>string</b>        Post excerpt
    @param    post_url        <b>string</b>        Post URL
     */
    public function ping($url, $post_id, $post_title, $post_excerpt, $post_url)
    {
        if ($this->core->blog === null) {
            return false;
        }

        $post_id = (integer) $post_id;

        # Check for previously done trackback
        $strReq = 'SELECT post_id, ping_url FROM ' . $this->table . ' ' .
        'WHERE post_id = ' . $post_id . ' ' .
        "AND ping_url = '" . $this->con->escape($url) . "' ";

        $rs = $this->con->select($strReq);

        if (!$rs->isEmpty()) {
            throw new Exception(sprintf(__('%s has still been pinged'), $url));
        }

        $ping_parts = explode('|', $url);
        # Maybe a webmention
        if (count($ping_parts) == 3) {
            $payload = http_build_query(array(
                'source' => $post_url,
                'target' => $ping_parts[1]
            ));

            try {
                $http = self::initHttp($ping_parts[0], $path);
                $http->setMoreHeader('Content-Type: application/x-www-form-urlencoded');
                $http->post($path, $payload, 'UTF-8');

                # Read response status
                $status     = $http->getStatus();
                $ping_error = '0';
            } catch (Exception $e) {
                throw new Exception(__('Unable to ping URL'));
            }

            if (!in_array($status, array('200', '201', '202'))) {
                $ping_error = $http->getStatus();
                $ping_msg   = __('Bad server response code');
            }
        }
        # No, let's walk by the trackback way
        elseif (count($ping_parts) < 2) {
            $data = array(
                'title'     => $post_title,
                'excerpt'   => $post_excerpt,
                'url'       => $post_url,
                'blog_name' => trim(html::escapeHTML(html::clean($this->core->blog->name)))
                //,'__debug' => false
            );

            # Ping
            try {
                $http = self::initHttp($url, $path);
                $http->post($path, $data, 'UTF-8');
                $res = $http->getContent();
            } catch (Exception $e) {
                throw new Exception(__('Unable to ping URL'));
            }

            $pattern =
                '|<response>.*<error>(.*)</error>(.*)' .
                '(<message>(.*)</message>(.*))?' .
                '</response>|msU';

            if (!preg_match($pattern, $res, $match)) {
                throw new Exception(sprintf(__('%s is not a ping URL'), $url));
            }

            $ping_error = trim($match[1]);
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
        } else {
            # Notify ping result in database
            $cur           = $this->con->openCursor($this->table);
            $cur->post_id  = $post_id;
            $cur->ping_url = $url;
            $cur->ping_dt  = date('Y-m-d H:i:s');

            $cur->insert();
        }
    }
    //@}

    /// @name Receive
    //@{
    /**
    Receives a trackback and insert it as a comment of given post.

    @param    post_id        <b>integer</b>        Post ID
     */
    public function receiveTrackback($post_id)
    {
        header('Content-Type: text/xml; charset=UTF-8');
        if (empty($_POST)) {
            http::head(405, 'Method Not Allowed');
            echo
                '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
                "<response>\n" .
                "  <error>1</error>\n" .
                "  <message>POST request needed</message>\n" .
                "</response>";
            return;
        }

        $post_id = (integer) $post_id;

        $title     = !empty($_POST['title']) ? $_POST['title'] : '';
        $excerpt   = !empty($_POST['excerpt']) ? $_POST['excerpt'] : '';
        $url       = !empty($_POST['url']) ? $_POST['url'] : '';
        $blog_name = !empty($_POST['blog_name']) ? $_POST['blog_name'] : '';
        $charset   = '';
        $comment   = '';

        $err = false;
        $msg = '';

        if ($this->core->blog === null) {
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
            $post = $this->core->blog->getPosts(array('post_id' => $post_id, 'post_type' => ''));

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
            $title = text::cutString($title, 60);

            $excerpt = trim(html::clean($excerpt));
            $excerpt = html::decodeEntities($excerpt);
            $excerpt = preg_replace('/\s+/ms', ' ', $excerpt);
            $excerpt = text::cutString($excerpt, 252);
            $excerpt = html::escapeHTML($excerpt) . '...';

            $blog_name = trim(html::clean($blog_name));
            $blog_name = html::decodeEntities($blog_name);
            $blog_name = html::escapeHTML($blog_name);
            $blog_name = text::cutString($blog_name, 60);

            try {
                $this->addBacklink($post_id, $url, $blog_name, $title, $excerpt, $comment);
            } catch (Exception $e) {
                $err = 1;
                $msg = 'Something went wrong : ' . $e->getMessage();
            }
        }

        $resp =
        '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
        "<response>\n" .
        '  <error>' . (integer) $err . "</error>\n";

        if ($msg) {
            $resp .= '  <message>' . $msg . "</message>\n";
        }

        if (!empty($_POST['__debug'])) {
            $resp .=
                "  <debug>\n" .
                '    <title>' . $title . "</title>\n" .
                '    <excerpt>' . $excerpt . "</excerpt>\n" .
                '    <url>' . $url . "</url>\n" .
                '    <blog_name>' . $blog_name . "</blog_name>\n" .
                '    <charset>' . $charset . "</charset>\n" .
                '    <comment>' . $comment . "</comment>\n" .
                "  </debug>\n";
        }

        echo $resp . "</response>";
    }

    /**
    Receives a pingback and insert it as a comment of given post.

    @param    from_url        <b>string</b>        Source URL
    @param    to_url            <b>string</b>        Target URL
     */
    public function receivePingback($from_url, $to_url)
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
            $title = text::cutString($title, 60);

            preg_match('!<body[^>]*?>(.*)?</body>!msi', $remote_content, $m);
            $source = $m[1];
            $source = preg_replace('![\r\n\s]+!ms', ' ', $source);
            $source = preg_replace("/<\/*(h\d|p|th|td|li|dt|dd|pre|caption|input|textarea|button)[^>]*>/", "\n\n", $source);
            $source = strip_tags($source, '<a>');
            $source = explode("\n\n", $source);

            $excerpt = '';
            foreach ($source as $line) {
                if (strpos($line, $to_url) !== false) {
                    if (preg_match("!<a[^>]+?" . $to_url . "[^>]*>([^>]+?)</a>!", $line, $m)) {
                        $excerpt = strip_tags($line);
                        break;
                    }
                }
            }
            if ($excerpt) {
                $excerpt = '(&#8230;) ' . text::cutString(html::escapeHTML($excerpt), 200) . ' (&#8230;)';
            } else {
                $excerpt = '(&#8230;)';
            }

            $this->addBacklink($posts->post_id, $from_url, '', $title, $excerpt, $comment);
        } catch (Exception $e) {
            throw new Exception(__('Sorry, an internal problem has occured.'), 0);
        }

        return __('Thanks, mate. It was a pleasure.');
    }

    /**
    Receives a webmention and insert it as a comment of given post.

    NB: plugin Fair Trackback check source content to find url.

    @return    <b>null</b>    Null on success, else throw an exception
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
            $title = text::cutString($title, 60);

            preg_match('!<body[^>]*?>(.*)?</body>!msi', $remote_content, $m);
            $source = $m[1];
            $source = preg_replace('![\r\n\s]+!ms', ' ', $source);
            $source = preg_replace("/<\/*(h\d|p|th|td|li|dt|dd|pre|caption|input|textarea|button)[^>]*>/", "\n\n", $source);
            $source = strip_tags($source, '<a>');
            $source = explode("\n\n", $source);

            $excerpt = '';
            foreach ($source as $line) {
                if (strpos($line, $to_url) !== false) {
                    if (preg_match("!<a[^>]+?" . $to_url . "[^>]*>([^>]+?)</a>!", $line, $m)) {
                        $excerpt = strip_tags($line);
                        break;
                    }
                }
            }
            if ($excerpt) {
                $excerpt = '(&#8230;) ' . text::cutString(html::escapeHTML($excerpt), 200) . ' (&#8230;)';
            } else {
                $excerpt = '(&#8230;)';
            }

            $this->addBacklink($post_id, $from_url, '', $title, $excerpt, $comment);

            # All done, thanks
            $code = $this->core->blog->settings->system->trackbacks_pub ? 200 : 202;
            http::head($code);
            return;
        } catch (Exception $e) {
            $err = $e->getMessage();
        }

        http::head(400);
        echo $err ?: 'Something went wrong.';
        return;
    }

    /**
    Check if a post previously received a ping a from an URL.

    @param    post_id    <b>integer</b>        Post ID
    @param    from_url    <b>string</b>        Source URL
    @return    <b>boolean</b>
     */
    private function pingAlreadyDone($post_id, $from_url)
    {
        $params = array(
            'post_id'           => $post_id,
            'comment_site'      => $from_url,
            'comment_trackback' => 1
        );

        $rs = $this->core->blog->getComments($params, true);
        if ($rs && !$rs->isEmpty()) {
            return ($rs->f(0));
        }

        return false;
    }

    /**
    Create a comment marked as trackback for a given post.

    @param    post_id    <b>integer</b>        Post ID
    @param    url        <b>string</b>        Discovered URL
    @param    blog name    <b>string</b>        Source blog name
    @param    title    <b>string</b>        Comment title
    @param    excerpt    <b>string</b>        Source excerpt
    @param    comment    <b>string</b>        Comment content
     */
    private function addBacklink($post_id, $url, $blog_name, $title, $excerpt, &$comment)
    {
        if (empty($blog_name)) {
            // Let use title as text link for this backlink
            $blog_name = ($title ?: 'Anonymous blog');
        }

        $comment =
            "<!-- TB -->\n" .
            '<p><strong>' . ($title ?: $blog_name) . "</strong></p>\n" .
            '<p>' . $excerpt . '</p>';

        $cur                    = $this->core->con->openCursor($this->core->prefix . 'comment');
        $cur->comment_author    = (string) $blog_name;
        $cur->comment_site      = (string) $url;
        $cur->comment_content   = (string) $comment;
        $cur->post_id           = $post_id;
        $cur->comment_trackback = 1;
        $cur->comment_status    = $this->core->blog->settings->system->trackbacks_pub ? 1 : -1;
        $cur->comment_ip        = http::realIP();

        # --BEHAVIOR-- publicBeforeTrackbackCreate
        $this->core->callBehavior('publicBeforeTrackbackCreate', $cur);
        if ($cur->post_id) {
            $comment_id = $this->core->blog->addComment($cur);

            # --BEHAVIOR-- publicAfterTrackbackCreate
            $this->core->callBehavior('publicAfterTrackbackCreate', $cur, $comment_id);
        }
    }

    /**
    Delete previously received comment made from an URL for a given post.

    @param    post_id    <b>integer</b>        Post ID
    @param    url        <b>string</b>        Source URL
     */
    private function delBacklink($post_id, $url)
    {
        $this->con->execute(
            'DELETE FROM ' . $this->core->prefix . 'comment ' .
            'WHERE post_id = ' . ((integer) $post_id) . ' ' .
            "AND comment_site = '" . $this->core->con->escape((string) $url) . "' " .
            'AND comment_trackback = 1 '
        );
    }

    /**
    Find Charset from HTTP headers.

    @param    header    <b>string</b>        Source header
    @return    <b>string</b>
     */
    private static function getCharsetFromRequest($header = '')
    {
        if (!$header && isset($_SERVER['CONTENT_TYPE'])) {
            $header = $_SERVER['CONTENT_TYPE'];
        }

        if ($header) {
            if (preg_match('|charset=([a-zA-Z0-9-]+)|', $header, $m)) {
                return $m[1];
            }
        }

        return;
    }

    /**
    Detect encoding.

    @param    content        <b>string</b>        Source URL
    @return    <b>string</b>
     */
    private static function detectCharset($content)
    {
        return mb_detect_encoding($content,
            'UTF-8,ISO-8859-1,ISO-8859-2,ISO-8859-3,' .
            'ISO-8859-4,ISO-8859-5,ISO-8859-6,ISO-8859-7,ISO-8859-8,' .
            'ISO-8859-9,ISO-8859-10,ISO-8859-13,ISO-8859-14,ISO-8859-15');
    }

    /**
    Retreive local post from a given URL

    @param    to_url        <b>string</b>        Target URL
    @return    <b>string</b>
     */
    private function getTargetPost($to_url)
    {
        $reg  = '!^' . preg_quote($this->core->blog->url) . '(.*)!';
        $type = $args = $next = '';

        # Are you dumb?
        if (!preg_match($reg, $to_url, $m)) {
            throw new Exception(__('Any chance you ping one of my contents? No? Really?'), 0);
        }

        # Does the targeted URL look like a registered post type?
        $url_part   = $m[1];
        $p_type     = '';
        $post_types = $this->core->getPostTypes();
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
        $params = array(
            'post_type' => $p_type,
            'post_url'  => $post_url
        );
        $posts = $this->core->blog->getPosts($params);

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
    Returns content of a distant page

    @param    from_url        <b>string</b>        Target URL
    @return    <b>string</b>
     */
    private function getRemoteContent($from_url)
    {
        $http = self::initHttp($from_url, $from_path);

        # First round : just to be sure the ping comes from an acceptable resource type.
        $http->setHeadersOnly(true);
        $http->get($from_path);
        $c_type = explode(';', $http->getHeader('content-type'));

        # Bad luck. Bye, bye...
        if (!in_array($c_type[0], array('text/html', 'application/xhtml+xml'))) {
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
    Returns an array containing all discovered trackbacks URLs in
    <var>$text</var>.

    @param    text        <b>string</b>        Input text
    @return    <b>array</b>
     */
    public function discover($text)
    {
        $res = array();

        foreach ($this->getTextLinks($text) as $link) {
            if (($url = $this->getPingURL($link)) !== null) {
                $res[] = $url;
            }
        }

        return $res;
    }

    /**
    Find links into a text.

    @param    text        <b>string</b>        Text to scan
    @return    <b>array</b>
     */
    private function getTextLinks($text)
    {
        $res = array();

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
    Check remote header/content to find api trace.

    @param    url        <b>string</b>        URL to scan
    @return    <b>string</b>
     */
    private function getPingURL($url)
    {
        if (strpos($url, '/') === 0) {
            $url = http::getHost() . $url;
        }

        try {
            $http = self::initHttp($url, $path);
            $http->get($path);
            $page_content = $http->getContent();
            $pb_url       = $http->getHeader('x-pingback');
            $wm_url       = $http->getHeader('link');
        } catch (Exception $e) {
            return false;
        }

        # Let's check for an elderly trackback data chunk...
        $pattern_rdf =
            '/<rdf:RDF.*?>.*?' .
            '<rdf:Description\s+(.*?)\/>' .
            '.*?<\/rdf:RDF>' .
            '/msi';

        preg_match_all($pattern_rdf, $page_content, $rdf_all, PREG_SET_ORDER);

        $url_path      = parse_url($url, PHP_URL_PATH);
        $sanitized_url = str_replace($url_path, html::sanitizeURL($url_path), $url);

        for ($i = 0; $i < count($rdf_all); $i++) {
            $rdf = $rdf_all[$i][1];
            if (preg_match('/dc:identifier="' . preg_quote($url, '/') . '"/msi', $rdf) ||
                preg_match('/dc:identifier="' . preg_quote($sanitized_url, '/') . '"/msi', $rdf)) {
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
            if (!in_array($type[0], array('text/html', 'application/xhtml+xml'))) {
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

        return;
    }
    //@}

    /**
    HTTP helper.

    @param    url        <b>string</b>        URL
    @param    path        <b>string</b>        Path
    @return    <b>object</b>
     */
    private static function initHttp($url, &$path)
    {
        $client = netHttp::initClient($url, $path);
        $client->setTimeout(5);
        $client->setUserAgent('Dotclear - http://www.dotclear.org/');
        $client->useGzip(false);
        $client->setPersistReferers(false);

        return $client;
    }

    /**
    URL helper.

    @param    from_url        <b>string</b>        URL a
    @param    to_url        <b>string</b>        URL b
     */
    public static function checkURLs($from_url, $to_url)
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
