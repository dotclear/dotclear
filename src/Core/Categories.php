<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Interface\Core\CategoriesInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Exception;

/**
 * @brief   Categories handler.
 *
 * Categories nested tree is based on excellent work of Kuzma Feskov
 * (http://php.russofile.ru/ru/authors/sql/nestedsets01/)
 *
 * @since   2.28, categories features have been grouped in this class
 * @since   2.28, container services have been added to constructor
 */
class Categories implements CategoriesInterface
{
    /**
     * Table name.
     */
    protected string $table;

    /**
     * Left cat field name (integer type).
     */
    protected string $f_left = 'cat_lft';

    /**
     * Right cat field name (integer type).
     */
    protected string $f_right = 'cat_rgt';

    /**
     * Cat ID field name (integer type).
     */
    protected string $f_id = 'cat_id';

    /**
     * Constructor.
     *
     * @param   ConnectionInterface     $con    The database connection instance
     */
    public function __construct(
        protected ConnectionInterface $con,
        protected string $blog_id = ''
    ) {
        $this->table = $this->con->prefix() . self::CATEGORY_TABLE_NAME;
    }

    public function createFromBlog(string $blog_id): CategoriesInterface
    {
        return new self($this->con, $blog_id);
    }

    public function openCategoryCursor(): Cursor
    {
        return $this->con->openCursor($this->con->prefix() . self::CATEGORY_TABLE_NAME);
    }

    /**
     * Gets the children.
     *
     * @param      int                      $start   The start
     * @param      null|int                 $id      The identifier
     * @param      string                   $sort    The sort
     * @param      array<string>            $fields  The fields
     *
     * @return     MetaRecord    The children.
     */
    public function getChildren(int $start = 0, ?int $id = null, string $sort = 'asc', array $fields = []): MetaRecord
    {
        $fields = $this->getFields($fields, 'C2.');

        $sql = 'SELECT C2.' . $this->f_id . ', C2.' . $this->f_left . ', C2.' . $this->f_right . ', COUNT(C1.' . $this->f_id . ') AS level ' . $fields . ' ' . 'FROM ' . $this->table . ' AS C1, ' . $this->table . ' AS C2 %s ' . 'WHERE C2.' . $this->f_left . ' BETWEEN C1.' . $this->f_left . ' AND C1.' . $this->f_right . ' ' . ' %s ' . $this->getCondition('AND', 'C2.') . $this->getCondition('AND', 'C1.') . 'GROUP BY C2.' . $this->f_id . ', C2.' . $this->f_left . ', C2.' . $this->f_right . ' ' . $fields . ' ' . ' %s ' . 'ORDER BY C2.' . $this->f_left . ' ' . ($sort === 'asc' ? 'ASC' : 'DESC') . ' ';

        $from = $where = '';
        if ($start > 0) {
            $from  = ', ' . $this->table . ' AS C3';
            $where = 'AND C3.' . $this->f_id . ' = ' . $start . ' AND C1.' . $this->f_left . ' >= C3.' . $this->f_left . ' AND C1.' . $this->f_right . ' <= C3.' . $this->f_right;
            $where .= $this->getCondition('AND', 'C3.');
        }

        $having = '';
        if ($id !== null) {
            $having = ' HAVING C2.' . $this->f_id . ' = ' . $id;
        }

        $sql = sprintf($sql, $from, $where, $having);

        return new MetaRecord($this->con->select($sql));
    }

    /**
     * Gets the parents.
     *
     * @param   int             $id         The category identifier
     * @param   array<string>   $fields     The fields
     *
     * @return  MetaRecord  The parents.
     */
    public function getParents(int $id, array $fields = []): MetaRecord
    {
        return new MetaRecord($this->con->select(
            'SELECT C1.' . $this->f_id . ' ' . $this->getFields($fields, 'C1.') . ' ' . 'FROM ' . $this->table . ' C1, ' . $this->table . ' C2 ' . 'WHERE C2.' . $this->f_id . ' = ' . $id . ' ' . 'AND C1.' . $this->f_left . ' < C2.' . $this->f_left . ' ' . 'AND C1.' . $this->f_right . ' > C2.' . $this->f_right . ' ' . $this->getCondition('AND', 'C2.') . $this->getCondition('AND', 'C1.') . 'ORDER BY C1.' . $this->f_left . ' ASC '
        ));
    }

