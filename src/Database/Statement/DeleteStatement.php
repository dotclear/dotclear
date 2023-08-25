<?php
/**
 * @class DeleteStatement
 *
 * Delete Statement : small utility to build delete queries
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Database\Statement;

use dcCore;

class DeleteStatement extends SqlStatement
{
    /**
     * Returns the delete statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeDeleteStatement -- SqlStatement
        if (class_exists('dcCore')) {
            dcCore::app()->behavior->callBehavior('coreBeforeDeleteStatement', $this);
        }

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL DELETE requires a FROM source'), E_USER_ERROR);

            return '';  // @phpstan-ignore-line
        }

        // Query
        $query = 'DELETE ';

        // Table
        $query .= 'FROM ' . $this->from[0] . ' ';

        // Where clause(s)
        if (count($this->where)) {
            $query .= 'WHERE ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            if (!count($this->where)) {
                // Hack to cope with the operator included in top of each condition
                $query .= 'WHERE ' . ($this->syntax === 'sqlite' ? '1' : 'TRUE') . ' ';
            }
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertDeleteStatement -- SqlStatement, string
        if (class_exists('dcCore')) {
            dcCore::app()->behavior->callBehavior('coreAfterDeleteStatement', $this, $query);
        }

        return $query;
    }

    /**
     * Run the SQL select query and return result
     *
     * @return     bool
     */
    public function delete(): bool
    {
        if ($this->con && ($sql = $this->statement())) {
            return $this->con->execute($sql);
        }

        return false;
    }

    /**
     * delete() alias
     *
     * @return     bool
     */
    public function run(): bool
    {
        return $this->delete();
    }
}
