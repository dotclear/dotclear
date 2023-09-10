<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\MetaRecord;

/**
 * Deprecated logger handler.
 *
 * @since 2.28
 */
interface DeprecatedInterface
{
    /**
     * Error level.
     *
     * @var     int  DEPRECATED_ERROR_LEVEL
     */
    public const DEPRECATED_ERROR_LEVEL = E_USER_DEPRECATED;

    /**
     * The log table name for deprecated
     *
     * @var     string  DEPRECATED_LOG_TABLE
     */
    public const DEPRECATED_LOG_TABLE = 'deprecated';

    /**
     * Logs limit in table.
     *
     * @var     int     DEPRECATED_PURGE_LIMIT
     */
    public const DEPRECATED_PURGE_LIMIT = 200;

    /**
     * The trace lines separator.
     *
     * @var     string  DEPRECATED_LINE_SEPARATOR
     */
    public const DEPRECATED_LINE_SEPARATOR = "\n";

    /**
     * Set a deprecated log.
     *
     * @param   null|string     $replacement    Function to use in replacement of deprecated one
     * @param   null|string     $since          Version from which this is deprecated
     * @param   null|string     $upto           Version where this is removed
     */
    public static function set(?string $replacement = null, ?string $since = null, ?string $upto = null): void;

    /**
     * Get deprecated logs
     *
     * @param   mixed   $limit          Limit parameter
     * @param   bool    $count_only     Count only resultats
     *
     * @return  MetaRecord    The logs.
     */
    public static function get($limit, bool $count_only = false): MetaRecord;
}
