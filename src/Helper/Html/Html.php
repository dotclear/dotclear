<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html;

/**
 * @class Html
 *
 * HTML utilities
 */
class Html
{
    /**
     * Array of regular expression for {@link absoluteURLs()}
     *
     * @var        array
     */
    public static $absolute_regs = [
        '/((?:action|cite|data|download|formaction|href|imagesrcset|itemid|itemprop|itemtype|ping|poster|src|srcset)=")(.*?)(")/msu',
    ];

    /**
     * HTML escape
     *
     * Replaces HTML special characters by entities.
     *
     * @param     string $str    String to escape
     *
     * @return    string
     */
    public static function escapeHTML(?string $str): string
    {
        return htmlspecialchars($str ?? '', ENT_COMPAT, 'UTF-8');
    }

    /**
     * Decode HTML entities
     *
     * Returns a string with all entities decoded.
     *
     * @param string        $str            String to protect
     * @param string|bool   $keep_special   Keep special characters: &gt; &lt; &amp;
     *
     * @return    string
     */
    public static function decodeEntities(?string $str, $keep_special = false): string
    {
        if ($keep_special) {
            $str = str_replace(
                ['&amp;', '&gt;', '&lt;'],
                ['&amp;amp;', '&amp;gt;', '&amp;lt;'],
                $str
            );
        }

        # Some extra replacements
        $extra = [
            '&apos;' => "'",
        ];

        $str = str_replace(array_keys($extra), array_values($extra), $str);

        return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Remove markup
     *
     * Removes every tags, comments, cdata from string
     *
     * @param string    $str        String to clean
     *
     * @return    string
     */
    public static function clean(?string $str): string
    {
        $str = strip_tags($str);

        return $str;
    }

    /**
     * Javascript escape
     *
     * Returns a protected JavaScript string
     *
     * @param string    $str        String to protect
     *
     * @return    string
     */
    public static function escapeJS(?string $str): string
    {
        $str = htmlspecialchars($str, ENT_NOQUOTES, 'UTF-8');
        $str = str_replace("'", "\'", $str);
        $str = str_replace('"', '\"', $str);

        return $str;
    }

    /**
     * URL escape
     *
     * Returns an escaped URL string for HTML content
     *
     * @param string    $str        String to escape
     *
     * @return    string
     */
    public static function escapeURL(?string $str): string
    {
        return str_replace('&', '&amp;', $str);
    }

    /**
     * URL sanitize
     *
     * Encode every parts between / in url
     *
     * @param string    $str        String to satinize
     *
     * @return    string
     */
    public static function sanitizeURL(?string $str): string
    {
        return str_replace('%2F', '/', rawurlencode((string) $str));
    }

    /**
     * Remove host in URL
     *
     * Removes host part in URL
     *
     * @param string    $url        URL to transform
     *
     * @return    string
     */
    public static function stripHostURL(?string $url): string
    {
        return preg_replace('|^[a-z]{3,}://.*?(/.*$)|', '$1', (string) $url);
    }

    /**
     * Set links to absolute ones
     *
     * Appends $root URL to URIs attributes in $str.
     *
     * @param string    $str        HTML to transform
     * @param string    $root       Base URL
     *
     * @return    string
     */
    public static function absoluteURLs(?string $str, ?string $root): string
    {
        $str = (string) $str;
        if ($root) {
            foreach (self::$absolute_regs as $pattern) {
                $str = preg_replace_callback(
                    $pattern,
                    function (array $matches) use ($root) {
                        $url = $matches[2];

                        $link = str_replace('%', '%%', $matches[1]) . '%s' . str_replace('%', '%%', $matches[3]);
                        $host = preg_replace('|^([a-z]{3,}://)(.*?)/(.*)$|', '$1$2', $root);

                        $parse = parse_url($matches[2]);
                        if (empty($parse['scheme'])) {
                            if (strpos($url, '//') === 0) {
                                // Nothing to do. Already an absolute URL.
                            } elseif (strpos($url, '/') === 0) {
                                // Beginning by a / return host + url
                                $url = $host . $url;
                            } elseif (strpos($url, '#') === 0) {
                                // Beginning by a # return root + hash
                                $url = $root . $url;
                            } elseif (preg_match('|/$|', $root)) {
                                // Root is ending by / return root + url
                                $url = $root . $url;
                            } else {
                                $url = dirname($root) . '/' . $url;
                            }
                        }

                        return sprintf($link, $url);
                    },
                    $str
                );
            }
        }

        return $str;
    }
}
