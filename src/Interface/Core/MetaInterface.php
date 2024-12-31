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
use Dotclear\Database\Statement\SelectStatement;

/**
 * @brief   Meta handler interface.
 *
 * @since   2.28
 */
interface MetaInterface
{
    /**
     * The Meta database table name.
     *
     * @var    string   META_TABLE_NAME
     */
    public const META_TABLE_NAME = 'meta';

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The meta database table cursor
     */
    public function openMetaCursor(): Cursor;

    /**
     * Splits up comma-separated values into an array of unique, URL-proof metadata values.
     *
     * @param   string  $str    Comma-separated metadata
     *
     * @return  array<int,string>   The array of sanitized metadata
     */
    public function splitMetaValues(string $str): array;

    /**
     * Make a metadata ID URL-proof.
     *
     * @param      string  $str    The metadata ID
     */
    public static function sanitizeMetaID(string $str): string;

    /**
     * Converts serialized metadata (for instance in dc_post post_meta) into a meta array.
     *
     * @param   string  $str     The serialized metadata
     *
     * @return  array<string,array<string,string>>      The meta array.
     */
    public function getMetaArray(?string $str): array;

    /**
     * Converts serialized metadata (for instance in dc_post post_meta)
     * into a comma-separated meta list for a given type.
     *
     * @param   string  $str    The serialized metadata
     * @param   string  $type   The meta type to retrieve metaIDs from
     *
     * @return  string  The comma-separated list of meta.
     */
    public function getMetaStr(?string $str, string $type): string;

    /**
     * Converts serialized metadata (for instance in dc_post post_meta)
     * into a "fetchable" metadata MetaRecord.
     *
     * @param   string  $str    The serialized metadata
     * @param   string  $type   The meta type to retrieve metaIDs from
     */
    public function getMetaRecordset(?string $str, string $type): MetaRecord;

    /**
     * Retrieves posts corresponding to given meta criteria.
     * <b>$params</b> is an array taking the following optional parameters:
     * - meta_id : get posts having meta id
     * - meta_type : get posts having meta type
     *
     * @param   array<string, mixed>    $params         The parameters
     * @param   bool                    $count_only     Only count results
     * @param   SelectStatement|null    $ext_sql        Optional SqlStatement instance
     *
     * @return  MetaRecord  The resulting posts record.
     */
    public function getPostsByMeta(array $params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord;

    /**
     * Retrieves comments corresponding to given meta criteria.
     * <b>$params</b> is an array taking the following optional parameters:
     * - meta_id : get posts having meta id
     * - meta_type : get posts having meta type
     *
     * @param   array<string, mixed>    $params         The parameters
     * @param   bool                    $count_only     Only count results
     * @param   SelectStatement|null    $ext_sql        Optional SqlStatement instance
     *
     * @return  MetaRecord  The resulting comments record.
     */
    public function getCommentsByMeta(array $params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord;

    /**
     * Generic-purpose metadata retrieval : gets metadatas according to given
     * criteria. <b>$params</b> is an array taking the following
     * optionnal parameters:
     *
     * - type: get metas having the given type
     * - meta_id: if not null, get metas having the given id
     * - post_id: get metas for the given post id
     * - limit: number of max fetched metas
     * - order: results order (default : posts count DESC)
     *
     * @param   array<string, mixed>    $params         The parameters
     * @param   bool                    $count_only     Only counts results
     * @param   SelectStatement|null    $ext_sql        Optional SqlStatement instance
     */
    public function getMetadata(array $params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord;

    /**
     * Computes statistics from a metadata recordset.
     * Each record gets enriched with lowercase name, percent and roundpercent columns
     *
     * @param      MetaRecord  $rs     The metadata recordset
     */
    public function computeMetaStats(MetaRecord $rs): MetaRecord;

    /**
     * Adds a metadata to a post.
     *
     * @param   int|string  $post_id    The post identifier
     * @param   string      $type       The type
     * @param   string      $value      The value
     */
    public function setPostMeta(int|string $post_id, ?string $type, ?string $value): void;

    /**
     * Removes metadata from a post.
     *
     * @param   int|string  $post_id    The post identifier
     * @param   string      $type       The meta type (if null, delete all types)
     * @param   string      $meta_id    The meta identifier (if null, delete all values)
     */
    public function delPostMeta(int|string $post_id, ?string $type = null, ?string $meta_id = null): void;

    /**
     * Mass updates metadata for a given post_type.
     *
     * @param   string  $meta_id        The old meta value
     * @param   string  $new_meta_id    The new meta value
     * @param   string  $type           The type (if null, select all types)
     * @param   string  $post_type  The post type (if null, select all types)
     *
     * @return  bool    true if at least 1 post has been impacted
     */
    public function updateMeta(string $meta_id, string $new_meta_id, ?string $type = null, ?string $post_type = null): bool;

    /**
     * Mass delete metadata for a given post_type.
     *
     * @param   string  $meta_id    The meta identifier
     * @param   string  $type       The meta type (if null, select all types)
     * @param   string  $post_type  The post type (if null, select all types)
     *
     * @return  array<int,int>  The list of impacted post_ids
     */
    public function delMeta(string $meta_id, ?string $type = null, ?string $post_type = null): array;
}
