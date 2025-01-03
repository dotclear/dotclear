<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\fairTrackbacks;

use Dotclear\App;
use Dotclear\Helper\Network\HttpClient;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @brief   The module trackbacks antispam filter.
 * @ingroup fairTrackbacks
 */
class AntispamFilterFairTrackbacks extends SpamFilter
{
    /**
     * Filter id.
     */
    public string $id = 'dcFilterFairTrackbacks';

    /**
     * Filter name.
     */
    public string $name = 'Fair Trackbacks';

    /**
     * Has GUI settings.
     */
    public bool $has_gui = false;

    /**
     * Filter active?
     */
    public bool $active = true;

    /**
     * Filter order.
     */
    public int $order = -10;

    /**
     * Sets the filter description.
     */
    protected function setInfo(): void
    {
        $this->description = __('Checks trackback source for a link to the post');
    }

    /**
     * This method should return if a comment is a spam or not.
     *
     * If it returns true or false, execution of next filters will be stoped.
     * If should return nothing to let next filters apply.
     *
     * @param   string  $type       The comment type (comment / trackback)
     * @param   string  $author     The comment author
     * @param   string  $email      The comment author email
     * @param   string  $site       The comment author site
     * @param   string  $ip         The comment author IP
     * @param   string  $content    The comment content
     * @param   int     $post_id    The comment post_id
     * @param   string  $status     The comment status
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status): void
    {
        if ($type !== 'trackback') {
            return;
        }

        try {
            $default_parse = ['scheme' => '', 'host' => '', 'path' => '', 'query' => ''];

            // Check source site URL
            $site_parts = $default_parse;
            if ($site && ($temp_parse = parse_url($site))) {
                $site_parts = array_merge($default_parse, $temp_parse);
            }

            if (($site_parts['scheme'] !== 'http' && $site_parts['scheme'] !== 'https') || !$site_parts['host'] || !$site_parts['path']) {
                throw new Exception('Invalid URL');
            }

            // Check incomink link page
            $post       = App::blog()->getPosts(['post_id' => $post_id]);
            $post_url   = $post->getURL();
            $post_parts = $default_parse;
            if ($post_url && ($temp_parts = parse_url($post_url))) {
                $post_parts = array_merge($default_parse, $temp_parts);
            }

            if ($post_url === $site) {
                throw new Exception('Same source and destination');
            }

            $path       = '';
            $http_query = HttpClient::initClient((string) $site, $path);
            if ($http_query === false) {
                throw new Exception('Unable to make an HTTP request');
            }
            $http_query->setTimeout(App::config()->queryTimeout());
            $http_query->get($path);

            // Trackback source does not return 200 status code
            if ($http_query->getStatus() !== 200) {
                throw new Exception('Invalid Status Code');
            }

            $tb_page = $http_query->getContent();

            // Do we find a link to post in trackback source?
            if ($site_parts['host'] === $post_parts['host']) {
                $pattern = $post_parts['path'] . ($post_parts['query'] ? '?' . $post_parts['query'] : '');
            } else {
                $pattern = $post_url;
            }
            $pattern = preg_quote($pattern, '/');

            if (!preg_match('/' . $pattern . '/', $tb_page)) {
                throw new Exception('Unfair');
            }
        } catch (Exception) {
            throw new Exception('Trackback not allowed for this URL.');
        }
    }
}
