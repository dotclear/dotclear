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

if (!defined('DC_RC_PATH')) {return;}

class dcFilterFairTrackbacks extends dcSpamFilter
{
    public $name    = 'Fair Trackbacks';
    public $has_gui = false;
    public $active  = true;
    public $order   = -10;

    public function __construct($core)
    {
        parent::__construct($core);
    }

    protected function setInfo()
    {
        $this->description = __('Checks trackback source for a link to the post');
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if ($type != 'trackback') {
            return;
        }

        try
        {
            $default_parse = array('scheme' => '', 'host' => '', 'path' => '', 'query' => '');
            $S             = array_merge($default_parse, parse_url($site));

            if ($S['scheme'] != 'http' || !$S['host'] || !$S['path']) {
                throw new Exception('Invalid URL');
            }

            # Check incomink link page
            $post     = $this->core->blog->getPosts(array('post_id' => $post_id));
            $post_url = $post->getURL();
            $P        = array_merge($default_parse, parse_url($post_url));

            if ($post_url == $site) {
                throw new Exception('Same source and destination');
            }

            $o = netHttp::initClient($site, $path);
            $o->setTimeout(3);
            $o->get($path);

            # Trackback source does not return 200 status code
            if ($o->getStatus() != 200) {
                throw new Exception('Invalid Status Code');
            }

            $tb_page = $o->getContent();

            # Do we find a link to post in trackback source?
            if ($S['host'] == $P['host']) {
                $pattern = $P['path'] . ($P['query'] ? '?' . $P['query'] : '');
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
