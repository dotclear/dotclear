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
use Dotclear\Exception\DatabaseException;

/**
 * @class SqlStatement
 *
 * SQL query statement builder
 */
class SqlStatement
{
    // Constants

    /**
     * Use AS for aliases anywhere (if true) else only for SQLite syntax (if false)
     *
     * @see self::alias(), self::as(), self::count(), self::avg(), self::min(), self::max(), self::sum() methods
     *
     * @var        bool
     */
    protected const VERBOSE_SQL_ALIAS = false;

    // Properties

    /**
     * DB handle
     *
     * @var     null|\Dotclear\Interface\Core\ConnectionInterface
     */
    protected $con;

    /**
     * DB SQL syntax
     *
     * should be 'mysql', 'postgresql' or 'sqlite'
     */
    protected string $syntax;

    /**
     * Keyword use between name and its alias
     */
    protected string $_AS = ' ';

    /**
     * Stack of fields
     *
     * @var        array<string>
     */
    protected array $columns = [];

    /**
     * Stack of from clauses
     *
     * @var        array<string>
     */
    protected array $from = [];

    /**
     * Stack of where clauses
     *
     * @var        array<string>
     */
    protected array $where = [];

    /**
     * Additionnal stack of where clauses
     *
     * @var        array<string>
     */
    protected array $cond = [];

    /**
     * Stack of generic SQL clauses
     *
     * @var        array<string>
     */
    protected array $sql = [];

    /**
     * Constructs a new instance.
     *
     * @param      mixed         $con     The DB handle
     * @param      null|string   $syntax  The syntax
     */
    public function __construct($con = null, ?string $syntax = null)
    {
        $this->con    = $con    ?? App::con();
        $this->syntax = $syntax ?? ($con ? $con->syntax() : App::con()->syntax());

        /* @phpstan-ignore-next-line */
        $this->_AS = ($this->syntax === 'sqlite' || self::VERBOSE_SQL_ALIAS ? ' AS ' : ' ');
    }

    /**
     * Magic getter method
     *
     * @param      string  $property  The property
     *
     * @return     mixed   property value if property exists
     */
    #[\ReturnTypeWillChange]
    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        trigger_error('Unknown property ' . $property, E_USER_WARNING);