    /**
     * Gets the parent.
     *
     * @param   int             $id         The category identifier
     * @param   array<string>   $fields     The fields
     *
     * @return     MetaRecord  The parent.
     */
    public function getParent(int $id, array $fields = []): MetaRecord
    {
        return new MetaRecord($this->con->select(
            'SELECT C1.' . $this->f_id . ' ' . $this->getFields($fields, 'C1.') . ' ' . 'FROM ' . $this->table . ' C1, ' . $this->table . ' C2 ' . 'WHERE C2.' . $this->f_id . ' = ' . $id . ' ' . 'AND C1.' . $this->f_left . ' < C2.' . $this->f_left . ' ' . 'AND C1.' . $this->f_right . ' > C2.' . $this->f_right . ' ' . $this->getCondition('AND', 'C2.') . $this->getCondition('AND', 'C1.') . 'ORDER BY C1.' . $this->f_left . ' DESC ' . $this->con->limit(1)
        ));
    }

    public function addNode($data, int $target = 0)
    {
        if (!is_array($data) && !($data instanceof Cursor)) {
            throw new Exception('Invalid data block');
        }

        if (is_array($data)) {
            $D    = $data;
            $data = $this->openCategoryCursor();
            foreach ($D as $k => $v) {
                $data->{$k} = $v;
            }
            unset($D);
        }

        # We want to put it at the end
        $this->con->writeLock($this->table);

        try {
            $rs = new MetaRecord($this->con->select('SELECT MAX(' . $this->f_id . ') as n_id FROM ' . $this->table));
            $id = (int) $rs->n_id;

            $rs = new MetaRecord($this->con->select(
                'SELECT MAX(' . $this->f_right . ') as n_r ' .
                'FROM ' . $this->table .
                $this->getCondition('WHERE')
            ));
            $last = (int) $rs->n_r === 0 ? 1 : $rs->n_r;

            $data->{$this->f_id}    = $id   + 1;
            $data->{$this->f_left}  = $last + 1;
            $data->{$this->f_right} = $last + 2;

            $data->insert();
            $this->con->unlock();

            try {
                $this->setNodeParent($id + 1, $target);

                return $data->{$this->f_id};
            } catch (Exception) {
                // We don't mind error in this case
            }
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }

        return null;
    }

    public function updatePosition(int $id, int $left, int $right): void
    {
        $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_left . ' = ' . $left . ', ' . $this->f_right . ' = ' . $right . ' WHERE ' . $this->f_id . ' = ' . $id . $this->getCondition();

        $this->con->begin();

        try {
            $this->con->execute($sql);
            $this->con->commit();
        } catch (Exception $e) {
            $this->con->rollback();

            throw $e;
        }
    }
    public function deleteNode(int $node, bool $keep_children = true): void
    {
        $rs = $this->getChildren(0, $node);
        if ($rs->isEmpty()) {
            throw new Exception('Node does not exist.');
        }
        $node_left  = (int) $rs->{$this->f_left};
        $node_right = (int) $rs->{$this->f_right};

        try {
            $this->con->begin();

            if ($keep_children) {
                $this->con->execute('DELETE FROM ' . $this->table . ' WHERE ' . $this->f_id . ' = ' . $node);

                $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_right . ' = CASE ' . 'WHEN ' . $this->f_right . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_right . ' - 1 ' . 'WHEN ' . $this->f_right . ' > ' . $node_right . ' ' . 'THEN ' . $this->f_right . ' - 2 ' . 'ELSE ' . $this->f_right . ' ' . 'END, ' . $this->f_left . ' = CASE ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_left . ' - 1 ' . 'WHEN ' . $this->f_left . ' > ' . $node_right . ' ' . 'THEN ' . $this->f_left . ' - 2 ' . 'ELSE ' . $this->f_left . ' ' . 'END ' . 'WHERE ' . $this->f_right . ' > ' . $node_left . $this->getCondition();

                $this->con->execute($sql);
            } else {
                $this->con->execute('DELETE FROM ' . $this->table . ' WHERE ' . $this->f_left . ' BETWEEN ' . $node_left . ' AND ' . $node_right);

                $node_delta = $node_right - $node_left + 1;
                $sql        = 'UPDATE ' . $this->table . ' SET ' . $this->f_left . ' = CASE ' . 'WHEN ' . $this->f_left . ' > ' . $node_left . ' ' . 'THEN ' . $this->f_left . ' - (' . $node_delta . ') ' . 'ELSE ' . $this->f_left . ' ' . 'END, ' . $this->f_right . ' = CASE ' . 'WHEN ' . $this->f_right . ' > ' . $node_left . ' ' . 'THEN ' . $this->f_right . ' - (' . $node_delta . ') ' . 'ELSE ' . $this->f_right . ' ' . 'END ' . 'WHERE ' . $this->f_right . ' > ' . $node_right . $this->getCondition();
            }

            $this->con->commit();
        } catch (Exception $e) {
            $this->con->rollback();

            throw $e;
        }
    }

