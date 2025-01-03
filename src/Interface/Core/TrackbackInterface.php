<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Exception\BadRequestException;

/**
 * @brief   Trackbacks/Pingbacks sender and server interface.
 *
 * Sends and receives trackbacks/pingbacks.
 * Also handles trackbacks/pingbacks auto discovery.
 *
 * @since   2.28
 */
interface TrackbackInterface
{
    /**
     * Trackbacks table name.
     *
     * @var     string  PING_TABLE_NAME
     */
    public const PING_TABLE_NAME = 'ping';

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The ping database table cursor
     */
    public function openTrackbackCursor(): Cursor;

    /// @name Send
    //@{
    /**
     * Get all pings sent for a given post.
     *
     * @param   int     $post_id    The post identifier
     *
     * @return  MetaRecord  The post pings.
     */
    public function getPostPings(int $post_id): MetaRecord;

    /**
     * Sends a ping to given <var>$url</var>.
     *
     * @param   string  $url            The url
     * @param   int     $post_id        The post identifier
     * @param   string  $post_title     The post title
     * @param   string  $post_excerpt   The post excerpt
     * @param   string  $post_url       The post url
     *
     * @throws  BadRequestException
     *
     * @return  bool    false if error
     */
    public function ping(string $url, int $post_id, string $post_title, string $post_excerpt, string $post_url): bool;
    //@}

    /// @name Receive
    //@{
    /**
     * Receives a trackback and insert it as a comment of given post.
     *
     * @param   int     $post_id  The post identifier
     */
    public function receiveTrackback(int $post_id): void;

    /**
     * Receives a pingback and insert it as a comment of given post.
     *
     * @param   string  $from_url   Source URL
     * @param   string  $to_url     Target URL
     *
     * @throws  BadRequestException
     */
    public function receivePingback(string $from_url, string $to_url): string;

    /**
     * Receives a webmention and insert it as a comment of given post.
     *
     * NB: plugin Fair Trackback check source content to find url.
     *
     * @throws  BadRequestException
     */
    public function receiveWebmention(): void;

    //@}

    /// @name Discover
    //@{
    /**
     * DIscover trackbacks.
     *
     * Returns an array containing all discovered trackbacks URLs in <var>$text</var>.
     *
     * @param   string  $text   The text
     *
     * @return  array<string>
     */
    public function discover(string $text): array;

    //@}

    /**
     * URL helper.
     *
     * @param   string  $from_url   The from url
     * @param   string  $to_url     To url
     *
     * @throws  BadRequestException
     */
    public static function checkURLs(string $from_url, string $to_url): void;
}
