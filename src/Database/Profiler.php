<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

/**
 * @class Profiler
 *
 * Database queries profiler (debug purpose)
 *
 * Collects, when enabled, every query sent to database handlers: number of calls,
 * duration, number of rows and the place it comes from.
 *
 * @since 2.41
 */
class Profiler
{
    /**
     * Maximum number of distinct queries to keep (counters are not limited)
     *
     * @var int MAX_QUERIES
     */
    public const MAX_QUERIES = 500;

    /**
     * Maximum depth of the backtrace used to find where a query comes from
     *
     * @var int MAX_DEPTH
     */
    public const MAX_DEPTH = 25;

    private static bool $enabled = false;

    /**
     * Number of queries
     */
    private static int $count = 0;

    /**
     * Cumulated queries duration (in seconds)
     */
    private static float $time = 0.0;

    /**
     * Collected queries, by SQL
     *
     * @var array<string, array{sql: string, count: int, time: float, rows: int, caller: string, origin: string}>
     */
    private static array $queries = [];

    /**
     * Enable (or disable) the profiler.
     */
    public static function enable(bool $enable = true): void
    {
        self::$enabled = $enable;
    }

    /**
     * Check if the profiler is enabled.
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Forget collected queries.
     */
    public static function reset(): void
    {
        self::$count   = 0;
        self::$time    = 0.0;
        self::$queries = [];
    }

    /**
     * Start a query measure.
     *
     * @return  float   The start time (0 if profiler is disabled)
     */
    public static function start(): float
    {
        return self::$enabled ? microtime(true) : 0.0;
    }

    /**
     * Stop a query measure.
     *
     * @param   float   $start  The start time returned by start()
     * @param   string  $sql    The query
     * @param   int     $rows   The number of rows
     */
    public static function stop(float $start, string $sql, int $rows = 0): void
    {
        if (!self::$enabled || $start <= 0.0) {
            return;
        }

        $time = microtime(true) - $start;

        ++self::$count;
        self::$time += $time;

        if (isset(self::$queries[$sql])) {
            ++self::$queries[$sql]['count'];
            self::$queries[$sql]['time'] += $time;
            self::$queries[$sql]['rows'] += $rows;

            return;
        }

        if (count(self::$queries) >= self::MAX_QUERIES) {
            return;
        }

        [$caller, $origin] = self::trace();

        self::$queries[$sql] = [
            'sql'    => $sql,
            'count'  => 1,
            'time'   => $time,
            'rows'   => $rows,
            'caller' => $caller,
            'origin' => $origin,
        ];
    }

    /**
     * Get number of queries.
     */
    public static function getCount(): int
    {
        return self::$count;
    }

    /**
     * Get cumulated queries duration (in seconds).
     */
    public static function getTime(): float
    {
        return self::$time;
    }

    /**
     * Get number of queries already done during the same request.
     */
    public static function getDuplicatesCount(): int
    {
        $count = 0;
        foreach (self::$queries as $query) {
            $count += $query['count'] - 1;
        }

        return $count;
    }

    /**
     * Get collected queries, slowest first.
     *
     * Each query contains its SQL, the number of calls, the cumulated duration (in seconds),
     * the cumulated number of rows, the place (file:line) of the first call outside
     * database layer and, if any, the place of the first call outside Dotclear core
     * (a plugin or a theme).
     *
     * @return  list<array{sql: string, count: int, time: float, rows: int, caller: string, origin: string}>
     */
    public static function getQueries(): array
    {
        $queries = array_values(self::$queries);
        usort($queries, fn (array $a, array $b): int => $b['time'] <=> $a['time']);

        return $queries;
    }

    /**
     * Find where the current query comes from.
     *
     * @return  array{0: string, 1: string}     The caller and the origin
     */
    private static function trace(): array
    {
        $core = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $skip = [
            __DIR__ . DIRECTORY_SEPARATOR,
            $core . 'Schema' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR,
        ];

        $caller = '';
        $origin = '';
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::MAX_DEPTH) as $frame) {
            $file = $frame['file'] ?? '';
            if ($file === '') {
                continue;
            }

            foreach ($skip as $dir) {
                if (str_starts_with($file, $dir)) {
                    continue 2;
                }
            }

            $place = (str_starts_with($file, $root) ? substr($file, strlen($root)) : $file) . ':' . ($frame['line'] ?? 0);
            if ($caller === '') {
                $caller = $place;
            }

            if (!str_starts_with($file, $core)) {
                // Not a core file, keep it if it is not an entry point (index.php, admin/index.php, ...)
                if ($place !== $caller && dirname($file) . DIRECTORY_SEPARATOR !== $root && !str_starts_with($file, $root . 'admin' . DIRECTORY_SEPARATOR)) {
                    $origin = $place;
                }

                break;
            }
        }

        return [$caller, $origin];
    }
}
