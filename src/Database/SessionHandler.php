<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Interface\Core\ConnectionInterface;
use SessionHandlerInterface;

/**
 * @class SessionHandler
 *
 * Database Session Handler
 *
 * This class allows you to handle session data in database.
 */
class SessionHandler implements SessionHandlerInterface
{
    /**
     * Constructor
     *
     * This method creates an instance of Session class.
     *
     * @param ConnectionInterface   $con               AbstractHandler inherited database instance
     * @param string                $table             Table name
     * @param string                $ttl               TTL (default -120 minutes)
     */
    public function __construct(
        private readonly ConnectionInterface $con,
        private readonly string $table,
        private string $ttl
    ) {
        if (!str_starts_with(trim($this->ttl), '-')) {
            // We will use negative session TTL
            $this->ttl = '-' . trim($this->ttl);
        }
    }

    /**
     * Session handler callback called on session open
     *
     * @param      string  $path   The save path
     * @param      string  $name   The session name
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Session handler callback called on session close
     */
    public function close(): bool
    {
        $this->gc(0);

        return true;
    }

    /**
     * Session handler callback called on session read
     *
     * @param      string  $ses_id  The session identifier
     */
    public function read(string $ses_id): string
    {
        $sql = new SelectStatement($this->con, $this->con->syntax());
        $sql
            ->field('ses_value')
            ->from($this->table)
            ->where('ses_id = ' . $sql->quote($this->checkID($ses_id)))
        ;

        $rs = $sql->select();
        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            return '';
        }

        return $rs->f('ses_value');
    }

    /**
     * Session handler callback called on session write
     *
     * @param      string  $ses_id  The session identifier
     * @param      string  $data    The data
     */
    public function write(string $ses_id, string $data): bool
    {
        $sql = new SelectStatement($this->con, $this->con->syntax());
        $sql
            ->field('ses_id')
            ->from($this->table)
            ->where('ses_id = ' . $sql->quote($this->checkID($ses_id)))
        ;

        $rs = $sql->select();
        if ($rs instanceof MetaRecord) {
            $cur            = $this->con->openCursor($this->table);
            $cur->ses_time  = (string) time();
            $cur->ses_value = $data;

            if (!$rs->isEmpty()) {
                $sqlUpdate = new UpdateStatement($this->con, $this->con->syntax());
                $sqlUpdate
                    ->where('ses_id = ' . $sqlUpdate->quote($this->checkID($ses_id)))
                    ->update($cur)
                ;
            } else {
                $cur->ses_id    = $this->checkID($ses_id);
                $cur->ses_start = (string) time();

                $cur->insert();
            }

            return true;
        }

        return false;
    }

    /**
     * Session handler callback called on session destroy
     *
     * @param      string  $ses_id  The session identifier
     */
    public function destroy(string $ses_id): bool
    {
        $sql = new DeleteStatement($this->con, $this->con->syntax());
        $sql
            ->from($this->table)
            ->where('ses_id = ' . $sql->quote($this->checkID($ses_id)))
        ;
        $sql->delete();

        $this->optimize();

        return true;
    }

    /**
     * Session handler callback called on session garbage collect
     *
     * @return     int|false
     */
    public function gc(int $max_lifetime): int|false
    {
        $ses_life = strtotime($this->ttl);

        $sql = new DeleteStatement($this->con, $this->con->syntax());
        $sql
            ->from($this->table)
            ->where('ses_time < ' . $ses_life)
        ;
        $sql->delete();

        if ($this->con->changes() > 0) {
            $this->optimize();
        }

        return (int) true;
    }

    /**
     * Optimize the session table
     */
    private function optimize(): void
    {
        $this->con->vacuum($this->table);
    }

    /**
     * Check a session id
     *
     * @param      string  $id     The identifier
     */
    private function checkID(string $id): string
    {
        return preg_match('/^([0-9a-f]{40})$/i', $id) ? $id : '';
    }
}
