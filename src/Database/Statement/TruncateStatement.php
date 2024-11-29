<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database\Statement;

use Dotclear\App;

/**
 * @class TruncateStatement
 *
 * Truncate Statement : small utility to build truncate queries
 */
class TruncateStatement extends SqlStatement
{
    /**
     * Returns the truncate statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeTruncateStatement -- SqlStatement
        App::behavior()->callBehavior('coreBeforeTruncateStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL TRUNCATE TABLE requires a FROM source'), E_USER_WARNING);
        }

        // Query
        $query = 'TRUNCATE ';

        // Reference
        $query .= 'TABLE ' . $this->from[0] . ' ';

        $query = trim($query);

        # --BEHAVIOR-- coreAfertTruncateStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterTruncateStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL select query and return result
     *
     * @return     bool
     */
    public function truncate(): bool
    {
        if ($this->con && ($sql = $this->statement())) {
            return $this->con->execute($sql);
        }

        return false;
    }

    /**
     * truncate() alias
     *
     * @return     bool
     */
    public function run(): bool
    {
        return $this->truncate();
    }
}
