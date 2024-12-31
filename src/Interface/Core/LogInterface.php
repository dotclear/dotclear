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
 * @brief   Core log handler interface.
 *
 * @since   2.28
 */
interface LogInterface
{
    /**
     * The Log database table name.
     *
     * @var    string   LOG_TABLE_NAME
     */
    public const LOG_TABLE_NAME = 'log';

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The log database table cursor
     */
    public function openLogCursor(): Cursor;

    /**
     * Retrieves logs.
     *
     * <b>$params</b> is an array taking the following optionnal parameters:
     *
     * - blog_id: Get logs belonging to given blog ID
     * - user_id: Get logs belonging to given user ID
     * - log_ip: Get logs belonging to given IP address
     * - log_table: Get logs belonging to given log table
     * - order: Order of results (default "ORDER BY log_dt DESC")
     * - limit: Limit parameter
     *
     * @param   array<string, mixed>    $params         The parameters
     * @param   bool                    $count_only     Count only resultats
     *
     * @return  MetaRecord  The logs.
     */
    public function getLogs(array $params = [], bool $count_only = false): MetaRecord;

    /**
     * Creates a new log. Takes a Cursor as input and returns the new log ID.
     *
     * @throws  BadRequestException
     *
     * @param   Cursor  $cur    The current
     */
    public function addLog(Cursor $cur): int;

    /**
     * Deletes a log.
     *
     * @param   int     $id     The identifier
     */
    public function delLog(int $id): void;

    /**
     * Deletes log(s).
     *
     * This methods keep various $id for backward compatibility.
     * Should be used only for array of ids
     *
     * @param   null|int|array<int,string|int>  $id     The identifier(s)
     * @param   bool                            $all    Remove all logs
     */
    public function delLogs($id, bool $all = false): void;

    /**
     * Deletes all log.
     */
    public function delAllLogs(): void;
}
