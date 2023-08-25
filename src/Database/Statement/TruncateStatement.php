<?php
/**
 * @class TruncateStatement
 *
 * Truncate Statement : small utility to build truncate queries
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Database\Statement;

use dcCore;

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
        if (class_exists('dcCore')) {
            dcCore::app()->behavior->callBehavior('coreBeforeTruncateStatement', $this);
        }

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL TRUNCATE TABLE requires a FROM source'), E_USER_ERROR);

            return '';  // @phpstan-ignore-line
        }

        // Query
        $query = 'TRUNCATE ';

        // Reference
        $query .= 'TABLE ' . $this->from[0] . ' ';

        $query = trim($query);

        # --BEHAVIOR-- coreAfertTruncateStatement -- SqlStatement, string
        if (class_exists('dcCore')) {
            dcCore::app()->behavior->callBehavior('coreAfterTruncateStatement', $this, $query);
        }

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
