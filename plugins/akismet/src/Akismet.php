<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\akismet;

use Dotclear\App;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @brief   The module HTTP client helper.
 * @ingroup akismet
 */
class Akismet extends HttpClient
{
    /**
     * Akismet domain.
     */
    protected string $base_host = 'rest.akismet.com';

    /**
     * Akismet URL host, composed with API key.
     */
    protected string $ak_host = '';

    /**
     * Akismet API version.
     */
    protected string $ak_version = '1.1';

    /**
     * Akismet path pattern.
     */
    protected string $ak_path = '/%s/%s';

    /**
     * Constructs a new instance.
     *
     * @param   string          $blog_url   The blog URL
     * @param   null|string     $ak_key     The Akismet API key
     */
    public function __construct(
        protected string $blog_url,
        protected ?string $ak_key = null
    ) {
        $this->ak_path = sprintf($this->ak_path, $this->ak_version, '%s');
        $this->ak_host = $this->ak_key . '.' . $this->base_host;

        parent::__construct($this->ak_host, 80, App::config()->queryTimeout());
    }

    /**
     * Verify API key.
     *
     * @return  bool    True if verified
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
     * Check if a comment is valid or not.
     *
     * @param   string          $permalink  The permalink
     * @param   string          $type       The type
     * @param   null|string     $author     The author
     * @param   null|string     $email      The email
     * @param   null|string     $url        The url
     * @param   null|string     $content    The content
     *
     * @return  bool    True if valid
     */
    public function comment_check(string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content): bool
    {
        $info_ignore = ['HTTP_COOKIE'];
        $info        = [];

        foreach ($_SERVER as $k => $v) {
            if (str_starts_with((string) $k, 'HTTP_') && !in_array($k, $info_ignore)) {
                $info[$k] = $v;
            }
        }

        return $this->callFunc('comment-check', $permalink, $type, $author, $email, $url, $content, $info);
    }

    /**
     * Submit positive (spam) comment to Akismet API.
     *
     * @param   string          $permalink  The permalink
     * @param   string          $type       The type
     * @param   null|string     $author     The author
     * @param   null|string     $email      The email
     * @param   null|string     $url        The url
     * @param   null|string     $content    The content
     *
     * @return  bool    True
     */
    public function submit_spam(string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content): bool
    {
        $this->callFunc('submit-spam', $permalink, $type, $author, $email, $url, $content);

        return true;
    }

    /**
     * Submit negative (not spam) comment to Akismet API.
     *
     * @param   string          $permalink  The permalink
     * @param   string          $type       The type
     * @param   null|string     $author     The author
     * @param   null|string     $email      The email
     * @param   null|string     $url        The url
     * @param   null|string     $content    The content
     *
     * @return  bool    True
     */
    public function submit_ham(string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content): bool
    {
        $this->callFunc('submit-ham', $permalink, $type, $author, $email, $url, $content);

        return true;
    }

    /**
     * Call an Akismet API method.
     *
     * @param   string                  $function   The function
     * @param   string                  $permalink  The permalink
     * @param   string                  $type       The type
     * @param   string                  $author     The author
     * @param   string                  $email      The email
     * @param   string                  $url        The url
     * @param   string                  $content    The content
     * @param   array<string,string>    $info       The information
     *
     * @throws  Exception
     *
     * @return  bool    True if API respond a contents
     */
    protected function callFunc(string $function, string $permalink, string $type, ?string $author, ?string $email, ?string $url, ?string $content, array $info = []): bool
    {
        $ua      = $info['HTTP_USER_AGENT'] ?? '';
        $referer = $info['HTTP_REFERER']    ?? '';

        # Prepare comment data
        $data = [
            'blog'                 => $this->blog_url,
            'user_ip'              => Http::realIP(),
            'user_agent'           => $ua,
            'referrer'             => $referer,
            'permalink'            => $permalink,
            'comment_type'         => $type,
            'comment_author'       => $author,
            'comment_author_email' => $email,
            'comment_author_url'   => $url,
            'comment_content'      => $content,
        ];

        $data = [...$data, ...$info];

        $this->host = $this->ak_host;
        $path       = sprintf($this->ak_path, $function);

        if (!$this->post($path, $data, 'UTF-8')) {
            throw new Exception('HTTP error: ' . $this->getStatus());
        }

        return $this->getContent() === 'true';
    }
}
