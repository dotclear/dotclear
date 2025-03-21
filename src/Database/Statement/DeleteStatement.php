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
 * @class DeleteStatement
 *
 * Delete Statement : small utility to build delete queries
 */
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
        App::behavior()->callBehavior('coreBeforeDeleteStatement', $this);

        // Check if source given
        if ($this->from === []) {
            trigger_error(__('SQL DELETE requires a FROM source'), E_USER_WARNING);
        }

        // Query
        $query = 'DELETE ';

        // Table
        $query .= 'FROM ' . $this->from[0] . ' ';

        // Where clause(s)
        if ($this->where !== []) {
            $query .= 'WHERE ' . implode(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if ($this->cond !== []) {
            if ($this->where === []) {
                // Hack to cope with the operator included in top of each condition
                $query .= 'WHERE ' . ($this->syntax === 'sqlite' ? '1' : 'TRUE') . ' ';
            }
            $query .= implode(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if ($this->sql !== []) {
            $query .= implode(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertDeleteStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterDeleteStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL select query and return result
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
     */
    public function run(): bool
    {
        return $this->delete();
    }
}
