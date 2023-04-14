<?php
/**
 * @brief fairTrackbacks, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\fairTrackbacks;

use dcCore;
use Dotclear\Helper\Network\HttpClient;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

class AntispamFilterFairTrackbacks extends SpamFilter
{
    /**
     * Filter id
     *
     * @var        string
     */
    public $id = 'dcFilterFairTrackbacks';

    /**
     * Filter name
     *
     * @var        string
     */
    public $name = 'Fair Trackbacks';

    /**
     * Has GUI settings
     *
     * @var        bool
     */
    public $has_gui = false;

    /**
     * Filter active?
     *
     * @var        bool
     */
    public $active = true;

    /**
     * Filter order
     *
     * @var        int
     */
    public $order = -10;

    /**
     * Sets the filter description.
     */
    protected function setInfo()
    {
        $this->description = __('Checks trackback source for a link to the post');
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
        if ($type != 'trackback') {
            return;
        }

        try {
            // Check source site URL
            $default_parse = ['scheme' => '', 'host' => '', 'path' => '', 'query' => ''];
            $site_parts    = array_merge($default_parse, parse_url($site));

            if (($site_parts['scheme'] !== 'http' && $site_parts['scheme'] !== 'https') || !$site_parts['host'] || !$site_parts['path']) {
                throw new Exception('Invalid URL');
            }

            // Check incomink link page
            $post       = dcCore::app()->blog->getPosts(['post_id' => $post_id]);
            $post_url   = $post->getURL();
            $post_parts = array_merge($default_parse, parse_url($post_url));

            if ($post_url === $site) {
                throw new Exception('Same source and destination');
            }

            $path       = '';
            $http_query = HttpClient::initClient($site, $path);
            $http_query->setTimeout(DC_QUERY_TIMEOUT);
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
        } catch (Exception $e) {
            throw new Exception('Trackback not allowed for this URL.');
        }
    }
}