        return null;
    }

    /**
     * Magic setter method
     *
     * @param       string  $property  The property
     * @param       mixed   $value     The value
     *
     * @return      static    self instance, enabling to chain calls
     */
    #[\ReturnTypeWillChange]
    public function __set(string $property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            trigger_error('Unknown property ' . $property, E_USER_WARNING);
        }

        return $this;   // @phpstan-ignore-line
    }

    /**
     * Magic isset method
     *
     * @param      string  $property  The property
     */
    public function __isset(string $property): bool
    {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        }

        return false;
    }

    /**
     * Magic unset method
     *
     * @param      string  $property  The property
     */
    public function __unset(string $property)
    {
        if (property_exists($this, $property)) {
            unset($this->$property);
        }
    }

    /**
     * Magic invoke method
     *
     * Alias of statement()
     */
    public function __invoke(): string
    {
        return $this->statement();
    }

    /**
     * Returns a SQL dummy statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        return '';
    }

    /**
     * Adds column(s)
     *
     * @param mixed     $c      the column(s)
     * @param boolean   $reset  reset previous column(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function columns($c, bool $reset = false): static
    {
        if ($reset) {
            $this->columns = [];
        }
        if (is_array($c)) {
            $this->columns = [...$this->columns, ...$c];
        } elseif (!is_null($c)) {
            $this->columns[] = $c;
        }

        return $this;
    }

    /**
     * columns() alias
     *
     * @param mixed     $c      the column(s)
     * @param boolean   $reset  reset previous column(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function fields($c, bool $reset = false): static
    {
        return $this->columns($c, $reset);
    }

    /**
     * columns() alias
     *
     * @param      mixed    $c      the column(s)
     * @param      boolean  $reset  reset previous column(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function column($c, bool $reset = false): static
    {
        return $this->columns($c, $reset);
    }

    /**
     * column() alias
     *
     * @param      mixed    $c      the column(s)
     * @param      boolean  $reset  reset previous column(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function field($c, bool $reset = false): static
    {
        return $this->column($c, $reset);
    }

    /**
     * Adds FROM clause(s)
     *
     * @param mixed     $c      the from clause(s)
     * @param boolean   $reset  reset previous from(s) first
     * @param boolean   $first  put the from clause(s) at top of list
     *
     * @return static    self instance, enabling to chain calls
     */
    public function from($c, bool $reset = false, bool $first = false): static
    {
        $filter = fn ($v): string => trim(ltrim((string) $v, ',')); // Remove comma on beginning of clause(s)

        if ($reset) {
            $this->from = [];
        }
        if (is_array($c)) {
            $c          = array_map($filter, $c);   // Cope with legacy code
            $this->from = $first ? [...$c, ...$this->from] : [...$this->from, ...$c];
        } elseif (!is_null($c)) {
            $c = $filter($c);   // Cope with legacy code
            if ($first) {
                array_unshift($this->from, $c);
            } else {
                $this->from[] = $c;
            }
        }

        return $this;
    }

    /**
     * Adds WHERE clause(s) condition (each will be AND combined in statement)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous where(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function where($c, bool $reset = false): static
    {
        $filter = fn ($v): string => (string) preg_replace('/^\s*(AND|OR)\s*/i', '', (string) $v);
        if ($reset) {
            $this->where = [];
        }
        if (is_array($c)) {
            $c           = array_map($filter, $c);  // Cope with legacy code
            $this->where = [...$this->where, ...$c];   // @phpstan-ignore-line
        } elseif (!is_null($c)) {
            $c             = $filter($c);   // Cope with legacy code
            $this->where[] = $c;
        }

        return $this;
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous where(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function on($c, bool $reset = false): static
    {
        return $this->where($c, $reset);
    }

    /**
     * Adds additional WHERE clause condition(s) (including an operator at beginning)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous condition(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function cond($c, bool $reset = false): static
    {
        if ($reset) {
            $this->cond = [];
        }
        if (is_array($c)) {
            $this->cond = [...$this->cond, ...$c];
        } elseif (!is_null($c)) {
            $this->cond[] = $c;
        }

        return $this;
    }

    /**
     * Adds additional WHERE AND clause condition(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous condition(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function and($c, bool $reset = false): static
    {
        return $this->cond(array_map(fn ($v): string => 'AND ' . $v, is_array($c) ? $c : [$c]), $reset);
    }

    /**
     * Helper to group some AND parts
     *
     * @param      mixed  $c      the parts
     */
    public function andGroup($c): string
    {
        $group = '(' . implode(' AND ', is_array($c) ? $c : [$c]) . ')';

        return $group === '()' ? '' : $group;
    }

    /**
     * Adds additional WHERE OR clause condition(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous condition(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function or($c, bool $reset = false): static
    {
        return $this->cond(array_map(fn ($v): string => 'OR ' . $v, is_array($c) ? $c : [$c]), $reset);
    }

    /**
     * Helper to group some OR parts
     *
     * @param      mixed  $c      the parts}
     */
    public function orGroup($c): string
    {
        $group = '(' . implode(' OR ', is_array($c) ? $c : [$c]) . ')';

        return $group === '()' ? '' : $group;
    }

    /**
     * Adds generic clause(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous generic clause(s) first
     *
     * @return static    self instance, enabling to chain calls
     */
    public function sql($c, bool $reset = false): static
    {
        if ($reset) {
            $this->sql = [];
        }
        if (is_array($c)) {
            $this->sql = [...$this->sql, ...$c];
        } elseif (!is_null($c)) {
            $this->sql[] = $c;
        }

        return $this;
    }

    // Helpers

    /**
     * Escape a value
     *
     * @param      string  $value  The value
     */
    public function escape(string $value): string
    {
        if (!$this->con) {
            throw new DatabaseException('ConnectionInterface instance is missing.');
        }

        return $this->con->escapeStr($value);
    }

    /**
     * Quote and escape a value if necessary (type string)
     *
     * @param      string   $value   The value
     * @param      boolean  $escape  The escape
     */
    public function quote(string $value, bool $escape = true): string
    {
        if (!$this->con) {
            throw new DatabaseException('ConnectionInterface instance is missing.');
        }

        return "'" . ($escape ? $this->con->escapeStr($value) : $value) . "'";
    }

    /**
     * Return a SQL table/column fragment using an alias for a name
     *
     * @param      string  $name   The name (table, field)
     * @param      string  $alias  The alias
     */
    public function alias(string $name, string $alias): string
    {
        return $name . $this->_AS . $alias;
    }

    /**
     * alias() alias
     */
    public function as(string $name, string $alias): string
    {
        return $this->alias($name, $alias);
    }

    /**
     * Return an SQL IN (...) fragment
     *
     * @param      mixed  $list         The list of values
     * @param      string $cast         Cast given not null values to specified type
     */
    public function in($list, string $cast = ''): string
    {
        if (!$this->con) {
            throw new DatabaseException('ConnectionInterface instance is missing.');
        }

        if ($cast !== '') {
            switch ($cast) {
                case 'int':
                    if (is_array($list)) {
                        $list = array_map(fn ($v): ?int => is_null($v) ? $v : (int) $v, $list);
                    } else {
                        $list = is_null($list) ? null : (int) $list;
                    }

                    break;
                case 'string':
                    if (is_array($list)) {
                        $list = array_map(fn ($v): ?string => is_null($v) ? $v : (string) $v, $list);
                    } else {
                        $list = is_null($list) ? null : (string) $list;
                    }

                    break;
            }
        }

        return ' ' . trim($this->con->in($list));
    }

    /**
     * Return an SQL IN (SELECT ...) fragment
     *
     * @param      string             $field  The field
     * @param      SelectStatement    $sql    The sql
     */
    public function inSelect(string $field, SelectStatement $sql): string
    {
        return $field . ' IN (' . $sql->statement() . ')';
    }

    /**
     * Return an SQL formatted date
     *
     * @param   string    $field     Field name
     * @param   string    $pattern   Date format
     */
    public function dateFormat(string $field, string $pattern): string
    {
        if (!$this->con) {
            throw new DatabaseException('ConnectionInterface instance is missing.');
        }

        return $this->con->dateFormat($field, $pattern);
    }

    /**
     * Return an SQL formatted like
     *
     * @param      string  $field    The field
     * @param      string  $pattern  The pattern
     */
    public function like(string $field, string $pattern): string
    {
        return $field . ' LIKE ' . $this->quote($pattern);
    }

    /**
     * Return an SQL formatted REGEXP clause
     *
     * @param      string  $value  The value
     */
    public function regexp(string $value): string
    {
        if ($this->syntax === 'mysql') {
            $clause = "REGEXP '^" . $this->escape(preg_quote($value)) . "[0-9]+$'";
        } elseif ($this->syntax === 'postgresql') {
            $clause = "~ '^" . $this->escape(preg_quote($value)) . "[0-9]+$'";
        } else {
            $clause = "LIKE '" .
                $this->escape((string) preg_replace(['/\%/', '/\_/', '/\!/'], ['!%', '!_', '!!'], $value)) . "%' ESCAPE '!'";
        }

        return ' ' . $clause;
    }

    /**
     * Return an DISTINCT clause
     *
     * @param      string       $field     The field
     */
    public function unique(string $field): string
    {
        return 'DISTINCT ' . $field;
    }

    /**
     * Return an COUNT(...) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     * @param      bool         $unique    Unique values only
     */
    public function count(string $field, ?string $as = null, bool $unique = false): string
    {
        return 'COUNT(' . ($unique ? $this->unique($field) : $field) . ')' . ($as ? $this->_AS . $as : '');
    }

    /**
     * Return an AVG(...) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     */
    public function avg(string $field, ?string $as = null): string
    {
        return 'AVG(' . $field . ')' . ($as ? $this->_AS . $as : '');
    }

    /**
     * Return an MAX(...) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     */
    public function max(string $field, ?string $as = null): string
    {
        return 'MAX(' . $field . ')' . ($as ? $this->_AS . $as : '');
    }

    /**
     * Return an MIN(...) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     */
    public function min(string $field, ?string $as = null): string
    {
        return 'MIN(' . $field . ')' . ($as ? $this->_AS . $as : '');
    }

    /**
     * Return an SUM(...) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     */
    public function sum(string $field, ?string $as = null): string
    {
        return 'SUM(' . $field . ')' . ($as ? $this->_AS . $as : '');
    }

    /**
     * Return an IS NULL clause
     *
     * @param      string       $field     The field
     */
    public function isNull(string $field): string
    {
        return $field . ' IS NULL';
    }

    /**
     * Return an IS NOT NULL clause
     *
     * @param      string       $field     The field
     */
    public function isNotNull(string $field): string
    {
        return $field . ' IS NOT NULL';
    }

    /**
     * Compare two SQL queries
     *
     * May be used for debugging purpose as:
     *
     * if (!$sql->isSame($sql->statement(), $strReq)) {
     *     trigger_error('SQL statement error: ' . $sql->statement() . ' / ' . $strReq, E_USER_WARNING);
     * }
     *
     * @param      string   $local     The local
     * @param      string   $external  The external
     */
    public function isSame(string $local, string $external): bool
    {
        $filter = function ($s) {
            $s        = strtoupper($s);
            $patterns = [
                '\s+' => ' ', // Multiple spaces/tabs -> one space
                ' \)' => ')', // <space>) -> )
                ' ,'  => ',', // <space>, -> ,
                '\( ' => '(', // (<space> -> (
            ];
            foreach ($patterns as $pattern => $replace) {
                $s = (string) preg_replace('!' . $pattern . '!', $replace, $s);
            }

            return trim((string) $s);
        };

        return $filter($local) === $filter($external);
    }

    /**
     * Compare local statement and external one
     *
     * @param      string   $external       The external
     * @param      bool     $trigger_error  True to trigger an error if compare failsl
     * @param      bool     $dump           True to var_dump() all if compare fails
     * @param      bool     $print          True to print_r() all if compare fails
     */
    public function compare(string $external, bool $trigger_error = false, bool $dump = false, bool $print = false): bool
    {
        $str = $this->statement();
        if (!$this->isSame($str, $external)) {
            if ($print) {
                print_r($str);
                print_r($external);
            } elseif ($dump) {
                var_dump($str);
                var_dump($external);
            }
            if ($trigger_error) {
                trigger_error('SQL statement error (internal/external): ' . $str . ' / ' . $external, E_USER_WARNING);
            }

            return false;
        }

        return true;
    }
}
