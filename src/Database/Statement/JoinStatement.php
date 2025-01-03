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
 * @class JoinStatement
 *
 * Join (sub)Statement : small utility to build join query fragments
 */
class JoinStatement extends SqlStatement
{
    /**
     * @var ?string
     */
    protected $type;

    /**
     * Constructs a new instance.
     *
     * @param      mixed         $con     The DB handle
     * @param      null|string   $syntax  The syntax
     */
    public function __construct($con = null, ?string $syntax = null)
    {
        parent::__construct($con, $syntax);
    }

    /**
     * Defines the type for join
     *
     *
     * @return self instance, enabling to chain calls
     */
    public function type(string $type = ''): JoinStatement
    {
        $this->type = strtoupper($type);

        return $this;
    }

    /**
     * Defines LEFT join type
     *
     * @return self instance, enabling to chain calls
     */
    public function left(): JoinStatement
    {
        return $this->type('LEFT');
    }

    /**
     * Defines RIGHT join type
     *
     * @return self instance, enabling to chain calls
     */
    public function right(): JoinStatement
    {
        return $this->type('RIGHT');
    }

    /**
     * Defines INNER join type
     *
     * @return self instance, enabling to chain calls
     */
    public function inner(): JoinStatement
    {
        return $this->type('INNER');
    }

    /**
     * Returns the join fragment
     *
     * @return string the fragment
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeJoinStatement -- SqlStatement
        App::behavior()->callBehavior('coreBeforeJoinStatement', $this);

        // Check if source given
        if ($this->from === []) {
            trigger_error(__('SQL JOIN requires a FROM source'), E_USER_WARNING);
        }

        // Query
        $query = 'JOIN ';

        if ($this->type) {
            // LEFT, RIGHT, …
            $query = $this->type . ' ' . $query;
        }

        // Table
        $query .= $this->from[0] . ' ';

        // Where clause(s)
        if ($this->where !== []) {
            $query .= 'ON ' . implode(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if ($this->cond !== []) {
            $query .= implode(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if ($this->sql !== []) {
            $query .= implode(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertJoinStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterJoinStatement', $this, $query);

        return $query;
    }
}
