<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\HttpClient;
use Dotclear\Helper\Network\XmlRpc\Client;
use Dotclear\Helper\Network\XmlRpc\XmlRpcException;
use Dotclear\Helper\Text;
use Dotclear\Exception\BadRequestException;
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\PostTypesInterface;
use Dotclear\Interface\Core\TrackbackInterface;
use Throwable;

/**
 * @brief   Trackbacks/Pingbacks sender and server.
 *
 * Sends and receives trackbacks/pingbacks.
 * Also handles trackbacks/pingbacks auto discovery.
 *
 * @todo    Use SqlStatement in Trackaback class
 *
 * @since   2.28, container services have been added to constructor
 */
class Trackback implements TrackbackInterface
{
    /**
     * Pings table name.
     *
     * @var    string   $table
     */
    public $table;

    /**
     * The query timeout.
     *
     * @var     int     $query_timeout
     */
    protected static int $query_timeout = 4;

    /**
     * Constructor.
     *
     * @param   BehaviorInterface       $behavior       The behavior instance
     * @param   BlogInterface           $blog           The blog instance
     * @param   ConfigInterface         $config         The application configuration
     * @param   ConnectionInterface     $con            The database connection instance
     * @param   PostTypesInterface      $post_types     The post types handler
     */
    public function __construct(
        protected BehaviorInterface $behavior,
        protected BlogInterface $blog,
        protected ConfigInterface $config,
        protected ConnectionInterface $con,
        protected PostTypesInterface $post_types,
    ) {
        $this->table         = $this->con->prefix() . self::PING_TABLE_NAME;
        self::$query_timeout = $config->queryTimeout();
    }

    public function openTrackbackCursor(): Cursor
    {
        return $this->con->openCursor($this->con->prefix() . self::PING_TABLE_NAME);
    }

    /// @name Send
    //@{
    public function getPostPings(int $post_id): MetaRecord
    {
        $sql = new SelectStatement();

        return $sql
            ->columns([
                'ping_url',
                'ping_dt',
            ])
            ->from($this->table)
            ->where('post_id = ' . (string) $post_id)
            ->select() ?? MetaRecord::newFromArray([]);
    }

    public function ping(string $url, int $post_id, string $post_title, string $post_excerpt, string $post_url): bool
    {
        if (!$this->blog->isDefined()) {
            return false;
        }

        # Check for previously done trackback
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns([
                'post_id',
                'ping_url',
            ])
            ->from($this->table)
            ->where('post_id = ' . (string) $post_id)
            ->and('ping_url = ' . $sql->quote($url))
            ->select();

        if ($rs && !$rs->isEmpty()) {
            throw new BadRequestException(sprintf(__('%s has still been pinged'), $url));
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
                if ($http === false) {
                    throw new BadRequestException(__('Unable to ping URL'));
                }
                $http->setMoreHeader('Content-Type: application/x-www-form-urlencoded');
                $http->post($path, $payload, 'UTF-8');

                # Read response status
                $status     = $http->getStatus();
                $ping_error = '0';
            } catch (Throwable) {
                throw new BadRequestException(__('Unable to ping URL'));
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
                'blog_name' => trim(Html::escapeHTML(Html::clean($this->blog->name()))),
                //,'__debug' => false
            ];

            # Ping
            $res = '';

            try {
                $path = '';
                $http = self::initHttp($url, $path);
                if ($http === false) {
                    throw new BadRequestException(__('Unable to ping URL'));
                }
                $http->post($path, $data, 'UTF-8');
                $res = $http->getContent();
            } catch (Throwable) {
                throw new BadRequestException(__('Unable to ping URL'));
            }

            $pattern = '|<response>.*<error>(.*)</error>(.*)' .
                '(<message>(.*)</message>(.*))?' .
                '</response>|msU';

            if (!preg_match($pattern, $res, $match)) {
                throw new BadRequestException(sprintf(__('%s is not a ping URL'), $url));
            }

            $ping_error = trim($match[1]);
            $ping_msg   = (!empty($match[4])) ? $match[4] : '';
        }
        # Damnit ! Let's play pingback
        else {
            try {
                $xmlrpc     = new Client($ping_parts[0]);
                $res        = $xmlrpc->query('pingback.ping', $post_url, $ping_parts[1]);
                $ping_error = '0';
            } catch (XmlRpcException $e) {
                $ping_error = $e->getCode();
                $ping_msg   = $e->getMessage();
            } catch (Throwable) {
                throw new BadRequestException(__('Unable to ping URL'));
            }
        }

