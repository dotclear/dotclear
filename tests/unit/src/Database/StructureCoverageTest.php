<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Dotclear\Database\Structure;
use Dotclear\Interface\Database\ConnectionInterface;
use Dotclear\Interface\Database\SchemaInterface;
use PHPUnit\Framework\TestCase;

class StructureCoverageTest extends TestCase
{
    public function testReverseAndSynchronizeExistingTables(): void
    {
        $schema = $this->createMock(SchemaInterface::class);
        $schema->method('getTables')->willReturn(['dc_items', 'other_table']);
        $schema->method('getColumns')->willReturn([
            'id'         => ['type' => 'integer', 'len' => 0, 'null' => false, 'default' => 0],
            'name'       => ['type' => 'varchar', 'len' => 40, 'null' => true, 'default' => null],
            'parent_id'  => ['type' => 'integer', 'len' => 0, 'null' => true, 'default' => null],
        ]);
        $schema->method('getKeys')->willReturn([
            ['name' => 'PRIMARY', 'primary' => true, 'unique' => true, 'cols' => ['id']],
            ['name' => 'dc_uk_name', 'primary' => false, 'unique' => true, 'cols' => ['name']],
        ]);
        $schema->method('getIndexes')->willReturn([
            ['name' => 'dc_idx_name', 'type' => 'btree', 'cols' => ['name']],
        ]);
        $schema->method('getReferences')->willReturn([
            ['name' => 'dc_fk_parent', 'c_cols' => ['parent_id'], 'p_table' => 'dc_parents', 'p_cols' => ['id'], 'update' => 'cascade', 'delete' => 'restrict'],
        ]);
        $schema->method('dbt2udt')->willReturnCallback(static fn (string $type): string => $type);

        $con = $this->createStub(ConnectionInterface::class);
        $con->method('driver')->willReturn('test');
        $con->method('syntax')->willReturn('mysql');
        $con->method('schema')->willReturn($schema);

        $current = new Structure($con, 'dc_');
        $current->reverse();
        $this->assertSame(['dc_dc_items'], array_keys($current->getTables()));
        $this->assertTrue($current->tableExists('dc_items'));
        $this->assertFalse($current->tableExists('other_table'));

        $target = $this->buildMatchingStructure($con);
        $this->assertSame(0, $current->synchronize($target));

        $target = new Structure($con, 'dc_');
        $targetTable = $target->table('items');
        $targetTable->field('id', 'integer', 0, false, 0);
        $targetTable->field('name', 'varchar', 80, true, null);
        $targetTable->field('parent_id', 'integer', 0, true, null);
        $targetTable->field('extra', 'text', 0, true, null);
        $targetTable->primary('pk_items', 'parent_id');
        $targetTable->unique('uk_name', 'name', 'parent_id');
        $targetTable->unique('uk_parent', 'parent_id');
        $targetTable->index('idx_name', 'btree', 'name', 'parent_id');
        $targetTable->index('idx_parent', 'hash', 'parent_id');
        $targetTable->reference('fk_parent', 'id', 'parents', 'id', 'cascade', 'restrict');
        $targetTable->reference('fk_new', 'id', 'parents', 'id', false, false);

        $schema->expects($this->once())->method('createField')->with('dc_items', 'extra', 'text', 0, true, null);
        $schema->expects($this->once())->method('alterField')->with('dc_items', 'name', 'varchar', 80, true, null);
        $schema->expects($this->once())->method('alterPrimary')->with('dc_items', 'PRIMARY', 'PRIMARY', ['parent_id']);
        $schema->expects($this->once())->method('createUnique')->with('dc_items', 'dc_uk_parent', ['parent_id']);
        $schema->expects($this->once())->method('alterUnique')->with('dc_items', 'dc_uk_name', 'dc_uk_name', ['name', 'parent_id']);
        $schema->expects($this->once())->method('createIndex')->with('dc_items', 'dc_idx_parent', 'hash', ['parent_id']);
        $schema->expects($this->once())->method('alterIndex')->with('dc_items', 'dc_idx_name', 'dc_idx_name', 'btree', ['name', 'parent_id']);
        $schema->expects($this->once())->method('createReference')->with('dc_fk_new', 'dc_items', ['id'], 'dc_parents', ['id'], false, false);
        $schema->expects($this->once())->method('alterReference')->with('dc_fk_parent', 'dc_fk_parent', 'dc_items', ['id'], 'dc_parents', ['id'], 'cascade', 'restrict');
        $schema->expects($this->once())->method('flushStack');

        $this->assertSame(8, $current->synchronize($target));
    }

    private function buildMatchingStructure(ConnectionInterface $con): Structure
    {
        $structure = new Structure($con, 'dc_');
        $table = $structure->table('items');
        $table->field('id', 'integer', 0, false, 0);
        $table->field('name', 'varchar', 40, true, null);
        $table->field('parent_id', 'integer', 0, true, null);
        $table->primary('pk_items', 'id');
        $table->unique('uk_name', 'name');
        $table->index('idx_name', 'btree', 'name');
        $table->reference('fk_parent', 'parent_id', 'parents', 'id', 'cascade', 'restrict');

        return $structure;
    }
}
