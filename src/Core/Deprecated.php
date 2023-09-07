<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Deprecated as HelperDeprecated;
use Dotclear\Interface\Core\LogInterface;
use Exception;
use Throwable;

/**
 * Deprecated logger handler.
 *
 * @since 2.26
 */
class Deprecated extends HelperDeprecated
{
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
     * Purge limit checked.
     *
     * @var     bool    $purged
     */
    private static bool $purged = false;

    /**
     * Get deprecated logs
     *
     * @param   mixed   $limit          Limit parameter
     * @param   bool    $count_only     Count only resultats
     *
     * @return  MetaRecord    The logs.
     */
    public static function get($limit, bool $count_only = false): MetaRecord
    {
        return App::log()->getLogs(['limit' => $limit, 'log_table' => self::DEPRECATED_LOG_TABLE], $count_only);
    }

    protected static function log(string $title, array $lines): void
    {
        // only log on DEV mode
        if (!defined('DC_DEV') || !DC_DEV) {
            return;
        }

        // to early to use core
        try {
            $log = App::log();
            if (!($log instanceof LogInterface)) {
                throw new Exception('too early');
            }
        } catch (Throwable) {
            parent::log($title, $lines);

            return;
        }

        self::purge();

        if (!empty($title)) {
            array_unshift($lines, $title);
        }

        // log deprecated to log table
        $cursor = $log->openLogCursor();
        $cursor->setField('log_msg', implode(self::DEPRECATED_LINE_SEPARATOR, $lines));
        $cursor->setField('log_table', self::DEPRECATED_LOG_TABLE);
        $cursor->setField('user_id', (defined('DC_CONTEXT_ADMIN')) ? App::auth()->userID() : 'unknown');
        $log->addLog($cursor);
    }

    /**
     * Purge deprecated logs.
     *
     * @param   bool    $all    Purge all deprecated logs
     */
    private static function purge(bool $all = false): void
    {
        // check once per page (and if a deprecated is thrown)
        if (self::$purged) {
            return;
        }
        self::$purged = true;

        // count deprecated logs
        $count = static::get(null, true)->f(0);
        $count = is_numeric($count) ? (int) $count : 0;

        // check logs limit and delete them if it's required
        if ($count > self::DEPRECATED_PURGE_LIMIT) {
            $sql = new DeleteStatement();
            $sql->from(App::con()->prefix() . App::log()::LOG_TABLE_NAME)
                ->where('log_table = ' . $sql->quote(self::DEPRECATED_LOG_TABLE));

            if (!$all) {
                $sql_dt = new SelectStatement();
                $rs     = $sql_dt->from(App::con()->prefix() . App::log()::LOG_TABLE_NAME)
                    ->column('log_dt')
                    ->where('log_table = ' . $sql_dt->quote(self::DEPRECATED_LOG_TABLE))
                    ->order('log_dt DESC')
                    ->limit([self::DEPRECATED_PURGE_LIMIT, 1])
                    ->select();

                if (!is_null($rs) && !$rs->isEmpty()) {
                    $sql->and('log_dt < ' . $sql_dt->quote($rs->f('log_dt')));
                }
                unset($sql_dt);
            }

            $sql->run();
            unset($sql);
        }
    }
}
