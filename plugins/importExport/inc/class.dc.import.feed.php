<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcImportFeed extends dcIeModule
{
    protected $status   = false;
    protected $feed_url = '';

    // IPv6 functions (from https://gist.github.com/tbaschak/7866688)
    private function gethostbyname6($host, $try_a = false)
    {
        // get AAAA record for $host
        // if $try_a is true, if AAAA fails, it tries for A
        // the first match found is returned
        // otherwise returns false

        $dns = $this->gethostbynamel6($host, $try_a);
        if ($dns == false) {
            return false;
        } else {
            return $dns[0];
        }
    }
    private function gethostbynamel6($host, $try_a = false)
    {
        // get AAAA records for $host,
        // if $try_a is true, if AAAA fails, it tries for A
        // results are returned in an array of ips found matching type
        // otherwise returns false

        $dns6 = dns_get_record($host, DNS_AAAA);
        if ($try_a == true) {
            $dns4 = dns_get_record($host, DNS_A);
            $dns  = array_merge($dns4, $dns6);
        } else {
            $dns = $dns6;
        }
        $ip6 = array();
        $ip4 = array();
        foreach ($dns as $record) {
            if ($record["type"] == "A") {
                $ip4[] = $record["ip"];
            }
            if ($record["type"] == "AAAA") {
                $ip6[] = $record["ipv6"];
            }
        }
        if (count($ip6) < 1) {
            if ($try_a == true) {
                if (count($ip4) < 1) {
                    return false;
                } else {
                    return $ip4;
                }
            } else {
                return false;
            }
        } else {
            return $ip6;
        }
    }

    public function setInfo()
    {
        $this->type        = 'import';
        $this->name        = __('RSS or Atom feed import');
        $this->description = __('Add a feed content to the blog.');
    }

    public function process($do)
    {
        if ($do == 'ok') {
            $this->status = true;
            return;
        }

        if (empty($_POST['feed_url'])) {
            return;
        }

        $this->feed_url = $_POST['feed_url'];

        // Check feed URL
        if ($this->core->blog->settings->system->import_feed_url_control) {
            // Get IP from URL
            $bits = parse_url($this->feed_url);
            if (!$bits || !isset($bits['host'])) {
                throw new Exception(__('Cannot retrieve feed URL.'));
            }
            $ip = gethostbyname($bits['host']);
            if ($ip == $bits['host']) {
                $ip = $this->gethostbyname6($bits['host']);
                if (!$ip) {
                    throw new Exception(__('Cannot retrieve feed URL.'));
                }
            }
            // Check feed IP
            $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
            if ($this->core->blog->settings->system->import_feed_no_private_ip) {
                $flag |= FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            }
            if (!filter_var($ip, $flag)) {
                throw new Exception(__('Cannot retrieve feed URL.'));
            }
            // IP control (white list regexp)
            if ($this->core->blog->settings->system->import_feed_ip_regexp != '') {
                if (!preg_match($this->core->blog->settings->system->import_feed_ip_regexp, $ip)) {
                    throw new Exception(__('Cannot retrieve feed URL.'));
                }
            }
            // Port control (white list regexp)
            if ($this->core->blog->settings->system->import_feed_port_regexp != '' && isset($bits['port'])) {
                if (!preg_match($this->core->blog->settings->system->import_feed_port_regexp, $bits['port'])) {
                    throw new Exception(__('Cannot retrieve feed URL.'));
                }
            }
        }

        $feed = feedReader::quickParse($this->feed_url);
        if ($feed === false) {
            throw new Exception(__('Cannot retrieve feed URL.'));
        }
        if (count($feed->items) == 0) {
            throw new Exception(__('No items in feed.'));
        }

        $cur = $this->core->con->openCursor($this->core->prefix . 'post');
        $this->core->con->begin();
        foreach ($feed->items as $item) {
            $cur->clean();
            $cur->user_id      = $this->core->auth->userID();
            $cur->post_content = $item->content ?: $item->description;
            $cur->post_title   = $item->title ?: text::cutString(html::clean($cur->post_content), 60);
            $cur->post_format  = 'xhtml';
            $cur->post_status  = -2;
            $cur->post_dt      = strftime('%Y-%m-%d %H:%M:%S', $item->TS);

            try {
                $post_id = $this->core->blog->addPost($cur);
            } catch (Exception $e) {
                $this->core->con->rollback();
                throw $e;
            }

            foreach ($item->subject as $subject) {
                $this->core->meta->setPostMeta($post_id, 'tag', dcMeta::sanitizeMetaID($subject));
            }
        }

        $this->core->con->commit();
        http::redirect($this->getURL() . '&do=ok');

    }

    public function gui()
    {
        if ($this->status) {
            dcPage::success(__('Content successfully imported.'));
        }

        echo
        '<form action="' . $this->getURL(true) . '" method="post">' .
        '<p>' . sprintf(__('Add a feed content to the current blog: <strong>%s</strong>.'), html::escapeHTML($this->core->blog->name)) . '</p>' .

        '<p><label for="feed_url">' . __('Feed URL:') . '</label>' .
        form::url('feed_url', 50, 300, html::escapeHTML($this->feed_url)) . '</p>' .

        '<p>' .
        $this->core->formNonce() .
        form::hidden(array('do'), 1) .
        '<input type="submit" value="' . __('Import') . '" /></p>' .

            '</form>';
    }
}