    public function resetOrder(): void
    {
        $rs = new MetaRecord($this->con->select(
            'SELECT ' . $this->f_id . ' ' . 'FROM ' . $this->table . ' ' . $this->getCondition('WHERE') . 'ORDER BY ' . $this->f_left . ' ASC '
        ));
        $lft = 2;
        $this->con->begin();

        try {
            while ($rs->fetch()) {
                $this->con->execute(
                    'UPDATE ' . $this->table . ' SET ' . $this->f_left . ' = ' . ($lft++) . ', ' . $this->f_right . ' = ' . ($lft++) . ' ' . 'WHERE ' . $this->f_id . ' = ' . (int) $rs->{$this->f_id} . ' ' . $this->getCondition()
                );
            }
            $this->con->commit();
        } catch (Exception $e) {
            $this->con->rollback();

            throw $e;
        }
    }

    public function setNodeParent(int $node, int $target = 0): void
    {
        if ($node === $target) {
            return;
        }

        $rs = $this->getChildren(0, $node);
        if ($rs->isEmpty()) {
            throw new Exception('Node does not exist.');
        }
        $node_left  = (int) $rs->{$this->f_left};
        $node_right = (int) $rs->{$this->f_right};
        $node_level = (int) $rs->level;

        if ($target > 0) {
            $rs = $this->getChildren(0, $target);
        } else {
            $rs = new MetaRecord($this->con->select(
                'SELECT MIN(' . $this->f_left . ')-1 AS ' . $this->f_left . ', MAX(' . $this->f_right . ')+1 AS ' . $this->f_right . ', 0 AS level ' . 'FROM ' . $this->table . ' ' . $this->getCondition('WHERE')
            ));
        }
        $target_left  = (int) $rs->{$this->f_left};
        $target_right = (int) $rs->{$this->f_right};
        $target_level = (int) $rs->level;

        if ($node_left === $target_left
            || ($target_left >= $node_left && $target_left <= $node_right)
            || ($node_level === $target_level + 1 && $node_left > $target_left && $node_right < $target_right)
        ) {
            throw new Exception('Cannot move tree');
        }

        if ($target_left < $node_left && $target_right > $node_right && $target_level < $node_level - 1) {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_right . ' = CASE ' . 'WHEN ' . $this->f_right . ' BETWEEN ' . ($node_right + 1) . ' AND ' . ($target_right - 1) . ' ' . 'THEN ' . $this->f_right . '-(' . ($node_right - $node_left + 1) . ') ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_right . '+' . ((($target_right - $node_right - $node_level + $target_level) / 2) * 2 + $node_level - $target_level - 1) . ' ' . 'ELSE ' . $this->f_right . ' ' . 'END, ' . $this->f_left . ' = CASE ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . ($node_right + 1) . ' AND ' . ($target_right - 1) . ' ' . 'THEN ' . $this->f_left . '-(' . ($node_right - $node_left + 1) . ') ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_left . '+' . ((($target_right - $node_right - $node_level + $target_level) / 2) * 2 + $node_level - $target_level - 1) . ' ' . 'ELSE ' . $this->f_left . ' ' . 'END ' . 'WHERE ' . $this->f_left . ' BETWEEN ' . ($target_left + 1) . ' AND ' . ($target_right - 1) . '';
        } elseif ($target_left < $node_left) {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_left . ' = CASE ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $target_right . ' AND ' . ($node_left - 1) . ' ' . 'THEN ' . $this->f_left . '+' . ($node_right - $node_left + 1) . ' ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_left . '-(' . ($node_left - $target_right) . ') ' . 'ELSE ' . $this->f_left . ' ' . 'END, ' . $this->f_right . ' = CASE ' . 'WHEN ' . $this->f_right . ' BETWEEN ' . $target_right . ' AND ' . $node_left . ' ' . 'THEN ' . $this->f_right . '+' . ($node_right - $node_left + 1) . ' ' . 'WHEN ' . $this->f_right . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_right . '-(' . ($node_left - $target_right) . ') ' . 'ELSE ' . $this->f_right . ' ' . 'END ' . 'WHERE (' . $this->f_left . ' BETWEEN ' . $target_left . ' AND ' . $node_right . ' ' . 'OR ' . $this->f_right . ' BETWEEN ' . $target_left . ' AND ' . $node_right . ')';
        } else {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_left . ' = CASE ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $node_right . ' AND ' . $target_right . ' ' . 'THEN ' . $this->f_left . '-' . ($node_right - $node_left + 1) . ' ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_left . '+' . ($target_right - 1 - $node_right) . ' ' . 'ELSE ' . $this->f_left . ' ' . 'END, ' . $this->f_right . ' = CASE ' . 'WHEN ' . $this->f_right . ' BETWEEN ' . ($node_right + 1) . ' AND ' . ($target_right - 1) . ' ' . 'THEN ' . $this->f_right . '-' . ($node_right - $node_left + 1) . ' ' . 'WHEN ' . $this->f_right . ' BETWEEN ' . $node_left . ' AND ' . $node_right . ' ' . 'THEN ' . $this->f_right . '+' . ($target_right - 1 - $node_right) . ' ' . 'ELSE ' . $this->f_right . ' ' . 'END ' . 'WHERE (' . $this->f_left . ' BETWEEN ' . $node_left . ' AND ' . $target_right . ' ' . 'OR ' . $this->f_right . ' BETWEEN ' . $node_left . ' AND ' . $target_right . ')';
        }
        $sql .= ' ' . $this->getCondition();

        $this->con->execute($sql);
    }

