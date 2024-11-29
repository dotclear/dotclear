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
 * @class DropStatement
 *
 * Drop Statement : small utility to build drop queries
 */
class DropStatement extends SqlStatement
{
    /**
     * Returns the drop statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeDropStatement -- SqlStatement
        App::behavior()->callBehavior('coreBeforeDropStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL DROP TABLE requires a FROM source'), E_USER_WARNING);
        }

        // Query
        $query = 'DROP ';

        // Reference
        $query .= 'TABLE ' . $this->from[0] . ' ';

        $query = trim($query);

        # --BEHAVIOR-- coreAfertDropStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterDropStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL select query and return result
     *
     * @return     bool
     */
    public function drop(): bool
    {
        if ($this->con && ($sql = $this->statement())) {
            return $this->con->execute($sql);
        }

        return false;
    }

    /**
     * drop() alias
     *
     * @return     bool
     */
    public function run(): bool
    {
        return $this->drop();
    }
}
