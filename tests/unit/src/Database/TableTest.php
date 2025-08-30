<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Exception;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function test(): void
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

        // @phpstan-ignore method.notFound
        $table
            ->id('INTEGER', null, false, 0)
            ->status('SMALLINT', null, true, -1)
            ->uid('BIGINT', null)
            ->cost('FLOAT', null)
            ->discount('REAL', null)
            ->number('NUMERIC', null)
            ->date('DATE', null)
            ->hour('TIME', null)
            ->ts('TIMESTAMP', null, true, 'now()')
            ->name('CHAR', 256, true, null)
            ->fullname('VARCHAR', null)
            ->description('TEXT', null)
            ->strange('WTF', null, true, null, true)
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
                'strange' => [
                    'type'    => null,
                    'len'     => 0,
                    'default' => null,
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
            ->id('INTEGER', null, false, 0)
            ->status('SMALLINT', null, true, -1)
            ->uid('BIGINT', null)
            ->cost('FLOAT', null)
            ->discount('REAL', null)
            ->number('NUMERIC', null)
            ->date('DATE', null)
            ->hour('TIME', null)
            ->ts('TIMESTAMP', null, true, 'now()')
            ->name('CHAR', 256, true, null)
            ->fullname('VARCHAR', null)
            ->description('TEXT', null)
            ->strange('WTF', null, true, null, true)
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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Field unknown does not exist in table dc_table');

        $table->unique('pk_unknown', 'unknown');
    }

    public function testPrimaryKeyAlreadyExists(): void
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

        // @phpstan-ignore method.notFound
        $table
            ->id('INTEGER', null, false, 0)
            ->status('SMALLINT', null, true, -1)
            ->uid('BIGINT', null)
            ->cost('FLOAT', null)
            ->discount('REAL', null)
            ->number('NUMERIC', null)
            ->date('DATE', null)
            ->hour('TIME', null)
            ->ts('TIMESTAMP', null, true, 'now()')
            ->name('CHAR', 256, true, null)
            ->fullname('VARCHAR', null)
            ->description('TEXT', null)
            ->strange('WTF', null, true, null, true)
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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Table dc_table already has a primary key');

        $table->primary('pk_uid', 'uid');
    }

    public function testMagicSetFieldError(): void
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

        // @phpstan-ignore method.notFound
        $table
            ->id('INTEGER', null, false, 0)
            ->status('SMALLINT', null, true, -1)
            ->uid('BIGINT', null)
            ->cost('FLOAT', null)
            ->discount('REAL', null)
            ->number('NUMERIC', null)
            ->date('DATE', null)
            ->hour('TIME', null)
            ->ts('TIMESTAMP', null, true, 'now()')
            ->name('CHAR', 256, true, null)
            ->fullname('VARCHAR', null)
            ->description('TEXT', null)
            ->strange('WTF', null, true, null, true)
        ;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid data type weird in schema');

        // @phpstan-ignore method.notFound
        $table->bizarre('weird', 0);
    }
}