    public function setNodePosition(int $nodeA, int $nodeB, string $position = 'after'): void
    {
        $rs = $this->getChildren(0, $nodeA);
        if ($rs->isEmpty()) {
            throw new Exception('Node does not exist.');
        }
        $A_left  = (int) $rs->{$this->f_left};
        $A_right = (int) $rs->{$this->f_right};
        $A_level = (int) $rs->level;

        $rs = $this->getChildren(0, $nodeB);
        if ($rs->isEmpty()) {
            throw new Exception('Node does not exist.');
        }
        $B_left  = (int) $rs->{$this->f_left};
        $B_right = (int) $rs->{$this->f_right};
        $B_level = (int) $rs->level;

        if ($A_level !== $B_level) {
            throw new Exception('Cannot change position');
        }

        $rs      = $this->getParents($nodeA);
        $parentA = $rs->isEmpty() ? 0 : (int) $rs->{$this->f_id};
        $rs      = $this->getParents($nodeB);
        $parentB = $rs->isEmpty() ? 0 : (int) $rs->{$this->f_id};

        if ($parentA !== $parentB) {
            throw new Exception('Cannot change position');
        }

        if ($position === 'before') {
            if ($A_left > $B_left) {
                $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_right . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_right . ' - (' . ($A_left - $B_left) . ') ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $B_left . ' AND ' . ($A_left - 1) . ' THEN ' . $this->f_right . ' +  ' . ($A_right - $A_left + 1) . ' ELSE ' . $this->f_right . ' END, ' . $this->f_left . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_left . ' - (' . ($A_left - $B_left) . ') ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . $B_left . ' AND ' . ($A_left - 1) . ' THEN ' . $this->f_left . ' + ' . ($A_right - $A_left + 1) . ' ELSE ' . $this->f_left . ' END ' . 'WHERE ' . $this->f_left . ' BETWEEN ' . $B_left . ' AND ' . $A_right;
            } else {
                $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_right . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_right . ' + ' . (($B_left - $A_left) - ($A_right - $A_left + 1)) . ' ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . ($A_right + 1) . ' AND ' . ($B_left - 1) . ' THEN ' . $this->f_right . ' - (' . ($A_right - $A_left + 1) . ') ELSE ' . $this->f_right . ' END, ' . $this->f_left . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_left . ' + ' . (($B_left - $A_left) - ($A_right - $A_left + 1)) . ' ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . ($A_right + 1) . ' AND ' . ($B_left - 1) . ' THEN ' . $this->f_left . ' - (' . ($A_right - $A_left + 1) . ') ELSE ' . $this->f_left . ' END ' . 'WHERE ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . ($B_left - 1);
            }
        } elseif ($A_left > $B_left) {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_right . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_right . ' - (' . ($A_left - $B_left - ($B_right - $B_left + 1)) . ') ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . ($B_right + 1) . ' AND ' . ($A_left - 1) . ' THEN ' . $this->f_right . ' +  ' . ($A_right - $A_left + 1) . ' ELSE ' . $this->f_right . ' END, ' . $this->f_left . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_left . ' - (' . ($A_left - $B_left - ($B_right - $B_left + 1)) . ') ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . ($B_right + 1) . ' AND ' . ($A_left - 1) . ' THEN ' . $this->f_left . ' + ' . ($A_right - $A_left + 1) . ' ELSE ' . $this->f_left . ' END ' . 'WHERE ' . $this->f_left . ' BETWEEN ' . ($B_right + 1) . ' AND ' . $A_right;
        } else {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $this->f_right . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_right . ' + ' . ($B_right - $A_right) . ' ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . ($A_right + 1) . ' AND ' . $B_right . ' THEN ' . $this->f_right . ' - (' . ($A_right - $A_left + 1) . ') ELSE ' . $this->f_right . ' END, ' . $this->f_left . ' = CASE WHEN ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $A_right . ' THEN ' . $this->f_left . ' + ' . ($B_right - $A_right) . ' ' . 'WHEN ' . $this->f_left . ' BETWEEN ' . ($A_right + 1) . ' AND ' . $B_right . ' THEN ' . $this->f_left . ' - (' . ($A_right - $A_left + 1) . ') ELSE ' . $this->f_left . ' END ' . 'WHERE ' . $this->f_left . ' BETWEEN ' . $A_left . ' AND ' . $B_right;
        }

        $sql .= $this->getCondition();
        $this->con->execute($sql);
    }

    /**
     * Get the condition.
     *
     * @param   string  $start      The start
     * @param   string  $prefix     The prefix
     *
     * @return  string  The condition.
     */
    protected function getCondition(string $start = 'AND', string $prefix = ''): string
    {
        return ' ' . $start . ' ' . $prefix . "blog_id = '" . $this->con->escapeStr($this->blog_id) . "' ";
    }

    /**
     * Get fields.
     *
     * @param   array<int,string>   $fields     The start
     * @param   string              $prefix     The prefix
     *
     * @return  string  The fields
     */
    protected function getFields(array $fields = [], string $prefix = ''): string
    {
        return ', ' . $prefix . implode(', ' . $prefix, ['cat_title', 'cat_url', 'cat_desc', ...$fields]);
    }
}
