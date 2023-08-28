<?php
/**
 * @class JoinStatement
 *
 * Join (sub)Statement : small utility to build join query fragments
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Database\Statement;

use dcCore;
use Dotclear\Core\Core;

class JoinStatement extends SqlStatement
{
    protected $type;

    /**
     * Constructs a new instance.
     *
     * @param      mixed         $con     The DB handle
     * @param      null|string   $syntax  The syntax
     */
    public function __construct($con = null, ?string $syntax = null)
    {
        $this->type = null;

        parent::__construct($con, $syntax);
    }

    /**
     * Defines the type for join
     *
     * @param string $type
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
        if (class_exists('dcCore')) {
            Core::behavior()->callBehavior('coreBeforeJoinStatement', $this);
        }

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL JOIN requires a FROM source'), E_USER_ERROR);

            return '';  // @phpstan-ignore-line
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
        if (count($this->where)) {
            $query .= 'ON ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertJoinStatement -- SqlStatement, string
        if (class_exists('dcCore')) {
            Core::behavior()->callBehavior('coreAfterJoinStatement', $this, $query);
        }

        return $query;
    }
}