        if ($ping_error != '0') {
            throw new BadRequestException(sprintf(__('%s, ping error:'), $url) . ' ' . $ping_msg);
        }
        # Notify ping result in database
        $cur           = $this->openTrackbackCursor();
        $cur->post_id  = $post_id;
        $cur->ping_url = $url;
        $cur->ping_dt  = date('Y-m-d H:i:s');

        $cur->insert();

        return true;
    }
    //@}

    /// @name Receive
    //@{
    public function receiveTrackback(int $post_id): void
    {
        header('Content-Type: text/xml; charset=UTF-8');
        if (empty($_POST)) {
            Http::head(405, 'Method Not Allowed');
            echo
                '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
                "<response>\n" .
                "  <error>1</error>\n" .
                "  <message>POST request needed</message>\n" .
                '</response>';

            return;
        }

        $title     = !empty($_POST['title']) ? $_POST['title'] : '';
        $excerpt   = !empty($_POST['excerpt']) ? $_POST['excerpt'] : '';
        $url       = !empty($_POST['url']) ? $_POST['url'] : '';
        $blog_name = !empty($_POST['blog_name']) ? $_POST['blog_name'] : '';
        $charset   = '';
        $comment   = '';

        $err = false;
        $msg = '';

        if (!$this->blog->isDefined()) {
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
            $post = $this->blog->getPosts(['post_id' => $post_id, 'post_type' => '']);

            if ($post->isEmpty()) {
                $err = true;
                $msg = 'No such post.';
            } elseif (!$post->trackbacksActive()) {
                $err = true;
                $msg = 'Trackbacks are not allowed for this post or weblog.';
            }

            $url = trim(Html::clean($url));
            if ($this->pingAlreadyDone((int) $post->post_id, $url)) {
                $err = true;
                $msg = 'The trackback has already been registered';
            }
        }

        if (!$err) {
            $charset = self::getCharsetFromRequest();

            if (!$charset) {
                $charset = self::detectCharset($title . ' ' . $excerpt . ' ' . $blog_name);
            }

            if (strtolower((string) $charset) != 'utf-8') {
                $title     = iconv((string) $charset, 'UTF-8', (string) $title);
                $excerpt   = iconv((string) $charset, 'UTF-8', (string) $excerpt);
                $blog_name = iconv((string) $charset, 'UTF-8', (string) $blog_name);
            }

            $title = trim(Html::clean($title));
            $title = Html::decodeEntities($title);
            $title = Html::escapeHTML($title);
            $title = Text::cutString($title, 60);

            $excerpt = trim(Html::clean($excerpt));
            $excerpt = Html::decodeEntities($excerpt);
            $excerpt = (string) preg_replace('/\s+/ms', ' ', $excerpt);
            $excerpt = Text::cutString($excerpt, 252);
            $excerpt = Html::escapeHTML($excerpt) . '...';

            $blog_name = trim(Html::clean($blog_name));
            $blog_name = Html::decodeEntities($blog_name);
            $blog_name = Html::escapeHTML($blog_name);
            $blog_name = Text::cutString($blog_name, 60);

            try {
                $this->addBacklink((int) $post_id, $url, $blog_name, $title, $excerpt, $comment);
            } catch (Throwable $e) {
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

    public function receivePingback(string $from_url, string $to_url): string
    {
        try {
            $posts = $this->getTargetPost($to_url);

            if ($this->pingAlreadyDone((int) $posts->post_id, $from_url)) {
                throw new BadRequestException(__('Don\'t repeat yourself, please.'));
            }

            $remote_content = $this->getRemoteContent($from_url);

            # We want a title...
            if (!preg_match('!<title>([^<].*?)</title>!mis', $remote_content, $m)) {
                throw new BadRequestException(__('Where\'s your title?'));
            }
            $title = trim(Html::clean($m[1]));
            $title = Html::decodeEntities($title);
            $title = Html::escapeHTML($title);
            $title = Text::cutString($title, 60);

            $blog_name = $this->getSourceName($remote_content);

            preg_match('!<body[^>]*?>(.*)?</body>!msi', $remote_content, $m);
            $source = $m[1] ?? '';
            $source = (string) preg_replace('![\r\n\s]+!ms', ' ', $source);
            $source = (string) preg_replace("/<\/*(h\d|p|th|td|li|dt|dd|pre|caption|input|textarea|button)[^>]*>/", "\n\n", $source);
            $source = strip_tags($source, '<a>');
            $source = explode("\n\n", $source);

            $excerpt = '';
            foreach ($source as $line) {
                if (str_contains($line, $to_url)) {
                    if (preg_match('!<a[^>]+?' . $to_url . '[^>]*>([^>]+?)</a>!', $line, $m)) {
                        $excerpt = strip_tags($line);

                        break;
                    }
                }
            }
            if ($excerpt) {
                $excerpt = '(&#8230;) ' . Text::cutString(Html::escapeHTML($excerpt), 200) . ' (&#8230;)';
            } else {
                $excerpt = '(&#8230;)';
            }

            $comment = '';
            $this->addBacklink((int) $posts->post_id, $from_url, $blog_name, $title, $excerpt, $comment);
        } catch (Throwable) {
            throw new BadRequestException(__('Sorry, an internal problem has occured.'));
        }

        return __('Thanks, mate. It was a pleasure.');
    }

    public function receiveWebmention(): void
    {
        $err = $post_id = false;
        header('Content-Type: text/html; charset=UTF-8');

        try {
            # Check if post and target are valid URL
            if (empty($_POST['source']) || empty($_POST['target'])) {
                throw new BadRequestException('Source or target is not valid');
            }

            $from_url = urldecode((string) $_POST['source']);
            $to_url   = urldecode((string) $_POST['target']);

            self::checkURLs($from_url, $to_url);

            # Try to find post
            $posts   = $this->getTargetPost($to_url);
            $post_id = $posts->post_id;

            # Check if it's an updated mention
            if ($this->pingAlreadyDone((int) $post_id, $from_url)) {
                $this->delBacklink($post_id, $from_url);
            }

            # Create a comment for received webmention
            $remote_content = $this->getRemoteContent($from_url);

            # We want a title...
            if (!preg_match('!<title>([^<].*?)</title>!mis', $remote_content, $m)) {
                throw new BadRequestException(__('Where\'s your title?'));
            }
            $title = trim(Html::clean($m[1]));
            $title = Html::decodeEntities($title);
            $title = Html::escapeHTML($title);
            $title = Text::cutString($title, 60);

            $blog_name = $this->getSourceName($remote_content);

            preg_match('!<body[^>]*?>(.*)?</body>!msi', $remote_content, $m);
            $source = $m[1] ?? '';
            $source = (string) preg_replace('![\r\n\s]+!ms', ' ', $source);
            $source = (string) preg_replace("/<\/*(h\d|p|th|td|li|dt|dd|pre|caption|input|textarea|button)[^>]*>/", "\n\n", $source);
            $source = strip_tags($source, '<a>');
            $source = explode("\n\n", $source);

            $excerpt = '';
            foreach ($source as $line) {
                if (str_contains($line, $to_url)) {
                    if (preg_match('!<a[^>]+?' . $to_url . '[^>]*>([^>]+?)</a>!', $line, $m)) {
                        $excerpt = strip_tags($line);

                        break;
                    }
                }
            }
            if ($excerpt) {
                $excerpt = '(&#8230;) ' . Text::cutString(Html::escapeHTML($excerpt), 200) . ' (&#8230;)';
            } else {
                $excerpt = '(&#8230;)';
            }

            $comment = '';
            $this->addBacklink((int) $post_id, $from_url, $blog_name, $title, $excerpt, $comment);

            # All done, thanks
            $code = $this->blog->settings()->system->trackbacks_pub ? 200 : 202;
            Http::head($code);

            return;
        } catch (Throwable $e) {
            $err = $e->getMessage();
        }

        Http::head(400);
        echo $err ?: 'Something went wrong.';
    }

    /**
     * Check if a post previously received a ping a from an URL.
     *
     * @param   int     $post_id    The post identifier
     * @param   string  $from_url   The from url
     *
     * @return  bool
     */
    private function pingAlreadyDone(int $post_id, string $from_url): bool
    {
        $params = [
            'post_id'           => $post_id,
            'comment_site'      => $from_url,
            'comment_trackback' => 1,
        ];

        $rs = $this->blog->getComments($params, true);
        if (!$rs->isEmpty()) {
            return (bool) $rs->f(0);
        }

        return false;
    }

    /**
     * Create a comment marked as trackback for a given post.
     *
     * @param   int     $post_id    The post identifier
     * @param   string  $url        The url
     * @param   string  $blog_name  The blog name
     * @param   string  $title      The title
     * @param   string  $excerpt    The excerpt
     * @param   string  $comment    The comment
     */
    private function addBacklink(int $post_id, string $url, string $blog_name, string $title, string $excerpt, string &$comment): void
    {
        if (empty($blog_name)) {
            // Let use title as text link for this backlink
            $blog_name = ($title ?: 'Anonymous blog');
        }

        $comment = "<!-- TB -->\n" .
            '<p><strong>' . ($title ?: $blog_name) . "</strong></p>\n" .
            '<p>' . $excerpt . '</p>';

        $cur                    = $this->blog->openCommentCursor();
        $cur->comment_author    = $blog_name;
        $cur->comment_site      = $url;
        $cur->comment_content   = $comment;
        $cur->post_id           = $post_id;
        $cur->comment_trackback = 1;
        $cur->comment_status    = $this->blog->settings()->system->trackbacks_pub ? $this->blog::COMMENT_PUBLISHED : $this->blog::COMMENT_PENDING;
        $cur->comment_ip        = Http::realIP();

        # --BEHAVIOR-- publicBeforeTrackbackCreate -- Cursor
        $this->behavior->callBehavior('publicBeforeTrackbackCreate', $cur);
        if ($cur->post_id) {
            $comment_id = $this->blog->addComment($cur);

            # --BEHAVIOR-- publicAfterTrackbackCreate -- Cursor, int
            $this->behavior->callBehavior('publicAfterTrackbackCreate', $cur, $comment_id);
        }
    }

    /**
     * Delete previously received comment made from an URL for a given post.
     *
     * @param   int     $post_id    The post identifier
     * @param   string  $url        The url
     */
    private function delBacklink(int $post_id, string $url): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->con->prefix() . $this->blog::COMMENT_TABLE_NAME)
            ->where('post_id = ' . (string) $post_id)
            ->and('comment_site = ' . $sql->quote($this->con->escapeStr($url)))
            ->and('comment_trackback = 1')
            ->delete();
    }

    /**
     * Get the charset from HTTP headers.
     *
     * @param   string  $header     The header
     *
     * @return  mixed   The charset from request.
     */
    private static function getCharsetFromRequest(string $header = '')
    {
        if (!$header && isset($_SERVER['CONTENT_TYPE'])) {
            $header = $_SERVER['CONTENT_TYPE'];
        }

        if ($header) {
            if (preg_match('|charset=([a-zA-Z0-9-]+)|', (string) $header, $m)) {
                return $m[1];
            }
        }
    }

    /**
     * Detect encoding.
     *
     * @param   string  $content    The content
     *
     * @return  string
     */
    private static function detectCharset(string $content): string
    {
        return (string) mb_detect_encoding(
            $content,
            'UTF-8,ISO-8859-1,ISO-8859-2,ISO-8859-3,' .
            'ISO-8859-4,ISO-8859-5,ISO-8859-6,ISO-8859-7,ISO-8859-8,' .
            'ISO-8859-9,ISO-8859-10,ISO-8859-13,ISO-8859-14,ISO-8859-15'
        );
    }

    /**
     * Retrieve local post from a given URL.
     *
     * @param   string  $to_url  To url
     *
     * @throws  BadRequestException
     *
     * @return  MetaRecord  The target post.
     */
    private function getTargetPost(string $to_url): MetaRecord
    {
        $reg = '!^' . preg_quote($this->blog->url()) . '(.*)!';

        # Are you dumb?
        if (!preg_match($reg, $to_url, $m)) {
            throw new BadRequestException(__('Any chance you ping one of my contents? No? Really?'));
        }

        # Does the targeted URL look like a registered post type?
        $url_part   = $m[1];
        $p_type     = '';
        $post_types = $this->post_types->dump();
        $post_url   = '';
        foreach ($post_types as $v) {
            $reg = '!^' . preg_quote(str_replace('%s', '', $v->get('public_url'))) . '(.*)!';
            if (preg_match($reg, $url_part, $n)) {
                $p_type   = $v->get('type');
                $post_url = $n[1];

                break;
            }
        }

        if (empty($p_type)) {
            throw new BadRequestException(__('Sorry but you can not ping this type of content.'));
        }

        # Time to see if we've got a winner...
        $params = [
            'post_type' => $p_type,
            'post_url'  => $post_url,
        ];
        $posts = $this->blog->getPosts($params);

        # Missed!
        if ($posts->isEmpty()) {
            throw new BadRequestException(__('Oops. Kinda "not found" stuff. Please check the target URL twice.'));
        }

        # Nice try. But, sorry, no.
        if (!$posts->trackbacksActive()) {
            throw new BadRequestException(__('Sorry, dude. This entry does not accept pingback at the moment.'));
        }

        return $posts;
    }

    /**
     * Get content of a distant page.
     *
     * @param   string  $from_url  Target URL
     *
     * @throws  BadRequestException
     *
     * @return  string
     */
    private function getRemoteContent(string $from_url): string
    {
        $remote_content = '';

        $from_path = '';
        $http      = self::initHttp($from_url, $from_path);
        if ($http === false) {
            return '';
        }

        # First round : just to be sure the ping comes from an acceptable resource type.
        $http->setHeadersOnly(true);
        $http->get($from_path);
        $header_ct = $http->getHeader('content-type');
        if (is_array($header_ct)) {
            $header_ct = implode(';', $header_ct);
        }
        if ($header_ct !== false) {
            $c_type = explode(';', $header_ct);

            # Bad luck. Bye, bye...
            if (!in_array($c_type[0], ['text/html', 'application/xhtml+xml'])) {
                throw new BadRequestException(__('Your source URL does not look like a supported content type. Sorry. Bye, bye!'));
            }
        }

        # Second round : let's go fetch and parse the remote content
        $http->setHeadersOnly(false);
        $http->get($from_path);
        $remote_content = $http->getContent();

        # Convert content charset
        $header_ct = $http->getHeader('content-type');
        if (is_array($header_ct)) {
            $header_ct = implode(';', $header_ct);
        }
        if ($header_ct !== false) {
            $charset = self::getCharsetFromRequest($header_ct);
            if (!$charset) {
                $charset = self::detectCharset($remote_content);
            }
            if (strtolower((string) $charset) != 'utf-8') {
                $remote_content = iconv((string) $charset, 'UTF-8', $remote_content);
                if ($remote_content === false) {
                    $remote_content = '';
                }
            }
        }

        return $remote_content;
    }
    //@}

    /// @name Discover
    //@{

    /**
     * Find ping URLs from links inside text
     *
     * @param      string  $text   The text
     *
     * @return     array<string>
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
     * Try to find source blog name or author.
     *
     * Does this from remote HTML page content
     * Used when receive a webmention or a pingback
     *
     * @param   string  $content    The content
     *
     * @return  string
     */
    private function getSourceName(string $content): string
    {
        // Clean text utility function
        $clean = fn ($text, $size = 255) => Text::cutString(Html::escapeHTML(Html::decodeEntities(Html::clean(trim((string) $text)))), $size);

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

        // Try to find a <meta name="author" content="???">
        if (preg_match('!<meta\sname="author"\scontent="([^<].*?)"\s?\/?>!msi', $content, $m)) {
            return $clean($m[1]);
        }

        return '';
    }

    /**
     * Find links into a text.
     *
     * @param   string  $text   The text
     *
     * @return  array<string>   The text links.
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
     * @param   string  $url    The url
     *
     * @return  mixed   The ping url.
     */
    private function getPingURL(string $url)
    {
        if (str_starts_with($url, '/')) {
            $url = Http::getHost() . $url;
        }

        try {
            $path = '';
            $http = self::initHttp($url, $path);
            if ($http === false) {
                return false;
            }

            $http->get($path);
            $page_content = $http->getContent();
            $pb_url       = $http->getHeader('x-pingback');
            $wm_url       = $http->getHeader('link');

            if (is_array($pb_url)) {
                // We keep the first pingback URL only
                $pb_url = $pb_url[0];
            }
            if (is_array($wm_url)) {
                // We keep the first webmention URL only
                $wm_url = $wm_url[0];
            }
        } catch (Throwable) {
            return false;
        }

        # Let's check for an elderly trackback data chunk...
        $pattern_rdf = '/<rdf:RDF.*?>.*?' .
            '<rdf:Description\s+(.*?)\/>' .
            '.*?<\/rdf:RDF>' .
            '/msi';

        preg_match_all($pattern_rdf, $page_content, $rdf_all, PREG_SET_ORDER);

        $url_path = parse_url($url, PHP_URL_PATH);
        if (!$url_path) {
            $url_path = '';
        }
        $sanitized_url = str_replace($url_path, Html::sanitizeURL($url_path), $url);

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
            $header_ct = $http->getHeader('content-type');
            if (is_array($header_ct)) {
                $header_ct = implode(';', $header_ct);
            }
            if ($header_ct !== false) {
                $type = explode(';', $header_ct);
                if (!in_array($type[0], ['text/html', 'application/xhtml+xml'])) {
                    $wm_url = false;
                }
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

        # Else check content for <link href="ENDPOINT_URL" rel="webmention">
        if ($wm_url && !$wm_api) {
            $content = (string) preg_replace('/<!--(.*)-->/Us', '', $page_content);
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
     * @param   string  $url    The url
     * @param   string  $path   The path
     *
     * @return  false|HttpClient
     */
    private static function initHttp(string $url, string &$path)
    {
        $client = HttpClient::initClient($url, $path);
        if ($client !== false) {
            $client->setTimeout(self::$query_timeout);
            $client->setUserAgent('Dotclear - https://dotclear.org/');
            $client->useGzip(false);
            $client->setPersistReferers(false);
        }

        return $client;
    }

    public static function checkURLs(string $from_url, string $to_url): void
    {
        if (!(filter_var($from_url, FILTER_VALIDATE_URL) && preg_match('!^https?://!', $from_url))) {
            throw new BadRequestException(__('No valid source URL provided? Try again!'), 0);
        }

        if (!(filter_var($to_url, FILTER_VALIDATE_URL) && preg_match('!^https?://!', $to_url))) {
            throw new BadRequestException(__('No valid target URL provided? Try again!'), 0);
        }

        if (Html::sanitizeURL(urldecode($from_url)) == Html::sanitizeURL(urldecode($to_url))) {
            throw new BadRequestException(__('LOL!'), 0);
        }
    }
}
