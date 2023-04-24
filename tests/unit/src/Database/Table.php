<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

// This statement may broke class mocking system:
// declare(strict_types=1);

namespace tests\unit\Dotclear\Database;

use atoum;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

/*
 * @tags TableDB
 */
class Table extends atoum
{
    public function test()
    {
        $table = new \Dotclear\Database\Table('dc_table');

        // Fields

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

        $this
            // Fields
            ->array($table->getFields())
            ->isEqualTo([
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
            ])
            ->exception(function () use ($table) {
                $table->bizarre('weird', 0);
            })
            ->hasMessage('Invalid data type weird in schema')
            ->boolean($table->fieldExists('id'))
            ->isTrue()
            ->boolean($table->fieldExists('unknown'))
            ->isFalse()
        ;

        // Primary key

        $table
            ->primary('pk_id', 'id')
        ;

        $this
            ->array($table->getKeys())
            ->isEqualTo([
                'pk_id' => [
                    'type' => 'primary',
                    'cols' => [
                        'id',
                    ],
                ],
            ])
            ->exception(function () use ($table) {
                $table->primary('pk_uid', 'uid');
            })
            ->hasMessage('Table dc_table already has a primary key')
            ->string($table->keyExists('pk_id', 'primary', ['id']))
            ->isEqualTo('pk_id')
            ->boolean($table->keyExists('pk_uid', 'primary', ['uid']))
            ->isFalse()
        ;

        // Unique keys

        $table
            ->unique('uk_uid', 'id', 'uid')
        ;

        $this
            ->array($table->getKeys())
            ->isEqualTo([
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
            ])
            ->exception(function () use ($table) {
                $table->unique('pk_unknown', 'unknown');
            })
            ->hasMessage('Field unknown does not exist in table dc_table')
            ->string($table->keyExists('uk_uid', 'unique', ['id', 'uid']))
            ->isEqualTo('uk_uid')
            ->string($table->keyExists('pk_bis', 'primary', ['id']))
            ->isEqualTo('pk_id')
            ->string($table->keyExists('uk_bis', 'unique', ['id', 'uid']))
            ->isEqualTo('uk_uid')
            ->boolean($table->keyExists('pk_unknown', 'unique', ['unknown']))
            ->isFalse()
        ;

        // Indexes

        $table
            ->index('idx_name', 'btree', 'name', 'fullname')
        ;

        $this
            ->array($table->getIndexes())
            ->isEqualTo([
                'idx_name' => [
                    'type' => 'btree',
                    'cols' => [
                        'name',
                        'fullname',
                    ],
                ],
            ])
            ->string($table->indexExists('idx_name', 'btree', ['name', 'fullname']))
            ->isEqualTo('idx_name')
            ->string($table->indexExists('idx_bis', 'btree', ['name', 'fullname']))
            ->isEqualTo('idx_name')
            ->boolean($table->indexExists('idx_unknown', 'btree', ['id', 'uid']))
            ->isFalse()
        ;

        // References

        $table
            ->reference('fk_contact', 'name', 'dc_contact', 'name', 'cascade', 'cascade');
        ;

        $this
            ->array($table->getReferences())
            ->isEqualTo([
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
            ])
            ->string($table->referenceExists('fk_contact', ['name'], 'dc_contact', ['name']))
            ->isEqualTo('fk_contact')
            ->string($table->referenceExists('fk_bis', ['name'], 'dc_contact', ['name']))
            ->isEqualTo('fk_contact')
            ->boolean($table->referenceExists('fk_unknown', ['id'], 'dc_report', ['uid']))
            ->isFalse()
        ;
    }
}
