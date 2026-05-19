<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Dotclear\Exception\DatabaseException;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function test(): void
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

        // @phpstan-ignore method.notFound
        $table
            ->field('id', 'INTEGER', 0, false, 0)
            ->field('status', 'SMALLINT', 0, true, -1)
            ->field('uid', 'BIGINT')
            ->field('cost', 'FLOAT')
            ->field('discount', 'REAL')
            ->field('number', 'NUMERIC')
            ->field('date', 'DATE')
            ->field('hour', 'TIME')
            ->field('ts', 'TIMESTAMP', 0, true, 'now()')
            ->field('name', 'CHAR', 256, true, null)
            ->field('fullname', 'VARCHAR')
            ->field('description', 'TEXT')
            ->field('strange', 'WTF', 0, true, null, false)
        ;

        // Fields
        $this->assertEquals(
            [
                'id' => [
                    'type'    => 'integer',
                    'len'     => 0,
                    'default' => 0,
                    'null'    => false,
                ],
                'status' => [
                    'type'    => 'smallint',
                    'len'     => 0,
                    'default' => -1,
                    'null'    => true,
                ],
                'uid' => [
                    'type'    => 'bigint',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
                'cost' => [
                    'type'    => 'float',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
                'discount' => [
                    'type'    => 'real',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
                'number' => [
                    'type'    => 'numeric',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
                'date' => [
                    'type'    => 'date',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
                'hour' => [
                    'type'    => 'time',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
                'ts' => [
                    'type'    => 'timestamp',
                    'len'     => 0,
                    'default' => 'now()',
                    'null'    => true,
                ],
                'name' => [
                    'type'    => 'char',
                    'len'     => 256,
                    'default' => null,
                    'null'    => true,
                ],
                'fullname' => [
                    'type'    => 'varchar',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
                'description' => [
                    'type'    => 'text',
                    'len'     => 0,
                    'default' => false,
                    'null'    => true,
                ],
            ],
            $table->getFields()
        );

        $this->assertTrue(
            $table->fieldExists('id')
        );
        $this->assertFalse(
            $table->fieldExists('unknown')
        );

        // Primary key

        $table
            ->primary('pk_id', 'id')
        ;

        $this->assertEquals(
            [
                'pk_id' => [
                    'type' => 'primary',
                    'cols' => [
                        'id',
                    ],
                ],
            ],
            $table->getKeys()
        );

        $this->assertEquals(
            'pk_id',
            $table->keyExists('pk_id', 'primary', ['id'])
        );
        $this->assertFalse(
            $table->keyExists('pk_uid', 'primary', ['uid'])
        );

        // Unique keys

        $table
            ->unique('uk_uid', 'id', 'uid')
        ;

        $this->assertEquals(
            [
                'pk_id' => [
                    'type' => 'primary',
                    'cols' => [
                        'id',
                    ],
                ],
                'uk_uid' => [
                    'type' => 'unique',
                    'cols' => [
                        'id',
                        'uid',
                    ],
                ],
            ],
            $table->getKeys()
        );

        $this->assertEquals(
            'uk_uid',
            $table->keyExists('uk_uid', 'unique', ['id', 'uid'])
        );
        $this->assertEquals(
            'pk_id',
            $table->keyExists('pk_bis', 'primary', ['id'])
        );
        $this->assertEquals(
            'uk_uid',
            $table->keyExists('uk_bis', 'unique', ['id', 'uid'])
        );
        $this->assertFalse(
            $table->keyExists('pk_unknown', 'unique', ['unknown'])
        );

        // Indexes

        $table
            ->index('idx_name', 'btree', 'name', 'fullname')
        ;

        $this->assertEquals(
            [
                'idx_name' => [
                    'type' => 'btree',
                    'cols' => [
                        'name',
                        'fullname',
                    ],
                ],
            ],
            $table->getIndexes()
        );
        $this->assertEquals(
            'idx_name',
            $table->indexExists('idx_name', 'btree', ['name', 'fullname'])
        );
        $this->assertEquals(
            'idx_name',
            $table->indexExists('idx_bis', 'btree', ['name', 'fullname'])
        );
        $this->assertFalse(
            $table->indexExists('idx_unknown', 'btree', ['id', 'uid'])
        );

        // References

        $table
            ->reference('fk_contact', 'name', 'dc_contact', 'name', 'cascade', 'cascade');
        ;

        $this->assertEquals(
            [
                'fk_contact' => [
                    'c_cols' => [
                        'name',
                    ],
                    'p_table' => 'dc_contact',
                    'p_cols'  => [
                        'name',
                    ],
                    'update' => 'cascade',
                    'delete' => 'cascade',
                ],
            ],
            $table->getReferences()
        );
        $this->assertEquals(
            'fk_contact',
            $table->referenceExists('fk_contact', ['name'], 'dc_contact', ['name'])
        );
        $this->assertEquals(
            'fk_contact',
            $table->referenceExists('fk_bis', ['name'], 'dc_contact', ['name'])
        );
        $this->assertFalse(
            $table->referenceExists('fk_unknown', ['id'], 'dc_report', ['uid'])
        );
    }

    public function testUniqueIndexOnUnknownField(): void
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

        // @phpstan-ignore method.notFound
        $table
            ->id('INTEGER', 0, false, 0)
            ->status('SMALLINT', 0, true, -1)
            ->uid('BIGINT')
            ->cost('FLOAT')
            ->discount('REAL')
            ->number('NUMERIC')
            ->date('DATE')
            ->hour('TIME')
            ->ts('TIMESTAMP', 0, true, 'now()')
            ->name('CHAR', 256, true, null)
            ->fullname('VARCHAR')
            ->description('TEXT')
            ->strange('WTF', 0, true, null, false)
        ;

        // Unique keys

        $table
            ->unique('uk_uid', 'id', 'uid')
        ;

        $this->assertEquals(
            [
                'uk_uid' => [
                    'type' => 'unique',
                    'cols' => [
                        'id',
                        'uid',
                    ],
                ],
            ],
            $table->getKeys()
        );

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Field unknown does not exist in table dc_table');

        $table->unique('pk_unknown', 'unknown');
    }

    public function testPrimaryKeyAlreadyExists(): void
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

        // @phpstan-ignore method.notFound
        $table
            ->id('INTEGER', 0, false, 0)
            ->status('SMALLINT', 0, true, -1)
            ->uid('BIGINT')
            ->cost('FLOAT')
            ->discount('REAL')
            ->number('NUMERIC')
            ->date('DATE')
            ->hour('TIME')
            ->ts('TIMESTAMP', 0, true, 'now()')
            ->name('CHAR', 256, true, null)
            ->fullname('VARCHAR')
            ->description('TEXT')
            ->strange('WTF', 0, true, null, false)
        ;

        // Primary key

        $table
            ->primary('pk_id', 'id')
        ;

        $this->assertEquals(
            [
                'pk_id' => [
                    'type' => 'primary',
                    'cols' => [
                        'id',
                    ],
                ],
            ],
            $table->getKeys()
        );

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Table dc_table already has a primary key');

        $table->primary('pk_uid', 'uid');
    }

    public function testMagicSetFieldError(): void
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

        // @phpstan-ignore method.notFound
        $table
            ->id('INTEGER', 0, false, 0)
            ->status('SMALLINT', 0, true, -1)
            ->uid('BIGINT')
            ->cost('FLOAT')
            ->discount('REAL')
            ->number('NUMERIC')
            ->date('DATE')
            ->hour('TIME')
            ->ts('TIMESTAMP', 0, true, 'now()')
            ->name('CHAR', 256, true, null)
            ->fullname('VARCHAR')
            ->description('TEXT')
            ->strange('WTF', 0, true, null, false)
        ;

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Invalid data type weird in schema');

        // @phpstan-ignore method.notFound
        $table->bizarre('weird', 0);
    }
}
