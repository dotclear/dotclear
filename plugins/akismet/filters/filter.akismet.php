<?php
/**
 * @brief akismet, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcFilterAkismet extends dcSpamFilter
{
    /**
     * Filter name
     *
     * @var        string
     */
    public $name = 'Akismet';

    /**
     * Has GUI settings
     *
     * @var        bool
     */
    public $has_gui = true;

    /**
     * Filter active?
     *
     * @var        bool
     */
    public $active = false;

    /**
     * Filter help resource ID
     *
     * @var        string
     */
    public $help = 'akismet-filter';

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();

        if (defined('DC_AKISMET_SUPER') && DC_AKISMET_SUPER && !dcCore::app()->auth->isSuperAdmin()) {
            $this->has_gui = false;
        }
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo()
    {
        $this->description = __('Akismet spam filter');
    }

    /**
     * Gets the status message.
     *
     * @param      string  $status      The status
     * @param      int     $comment_id  The comment identifier
     *
     * @return     string  The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return sprintf(__('Filtered by %s.'), $this->guiLink());
    }

    /**
     * Return a new akismet instance of false if API key not defined
     *
     * @return     akismet|bool
     */
    private function akInit()
    {
        if (!dcCore::app()->blog->settings->akismet->ak_key) {
            return false;
        }

        return new akismet(dcCore::app()->blog->url, dcCore::app()->blog->settings->akismet->ak_key);
    }

    /**
     * This method should return if a comment is a spam or not. If it returns true
     * or false, execution of next filters will be stoped. If should return nothing
     * to let next filters apply.
     *
     * @param      string   $type     The comment type (comment / trackback)
     * @param      string   $author   The comment author
     * @param      string   $email    The comment author email
     * @param      string   $site     The comment author site
     * @param      string   $ip       The comment author IP
     * @param      string   $content  The comment content
     * @param      int      $post_id  The comment post_id
     * @param      string   $status   The comment status
     *
     * @return  mixed
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if (($ak = $this->akInit()) === false) {
            return;
        }

        try {
            if ($ak->verify()) {
                $post = dcCore::app()->blog->getPosts(['post_id' => $post_id]);

                $c = $ak->comment_check(
                    $post->getURL(),
                    $type,
                    $author,
                    $email,
                    $site,
                    $content
                );

                if ($c) {
                    $status = 'Filtered by Akismet';

                    return true;
                }
            }
        } catch (Exception $e) {
            // If http or akismet is dead, we don't need to know it
        }
    }

    /**
     * Train the antispam filter
     *
     * @param      string                                   $status   The comment status
     * @param      string                                   $filter   The filter
     * @param      string                                   $type     The comment type
     * @param      string                                   $author   The comment author
     * @param      string                                   $email    The comment author email
     * @param      string                                   $site     The comment author site
     * @param      string                                   $ip       The comment author IP
     * @param      string                                   $content  The comment content
     * @param      record|staticRecord|extStaticRecord      $rs       The comment record
     */
    public function trainFilter(string $status, string $filter, string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, $rs)
    {
        # We handle only false positive from akismet
        if ($status === 'spam' && $filter !== 'dcFilterAkismet') {
            return;
        }

        $f = $status === 'spam' ? 'submit_spam' : 'submit_ham';

        if (($ak = $this->akInit()) === false) {
            return;
        }

        try {
            if ($ak->verify()) {
                $ak->{$f}($rs->getPostURL(), $type, $author, $email, $site, $content);
            }
        } catch (Exception $e) {
            // If http or akismet is dead, we don't need to know it
        }
    }

    /**
     * Filter settings
     *
     * @param      string  $url    The GUI URL
     *
     * @return     string
     */
    public function gui($url): string
    {
        dcCore::app()->blog->settings->addNamespace('akismet');
        $ak_key      = dcCore::app()->blog->settings->akismet->ak_key;
        $ak_verified = null;

        if (isset($_POST['ak_key'])) {
            try {
                $ak_key = $_POST['ak_key'];

                dcCore::app()->blog->settings->akismet->put('ak_key', $ak_key, 'string');

                dcPage::addSuccessNotice(__('Filter configuration have been successfully saved.'));
                http::redirect($url);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (dcCore::app()->blog->settings->akismet->ak_key) {
            try {
                $ak          = new akismet(dcCore::app()->blog->url, dcCore::app()->blog->settings->akismet->ak_key);
                $ak_verified = $ak->verify();
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        $res = dcPage::notices();

        $res .= '<form action="' . html::escapeURL($url) . '" method="post" class="fieldset">' .
        '<p><label for="ak_key" class="classic">' . __('Akismet API key:') . '</label> ' .
        form::field('ak_key', 12, 128, $ak_key);

        if ($ak_verified !== null) {
            if ($ak_verified) {
                $res .= ' <img src="images/check-on.png" alt="" /> ' . __('API key verified');
            } else {
                $res .= ' <img src="images/check-off.png" alt="" /> ' . __('API key not verified');
            }
        }

        $res .= '</p>';

        $res .= '<p><a href="https://akismet.com/">' . __('Get your own API key') . '</a></p>' .
        '<p><input type="submit" value="' . __('Save') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
            '</form>';

        return $res;
    }
}

class akismet extends netHttp
{
    /**
     * Akismet domain
     *
     * @var        string
     */
    protected $base_host = 'rest.akismet.com';

    /**
     * Akismet URL host, composed with API key
     *
     * @var        string
     */
    protected $ak_host = '';

    /**
     * Akismet API version
     *
     * @var        string
     */
    protected $ak_version = '1.1';

    /**
     * Akismet path pattern
     *
     * @var        string
     */
    protected $ak_path = '/%s/%s';

    /**
     * Akismet API key
     *
     * @var        null|string
     */
    protected $ak_key = null;

    /**
     * Blog URL
     *
     * $var        string
     */
    protected $blog_url;

    /**
     * Constructs a new instance.
     *
     * @param      string       $blog_url  The blog URL
     * @param      null|string  $api_key   The API key
     */
    public function __construct(string $blog_url, ?string $api_key)
    {
        $this->blog_url = $blog_url;
        $this->ak_key   = $api_key;

        $this->ak_path = sprintf($this->ak_path, $this->ak_version, '%s');
        $this->ak_host = $this->ak_key . '.' . $this->base_host;

        parent::__construct($this->ak_host, 80, DC_QUERY_TIMEOUT);
    }

    /**
     * Verify API key
     *
     * @return     bool
     */
    public function verify(): bool
    {
        $this->host = $this->base_host;
        $path       = sprintf($this->ak_path, 'verify-key');

        $data = [
            'key'  => $this->ak_key,
            'blog' => $this->blog_url,
        ];

        if ($this->post($path, $data, 'UTF-8')) {
            return $this->getContent() === 'valid';
        }

        return false;
    }

    /**
     * Check if a comment is valid or not
     *
     * @param      string       $permalink  The permalink
     * @param      string       $type       The type
     * @param      null|string  $author     The author
     * @param      null|string  $email      The email
     * @param      null|string  $url        The url
     * @param      null|string  $content    The content
     *
     * @return     bool
     */
    public function comment_check(string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content): bool
    {
        $info_ignore = ['HTTP_COOKIE'];
        $info        = [];

        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0 && !in_array($k, $info_ignore)) {
                $info[$k] = $v;
            }
        }

        return $this->callFunc('comment-check', $permalink, $type, $author, $email, $url, $content, $info);
    }

    /**
     * Submit positive (spam) comment to Akismet API
     *
     * @param      string       $permalink  The permalink
     * @param      string       $type       The type
     * @param      null|string  $author     The author
     * @param      null|string  $email      The email
     * @param      null|string  $url        The url
     * @param      null|string  $content    The content
     *
     * @return     bool
     */
    public function submit_spam(string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content): bool
    {
        $this->callFunc('submit-spam', $permalink, $type, $author, $email, $url, $content);

        return true;
    }

    /**
     * Submit negative (not spam) comment to Akismet API
     *
     * @param      string       $permalink  The permalink
     * @param      string       $type       The type
     * @param      null|string  $author     The author
     * @param      null|string  $email      The email
     * @param      null|string  $url        The url
     * @param      null|string  $content    The content
     *
     * @return     bool
     */
    public function submit_ham(string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content): bool
    {
        $this->callFunc('submit-ham', $permalink, $type, $author, $email, $url, $content);

        return true;
    }

    /**
     * Call an Akismet API method
     *
     * @param      string     $function   The function
     * @param      string     $permalink  The permalink
     * @param      string     $type       The type
     * @param      string     $author     The author
     * @param      string     $email      The email
     * @param      string     $url        The url
     * @param      string     $content    The content
     * @param      array      $info       The information
     *
     * @throws     Exception
     *
     * @return     bool
     */
    protected function callFunc(string $function, string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content, array $info = []): bool
    {
        $ua      = $info['HTTP_USER_AGENT'] ?? '';
        $referer = $info['HTTP_REFERER']    ?? '';

        # Prepare comment data
        $data = [
            'blog'                 => $this->blog_url,
            'user_ip'              => http::realIP(),
            'user_agent'           => $ua,
            'referrer'             => $referer,
            'permalink'            => $permalink,
            'comment_type'         => $type,
            'comment_author'       => $author,
            'comment_author_email' => $email,
            'comment_author_url'   => $url,
            'comment_content'      => $content,
        ];

        $data = array_merge($data, $info);

        $this->host = $this->ak_host;
        $path       = sprintf($this->ak_path, $function);

        if (!$this->post($path, $data, 'UTF-8')) {
            throw new Exception('HTTP error: ' . $this->getStatus());
        }

        return $this->getContent() === 'true';
    }
}
