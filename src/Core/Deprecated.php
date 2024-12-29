<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Interface\Core\DeprecatedInterface;

/**
 * @brief   Deprecated logger handler.
 *
 * @since   2.26
 */
class Deprecated implements DeprecatedInterface
{
    /**
     * Purge limit checked.
     */
    private static bool $purged = false;

    public static function set(?string $replacement = null, ?string $since = null, ?string $upto = null): void
    {
        // too early to use log
        if (!App::blog()->isDefined()) {
            return;
        }

        // get backtrace
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // remove call to this method
        array_shift($traces);

        // clean trace
        $title = '';
        $lines = [];
        foreach ($traces as $line) {
            $class = empty($line['class']) ? '' : $line['class'] . '::';
            $func  = empty($line['function']) ? '' : $line['function'] . '() ';
            $file  = empty($line['file']) ? '' : $line['file'] . ':';
            $line  = empty($line['line']) ? '' : $line['line'];

            if ($replacement !== null && $lines === []) {
                $title = $class . $func . ' is deprecated' .
                    ($since !== null ? ' since version ' . $since : '') .
                    ($upto !== null ? ' and wil be removed in version ' . $upto : '') .
                    ($replacement === '' ? '' : ', use ' . $replacement . ' as replacement') .
                    '.';
            }

            $lines[] = $class . $func . $file . $line;
        }

        // only log on DEV mode
        if (!App::config()->devMode()) {
            return;
        }

        self::purge();

        if ($title !== '') {
            array_unshift($lines, $title);
        }

        // log deprecated to log table
        $log    = App::log();
        $cursor = $log->openLogCursor();
        $cursor->setField('log_msg', implode(self::DEPRECATED_LINE_SEPARATOR, $lines));
        $cursor->setField('log_table', self::DEPRECATED_LOG_TABLE);
        $cursor->setField('user_id', App::task()->checkContext('BACKEND') ? App::auth()->userID() : 'unknown');
        $log->addLog($cursor);
    }

    public static function get($limit, bool $count_only = false): MetaRecord
    {
        return App::log()->getLogs(['limit' => $limit, 'log_table' => self::DEPRECATED_LOG_TABLE], $count_only);
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
        $count = self::get(null, true)->f(0);
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
