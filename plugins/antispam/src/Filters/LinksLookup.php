<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam\Filters;

use Dotclear\Plugin\antispam\SpamFilter;

/**
 * @brief   The module links lookup filter.
 * @ingroup antispam
 */
class LinksLookup extends SpamFilter
{
    /**
     * Filter id.
     */
    public string $id = 'dcFilterLinksLookup';

    /**
     * Filter name.
     */
    public string $name = 'Links Lookup';

    /**
     * Filter has settings GUI?
     */
    public bool $has_gui = false;

    /**
     * Filter help ID.
     */
    public ?string $help = '';

    /**
     * subrl org URL.
     */
    private string $server = 'multi.surbl.org';

    /**
     * Sets the filter description.
     */
    protected function setInfo(): void
    {
        $this->description = __('Checks links in comments against surbl.org');
    }

    /**
     * Gets the status message.
     *
     * @param   string  $status         The status
     * @param   int     $comment_id     The comment identifier
     *
     * @return  string  The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return sprintf(__('Filtered by %1$s with server %2$s.'), $this->guiLink(), $status);
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
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status): ?bool
    {
        if (!$ip || long2ip((int) ip2long($ip)) !== $ip) {
            return null;
        }

        $urls = $this->getLinks((string) $content);
        array_unshift($urls, $site);

        foreach ($urls as $u) {
            if (empty($u)) {
                continue;
            }
            $b = parse_url($u);
            if (!isset($b['host']) || !$b['host']) {
                continue;
            }

            $domain      = (string) preg_replace('/^[\w]{2,6}:\/\/([\w\d\.\-]+).*$/', '$1', $b['host']);
            $domain_elem = explode('.', $domain);

            $i = count($domain_elem) - 1;
            if ($i == 0) {
                // "domain" is 1 word long, don't check it
                return null;
            }
            $host = $domain_elem[$i];
            do {
                $host = $domain_elem[$i - 1] . '.' . $host;
                $i--;
                $response = gethostbyname($host . '.' . $this->server);
                if (str_starts_with($response, '127') && substr($response, 8) !== '1') {
                    $status = substr($domain, 0, 128);

                    return true;
                }
            } while ($i > 0);
        }

        return null;
    }

    /**
     * Return the links URL in content.
     *
     * @param   string  $text   The text
     *
     * @return  array<string>   The links.
     */
    private function getLinks(string $text): array
    {
        // href attribute on "a" tags is second match
        preg_match_all('|<a.*?href="(http.*?)"|', $text, $parts);

        return $parts[1];
    }
}
