<?php
/**
 * @brief Deprecated logger class
 *
 * @since 2.26
 * 
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Helper\Deprecated;

class dcDeprecated extends Deprecated
{
    /** @var    string  The log table name for deprecated */
    public const DEPRECATED_LOG_TABLE = 'dcDeprecated';

    /** @var    int     Logs limit in table */
    public const DEPRECATED_PURGE_LIMIT = 100;

    /** @var    string  The trace lines separator */
    public const DEPRECATED_LINE_SEPARATOR = "\n";

    /** @var    bool    Purge limit checked */
    private static bool $purged = false;

    /**
     * Get deprecated logs
     * 
     * @param   mixed   $limit          Limit parameter
     * @param   bool    $count_only     Count only resultats
     * 
     * @return  dcRecord    The logs.
     */
    public static function get(mixed $limit, bool $count_only = false): dcRecord
    {
        return dcCore::app()->log->getLogs(['limit' => $limit, 'log_table' => self::DEPRECATED_LOG_TABLE], $count_only);
    }

    protected static function log(string $title, array $lines): void
    {
        // only log on DEV mode
        if (!defined('DC_DEV') || !DC_DEV) {
            return;
        }

        // to early to use core
        try {
            $log = dcCore::app()->log;
            if (!($log instanceof dcLog)) {
                throw new Exception('too early');
            }
        } catch (Throwable $e) {
                parent::log($title, $lines);
                return;
        }

        self::purge();

        if (!empty($title)) {
            array_unshift($lines, $title);
        }

        // log deprecated to log table
        $cursor = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME);
        $cursor->setField('log_msg', implode(self::DEPRECATED_LINE_SEPARATOR, $lines));
        $cursor->setField('log_table', self::DEPRECATED_LOG_TABLE);
        $cursor->setField('user_id', dcCore::app()->auth->userID());
        $log->addLog($cursor);
    }

    /**
     * Purge deprecated logs
     */
    private static function purge(): void
    {
        // check once per page (and if a deprecated is thrown)
        if (self::$purged){
            return;
        }
        self::$purged = true;

        // check logs limit and delete them if it's required
        if ((int) static::get(null, true)->f(0) > self::DEPRECATED_PURGE_LIMIT) {
            $sql = new DeleteStatement();
            $sql->from(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME)
                ->where('log_table = ' . $sql->quote(self::DEPRECATED_LOG_TABLE))
                ->run();
            unset($sql);
        }
    }
}
