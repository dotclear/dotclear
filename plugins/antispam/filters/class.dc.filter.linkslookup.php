<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcFilterLinksLookup extends dcSpamFilter
{
    public $name = 'Links Lookup';

    private $server = 'multi.surbl.org';

    protected function setInfo()
    {
        $this->description = __('Checks links in comments against surbl.org');
    }

    public function getStatusMessage($status, $comment_id)
    {
        return sprintf(__('Filtered by %1$s with server %2$s.'), $this->guiLink(), $status);
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if (!$ip || long2ip(ip2long($ip)) != $ip) {
            return;
        }

        $urls = $this->getLinks($content);
        array_unshift($urls, $site);

        foreach ($urls as $u) {
            $b = parse_url($u);
            if (!isset($b['host']) || !$b['host']) {
                continue;
            }

            $domain      = preg_replace('/^[\w]{2,6}:\/\/([\w\d\.\-]+).*$/', '$1', $b['host']);
            $domain_elem = explode(".", $domain);

            $i = count($domain_elem) - 1;
            if ($i == 0) {
                // "domain" is 1 word long, don't check it
                return;
            }
            $host = $domain_elem[$i];
            do {
                $host = $domain_elem[$i - 1] . '.' . $host;
                $i--;
                if (substr(gethostbyname($host . '.' . $this->server), 0, 3) == "127") {
                    $status = substr($domain, 0, 128);
                    return true;
                }
            } while ($i > 0);
        }
    }

    private function getLinks($text)
    {
        // href attribute on "a" tags is second match
        preg_match_all('|<a.*?href="(http.*?)"|', $text, $parts);

        return $parts[1];
    }
}
