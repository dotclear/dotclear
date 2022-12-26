<?php
/**
 * @class cursor
 * @brief DBLayer Cursor
 *
 * This class implements facilities to insert or update in a table.
 *
 * @package Clearbricks
 * @subpackage DBLayer
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class cursor
{
    /**
     * @var        dbLayer
     */
    private $__con;

    /**
     * @var        array
     */
    private $__data = [];

    /**
     * @var        string
     */
    private $__table;

    /**
     * Constructor
     *
     * Init cursor object on a given table. Note that you can init it with
     * {@link dbLayer::openCursor() openCursor()} method of your connection object.
     *
     * Example:
     * <code>
     * <?php
     *    $cur = $con->openCursor('table');
     *    $cur->field1 = 1;
     *    $cur->field2 = 'foo';
     *    $cur->insert(); // Insert field ...
     *
     *    $cur->update('WHERE field3 = 4'); // ... or update field
     * ?>
     * </code>
     *
     * @see dbLayer::openCursor()
     *
     * @param dbLayer   $con      Connection object
     * @param string    $table    Table name
     */
    public function __construct(dbLayer $con, string $table)
    {
        $this->__con = &$con;
        $this->setTable($table);
    }

    /**
     * Set table
     *
     * Changes working table and resets data
     *
     * @param string    $table    Table name
     */
    public function setTable(string $table): void
    {
        $this->__table = $table;
        $this->__data  = [];
    }

    /**
     * Set field
     *
     * Set value <var>$value</var> to a field named <var>$name</var>. Value could be
     * an string, an integer, a float, a null value or an array.
     *
     * If value is an array, its first value will be interpreted as a SQL
     * command. String values will be automatically escaped.
     *
     * @see __set()
     *
     * @param string    $name        Field name
     * @param mixed     $value        Field value
     */
    public function setField(string $name, $value): void
    {
        $this->__data[$name] = $value;
    }

    /**
     * Unset field
     *
     * Remove a field from data set.
     *
     * @param string    $name        Field name
     */
    public function unsetField(string $name): void
    {
        unset($this->__data[$name]);
    }

    /**
     * Field exists
     *
     * @return boolean    true if field named <var>$name</var> exists
     */
    public function isField(string $name): bool
    {
        return isset($this->__data[$name]);
    }

    /**
     * Field value
     *
     * @see __get()
     *
     * @return mixed    value for a field named <var>$name</var>
     */
    public function getField(string $name)
    {
        if (isset($this->__data[$name])) {
            return $this->__data[$name];
        }
    }

    /**
     * Set Field
     *
     * Magic alias for {@link setField()}
     */
    public function __set(string $name, $value): void
    {
        $this->setField($name, $value);
    }

    /**
     * Field value
     *
     * Magic alias for {@link getField()}
     *
     * @return mixed    value for a field named <var>$n</var>
     */
    public function __get(string $name)
    {
        return $this->getField($name);
    }

    /**
     * Empty data set
     *
     * Removes all data from data set
     */
    public function clean(): void
    {
        $this->__data = [];
    }

    private function formatFields(): array
    {
        $data = [];

        foreach ($this->__data as $k => $v) {
            $k = $this->__con->escapeSystem($k);

            if (is_null($v)) {
                $data[$k] = 'NULL';
            } elseif (is_string($v)) {
                $data[$k] = "'" . $this->__con->escape($v) . "'";
            } elseif (is_array($v)) {
                $data[$k] = is_string($v[0]) ? "'" . $this->__con->escape($v[0]) . "'" : $v[0];
            } else {
                $data[$k] = $v;
            }
        }

        return $data;
    }

    /**
     * Get insert query
     *
     * Returns the generated INSERT query
     *
     * @return string
     */
    public function getInsert(): string
    {
        $data = $this->formatFields();

        return 'INSERT INTO ' . $this->__con->escapeSystem($this->__table) . " (\n" .
        implode(",\n", array_keys($data)) . "\n) VALUES (\n" .
        implode(",\n", array_values($data)) . "\n) ";
    }

    /**
     * Get update query
     *
     * Returns the generated UPDATE query
     *
     * @param string    $where        WHERE condition
     *
     * @return string
     */
    public function getUpdate(string $where): string
    {
        $data   = $this->formatFields();
        $fields = [];

        $updReq = 'UPDATE ' . $this->__con->escapeSystem($this->__table) . " SET \n";

        foreach ($data as $k => $v) {
            $fields[] = $k . ' = ' . $v . '';
        }

        $updReq .= implode(",\n", $fields);
        $updReq .= "\n" . $where;

        return $updReq;
    }

    /**
     * Execute insert query
     *
     * Executes the generated INSERT query
     */
    public function insert(): bool
    {
        if (!$this->__table) {
            throw new Exception('No table name.');
        }

        $insReq = $this->getInsert();

        $this->__con->execute($insReq);

        return true;
    }

    /**
     * Execute update query
     *
     * Executes the generated UPDATE query
     *
     * @param string    $where        WHERE condition
     */
    public function update(string $where): bool
    {
        if (!$this->__table) {
            throw new Exception('No table name.');
        }

        $updReq = $this->getUpdate($where);

        $this->__con->execute($updReq);

        return true;
    }
}
