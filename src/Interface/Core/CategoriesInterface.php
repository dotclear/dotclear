<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;

/**
 * @brief   Categories handler interface.
 *
 * @since   2.28
 */
interface CategoriesInterface
{
    /**
     * Create new categorires instance for given blog ID.
     *
     * @param   string  $blog_id    The blog_id
     *
     * @return  CategoriesInterface     The blog categories instance
     */
    public function createFromBlog(string $blog_id): CategoriesInterface;

    /**
     * Categories table name.
     *
     * @var   string  CATEGORY_TABLE_NAME
     */
    public const CATEGORY_TABLE_NAME = 'category';

    /**
     * Open a database table cursor.
     *
     * @return  Cursor  The category database table cursor
     */
    public function openCategoryCursor(): Cursor;

    /**
     * Gets the category children.
     *
     * @param   int             $start      The start
     * @param   null|int        $id         The identifier
     * @param   string          $sort       The sort
     * @param   array<string>   $fields     The fields
     *
     * @return  MetaRecord  The children.
     */
    public function getChildren(int $start = 0, ?int $id = null, string $sort = 'asc', array $fields = []): MetaRecord;

    /**
     * Gets the parents.
     *
     * @param   int             $id         The category identifier
     * @param   array<string>   $fields     The fields
     *
     * @return  MetaRecord  The parents.
     */
    public function getParents(int $id, array $fields = []): MetaRecord;

    /**
     * Gets the parent.
     *
     * @param   int             $id         The category identifier
     * @param   array<string>   $fields     The fields
     *
     * @return  MetaRecord  The parent.
     */
    public function getParent(int $id, array $fields = []): MetaRecord;

    /// @name Tree manipulations methods
    //@{
    /**
     * Add a node.
     *
     * @param   mixed   $data       The data
     * @param   int     $target     The target
     *
     * @throws  \Exception
     *
     * @return  mixed
     */
    public function addNode($data, int $target = 0);

    /**
     * Update position.
     *
     * @param   int     $id     The identifier
     * @param   int     $left   The left
     * @param   int     $right  The right
     */
    public function updatePosition(int $id, int $left, int $right): void;

    /**
     * Delete a node.
     *
     * @param   int     $node           The node
     * @param   bool    $keep_children  keep children
     *
     * @throws  \Exception
     */
    public function deleteNode(int $node, bool $keep_children = true): void;

    /**
     * Reset order.
     */
    public function resetOrder(): void;

    /**
     * Set the node parent.
     *
     * @param   int     $node       The node
     * @param   int     $target     The target
     *
     * @throws  \Exception
     */
    public function setNodeParent(int $node, int $target = 0): void;

    /**
     * Set the node position.
     *
     * @param   int     $nodeA      The node a
     * @param   int     $nodeB      The node b
     * @param   string  $position   The position
     *
     * @throws  \Exception
     */
    public function setNodePosition(int $nodeA, int $nodeB, string $position = 'after'): void;

    //@}
}
