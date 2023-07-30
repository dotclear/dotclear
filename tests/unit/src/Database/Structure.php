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
use atoum\atoum\mock\controller;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

/*
 * @tags StructureDB
 */
class Structure extends atoum
{
    private function getConnection($driver, $syntax)
    {
        $controller              = new controller();
        $controller->__construct = function () {};

        $class_name = sprintf('\\mock\\Dotclear\\Database\\Driver\\%s\\Handler', ucfirst($driver));
        $con        = new $class_name($controller, $driver);

        $this->calling($con)->driver    = $driver;
        $this->calling($con)->syntax    = $syntax;
        $this->calling($con)->escapeStr = fn ($str) => addslashes((string) $str);
        /*
        $this->calling($con)->escapeSystem = function ($str) use ($syntax) {
            $str = (string) $str;
            switch ($syntax) {
                case 'mysql':
                    return '`' . $str . '`';
                case 'postgresql':
                    return '"' . $str . '"';
                case 'sqlite':
                    return '\'' . $str . '\'';
            }

            return '"' . $str . '"';
        };
        */
        $this->calling($con)
            ->methods(
                function ($method) use ($con) {
                    if (in_array($method, ['link'])) {
                        switch ($method) {
                            case 'link':
                                return $con;

                                break;
                        }

                        return true;
                    }
                }
            )
        ;

        return $con;
    }

    private function getSchema($con, $driver)
    {
        $controller              = new controller();
        $controller->__construct = function () {};

        $class_name = sprintf('\\mock\\Dotclear\\Database\\Driver\\%s\\Schema', ucfirst($driver));
        $schema     = new $class_name($con, $controller);

        $this->calling($schema)->flushStack = null;     // Nothing to do

        return $schema;
    }

    public function test($driver, $syntax)
    {
        $con = $this->getConnection($driver, $syntax);

        $structure = new \Dotclear\Database\Structure($con, 'dc_');

        $this
            ->string($structure->driver())
            ->isEqualTo($driver)
        ;

        // Add a table

        $structure->table('table');
        $tables = $structure->getTables();

        $this
            ->integer(count($tables))
            ->isEqualTo(1)
            ->array(array_keys($tables))
            ->isEqualTo(['dc_table'])
            ->given($table = $tables['dc_table'])
            ->object($table)
            ->isNotNull()
            ->boolean($structure->tableExists('table'))
            ->isTrue()
        ;

        // Use magic to create a second table

        $this
            ->array($structure->table_bis->getFields())
            ->isEqualTo([])
            ->array($structure->table->getFields())
            ->isEqualTo([])
            ->given($tables = $structure->getTables())
            ->integer(count($tables))
            ->isEqualTo(2)
            ->array(array_keys($tables))
            ->isEqualTo(['dc_table', 'dc_table_bis'])
        ;
    }

    protected function testDataProvider()
    {
        return [
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }

    public function testSynchronize($driver, $syntax, $data, $info, $sql, $sql_bis)
    {
        /* A mocker:
         *
         * Pour reverse() :
         *
         * - AbstractSchema::init()
         * - AbstractSchema::flushStack() → void
         *
         * - AbstractSchema::getTables()
         * - AbstractSchema::getColumns()
         * - AbstractSchema::getKeys()
         * - AbstractSchema::getIndexes()
         * - AbstractSchema::getReferences()
         *
         * - con->execute() pour contrôler les requêtes SQL (argument $sql) de synchro :
         *   - AbstractSchema::createTable()
         *   - AbstractSchema::createField()
         *   - AbstractSchema::alterField()
         *   - AbstractSchema::createPrimary()
         *   - AbstractSchema::createUnique()
         *   - AbstractSchema::alterPrimary()
         *   - AbstractSchema::alterUnique()
         *   - AbstractSchema::createIndex()
         *   - AbstractSchema::alterIndex()
         *   - AbstractSchema::createReference()
         *   - AbstractSchema::alterReference()
         *
         * (ancienne_structure)->synchronize(nouvelle_structure)
         */

        $con    = $this->getConnection($driver, $syntax);
        $schema = $this->getSchema($con, $driver);

        // Prepare current structure
        $current_str = new \Dotclear\Database\Structure($con, 'dc_');

        // Prepare new structure (empty)
        $update_str = new \Dotclear\Database\Structure($con, 'dc_');

        // Mock some Schema methods
        $this->calling($schema)->getTables = function () use ($current_str) {
            $res    = [];
            $tables = $current_str->getTables();
            if (count($tables)) {
                $res = array_keys($tables);
            }

            return $res;
        };

        $table_exists = fn ($table) => in_array($table, ['dc_table', 'dc_dc_table']);

        $this->calling($schema)->getColumns    = fn ($table) => $table_exists($table) ? $info['dc_table']['columns'] : [];
        $this->calling($schema)->getKeys       = fn ($table) => $table_exists($table) ? $info['dc_table']['keys'] : [];
        $this->calling($schema)->getIndexes    = fn ($table) => $table_exists($table) ? $info['dc_table']['indexes'] : [];
        $this->calling($schema)->getReferences = fn ($table) => $table_exists($table) ? $info['dc_table']['references'] : [];

        // Temporary
        $this->calling($con)->execute = true;
        $info                         = [
            'con'  => $con,
            'cols' => 0,
            'rows' => 0,
            'info' => [
                'name' => [
                ],
                'type' => [
                ],
            ],
        ];
        if ($driver !== 'sqlite') {
            $this->calling($con)->select = new \Dotclear\Database\Record(null, $info);
        } else {
            $this->calling($con)->select = new \Dotclear\Database\StaticRecord(null, $info);
        }

        $this
            ->integer($current_str->synchronize($update_str))
            ->isEqualTo(0)
        ;

        // Add some stuff to update structure, then run synchronize and test execute queries
        $table = $update_str->table($data['table']);
        foreach ($data['fields'] as $field) {
            $table->field(...$field);
        }
        $table->primary(...$data['primary']);
        foreach ($data['unique'] as $unique) {
            $table->unique(...$unique);
        }
        foreach ($data['indexes'] as $index) {
            $table->index(...$index);
        }
        foreach ($data['references'] as $reference) {
            $table->reference(...$reference);
        }

        $this
            //->dump($schema->getTables())
            ->given($synchro = $current_str->synchronize($update_str))
            ->then()
                ->mock($con)->call('execute')
                ->withIdenticalArguments($sql[0])
                ->atLeastOnce()
                ->mock($con)->call('execute')
                ->withIdenticalArguments($sql[1])
                ->once()
                ->mock($con)->call('execute')
                ->withIdenticalArguments($sql[2])
                ->once()
                ->mock($con)->call('execute')
                ->withIdenticalArguments($sql[3])
                ->once()
                ->mock($con)->call('execute')
                ->withIdenticalArguments($sql[4])
                ->once()
        ;

        if (count($sql) > 5) {
            $this
                ->mock($con)->call('execute')
                ->withIdenticalArguments($sql[5])
                ->once()
            ;
        }

        $this
            ->integer($synchro)
            ->isEqualTo(4)
        ;

        if ($driver !== 'sqlite') {
            // Test structure modifications (not for SQlite)

            // Mock some Schema methods
            $this->calling($schema)->getTables = function () use ($update_str) {
                $res    = [];
                $tables = $update_str->getTables();
                if (count($tables)) {
                    $res = array_keys($tables);
                }

                return $res;
            };

            // Add some stuff to current structure, then run synchronize and test execute queries

            // Prepare new structure
            $update_bis_str = new \Dotclear\Database\Structure($con, 'dc_');

            // Clone existing structure
            $table = $update_bis_str->table($data['table']);
            foreach ($data['fields'] as $field) {
                $table->field(...$field);
            }
            $table->primary(...$data['primary']);
            foreach ($data['unique'] as $unique) {
                $table->unique(...$unique);
            }
            foreach ($data['indexes'] as $index) {
                $table->index(...$index);
            }
            foreach ($data['references'] as $reference) {
                $table->reference(...$reference);
            }

            // Fields
            // Add a new field
            $table->field('extra', 'TEXT', null, true, null);
            // Modify an existing field
            $table->field('fullname', 'TEXT', null, true, null);

            // Keys
            // Add a new key
            $table->unique('uk_name', 'name');
            // Modify ab existing key
            $table->unique('uk_uid', 'id', 'name');

            // Indexes
            // Add a new index
            $table->index('idx_cost', 'btree', 'cost', 'discount');
            // Modify an existing index
            $table->index('idx_name', 'btree', 'fullname');

            // References
            // Add a new reference
            $table->reference('fk_uid', 'uid', 'dc_uid', 'uid', 'cascade', 'cascade');
            // Modify an existing reference
            $table->reference('fk_contact', 'name', 'dc_contact', 'id', 'cascade', 'cascade');

            // Check current structure

            $this
                ->variable($update_str->getTables())
                ->isNotNull()
                //->dump(array_keys($update_str->getTables()))
                ->boolean($update_str->tableExists($data['table']))
                ->isTrue()
                ->variable($schema->getTables())
                ->isNotNull()
                //->dump($schema->getTables())
                ->variable($schema->getColumns($data['table']))
                ->isNotNull()
                //->dump($schema->getColumns($data['table']))
                ->variable($schema->getKeys($data['table']))
                ->isNotNull()
                //->dump($schema->getKeys($data['table']))
                ->variable($schema->getIndexes($data['table']))
                ->isNotNull()
                //->dump($schema->getIndexes($data['table']))
                ->variable($schema->getReferences($data['table']))
                ->isNotNull()
                //->dump($schema->getReferences($data['table']))
            ;

            // Check new structure

            $this
                //->dump($update_bis_str)
                ->variable($update_bis_str->getTables())
                ->isNotNull()
                //->dump(array_keys($update_bis_str->getTables()))
                ->boolean($update_bis_str->tableExists($data['table']))
                ->isTrue()
            ;

            // Run synchronize again and test execute queries
            $this
                ->given($synchro = $update_str->synchronize($update_bis_str))
                ->then()
                    ->integer($synchro)
                    ->isEqualTo(4)
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[0])
                    ->once()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[1])
                    ->atLeastOnce()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[2])
                    ->once()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[3])
                    ->once()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[4])
                    ->once()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[5])
                    ->once()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[6])
                    ->once()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($sql_bis[7])
                    ->once()
            ;

            $this
                ->integer($synchro)
                ->isEqualTo(4)
            ;
        }
    }

    protected function testSynchronizeDataProvider()
    {
        $data = [
            'table'  => 'dc_table',
            'fields' => [
                // Name, type, length, null=true, default=false
                ['id', 'INTEGER', null, false, 0],
                ['status', 'SMALLINT', null, true, -1],
                ['uid', 'BIGINT', null, true, null],
                ['cost', 'FLOAT', null, true, null],
                ['discount', 'REAL', null, true, null],
                ['number', 'NUMERIC', null, true, null],
                ['date', 'DATE', null, true, null],
                ['hour', 'TIME', null, true, null],
                ['ts', 'TIMESTAMP', null, true, 'now()'],
                ['name', 'CHAR', 256, true, null],
                ['fullname', 'VARCHAR', null, true, null],
                ['description', 'TEXT', null, true, null],
            ],
            'primary' => [
                'pk_id', 'id',
            ],
            'unique' => [
                ['uk_uid', 'id', 'uid'],
            ],
            'indexes' => [
                ['idx_name', 'btree', 'name', 'fullname'],
            ],
            'references' => [
                ['fk_contact', 'name', 'dc_contact', 'name', 'cascade', 'cascade'],
            ],
        ];

        $info = [];

        // getColumns()
        foreach ($data['fields'] as $field) {
            $info['dc_table']['columns'] = [
                $field[0] => [
                    'type'    => strtolower($field[1]),
                    'len'     => $field[2],
                    'null'    => $field[3],
                    'default' => $field[4],
                ],
            ];
        }

        // getKeys()
        $primary = $data['primary'];
        $fields  = array_slice($primary, 1);

        $info['dc_table']['keys'][] = [
            'name'    => $primary[0],
            'primary' => true,
            'unique'  => true,
            'cols'    => $fields,
        ];
        foreach ($data['unique'] as $unique) {
            $fields = array_slice($unique, 1);

            $info['dc_table']['keys'][] = [
                'name'    => $unique[0],
                'primary' => false,
                'unique'  => true,
                'cols'    => $fields,
            ];
        }

        // getIndexes()
        foreach ($data['indexes'] as $index) {
            $fields = array_slice($index, 2);

            $info['dc_table']['indexes'][] = [
                'name' => $index[0],
                'type' => $index[1],
                'cols' => $fields,
            ];
        }

        // getReferences()
        foreach ($data['references'] as $reference) {
            $info['dc_table']['references'][] = [
                'name'    => $reference[0],
                'c_cols'  => [$reference[1]],
                'p_table' => $reference[2],
                'p_cols'  => [$reference[3]],
                'update'  => $reference[4],
                'delete'  => $reference[5],
            ];
        }

        return [
            // driver, syntax, queries
            ['mysqli', 'mysql', $data, $info,
                [
                    // Init
                    "CREATE TABLE `dc_dc_table` (
`id` integer NOT NULL DEFAULT 0 ,
`status` smallint NULL DEFAULT -1 ,
`uid` bigint NULL DEFAULT NULL,
`cost` double NULL DEFAULT NULL,
`discount` float NULL DEFAULT NULL,
`number` numeric NULL DEFAULT NULL,
`date` date NULL DEFAULT NULL,
`hour` time NULL DEFAULT NULL,
`ts` datetime NULL DEFAULT '1970-01-01 00:00:00' ,
`name` char(256) NULL DEFAULT NULL,
`fullname` varchar NULL DEFAULT NULL,
`description` longtext NULL DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin ",
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT PRIMARY KEY (`id`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT UNIQUE KEY `dc_uk_uid` (`id`,`uid`) ',
                    'ALTER TABLE `dc_dc_table` ADD INDEX `dc_idx_name` USING btree (`name`,`fullname`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT dc_fk_contact FOREIGN KEY (`name`) REFERENCES `dc_dc_contact` (`name`) ON UPDATE cascade ON DELETE cascade ',
                ],
                [
                    // Synchro
                    "CREATE TABLE `dc_dc_table` (
`id` integer NOT NULL DEFAULT 0 ,
`status` smallint NULL DEFAULT -1 ,
`uid` bigint NULL DEFAULT NULL,
`cost` double NULL DEFAULT NULL,
`discount` float NULL DEFAULT NULL,
`number` numeric NULL DEFAULT NULL,
`date` date NULL DEFAULT NULL,
`hour` time NULL DEFAULT NULL,
`ts` datetime NULL DEFAULT '1970-01-01 00:00:00' ,
`name` char(256) NULL DEFAULT NULL,
`fullname` longtext NULL DEFAULT NULL,
`description` longtext NULL DEFAULT NULL,
`extra` longtext NULL DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin ",
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT PRIMARY KEY (`id`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT UNIQUE KEY `dc_uk_uid` (`id`,`name`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT UNIQUE KEY `dc_uk_name` (`name`) ',
                    'ALTER TABLE `dc_dc_table` ADD INDEX `dc_idx_name` USING btree (`fullname`) ',
                    'ALTER TABLE `dc_dc_table` ADD INDEX `dc_idx_cost` USING btree (`cost`,`discount`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT dc_fk_contact FOREIGN KEY (`name`) REFERENCES `dc_dc_contact` (`id`) ON UPDATE cascade ON DELETE cascade ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT dc_fk_uid FOREIGN KEY (`uid`) REFERENCES `dc_dc_uid` (`uid`) ON UPDATE cascade ON DELETE cascade ',
                ],
            ],
            ['mysqlimb4', 'mysql', $data, $info,
                [
                    // Init
                    "CREATE TABLE `dc_dc_table` (
`id` integer NOT NULL DEFAULT 0 ,
`status` smallint NULL DEFAULT -1 ,
`uid` bigint NULL DEFAULT NULL,
`cost` double NULL DEFAULT NULL,
`discount` float NULL DEFAULT NULL,
`number` numeric NULL DEFAULT NULL,
`date` date NULL DEFAULT NULL,
`hour` time NULL DEFAULT NULL,
`ts` datetime NULL DEFAULT '1970-01-01 00:00:00' ,
`name` char(256) NULL DEFAULT NULL,
`fullname` varchar NULL DEFAULT NULL,
`description` longtext NULL DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT PRIMARY KEY (`id`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT UNIQUE KEY `dc_uk_uid` (`id`,`uid`) ',
                    'ALTER TABLE `dc_dc_table` ADD INDEX `dc_idx_name` USING btree (`name`,`fullname`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT dc_fk_contact FOREIGN KEY (`name`) REFERENCES `dc_dc_contact` (`name`) ON UPDATE cascade ON DELETE cascade ',
                ],
                [
                    // Synchro
                    "CREATE TABLE `dc_dc_table` (
`id` integer NOT NULL DEFAULT 0 ,
`status` smallint NULL DEFAULT -1 ,
`uid` bigint NULL DEFAULT NULL,
`cost` double NULL DEFAULT NULL,
`discount` float NULL DEFAULT NULL,
`number` numeric NULL DEFAULT NULL,
`date` date NULL DEFAULT NULL,
`hour` time NULL DEFAULT NULL,
`ts` datetime NULL DEFAULT '1970-01-01 00:00:00' ,
`name` char(256) NULL DEFAULT NULL,
`fullname` longtext NULL DEFAULT NULL,
`description` longtext NULL DEFAULT NULL,
`extra` longtext NULL DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT PRIMARY KEY (`id`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT UNIQUE KEY `dc_uk_uid` (`id`,`name`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT UNIQUE KEY `dc_uk_name` (`name`) ',
                    'ALTER TABLE `dc_dc_table` ADD INDEX `dc_idx_name` USING btree (`fullname`) ',
                    'ALTER TABLE `dc_dc_table` ADD INDEX `dc_idx_cost` USING btree (`cost`,`discount`) ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT dc_fk_contact FOREIGN KEY (`name`) REFERENCES `dc_dc_contact` (`id`) ON UPDATE cascade ON DELETE cascade ',
                    'ALTER TABLE `dc_dc_table` ADD CONSTRAINT dc_fk_uid FOREIGN KEY (`uid`) REFERENCES `dc_dc_uid` (`uid`) ON UPDATE cascade ON DELETE cascade ',
                ],
            ],

            ['pgsql', 'postgresql', $data, $info,
                [
                    // Init
                    'CREATE TABLE "dc_dc_table" (
id integer NOT NULL DEFAULT 0 ,
status smallint NULL DEFAULT -1 ,
uid bigint NULL DEFAULT NULL,
cost float NULL DEFAULT NULL,
discount real NULL DEFAULT NULL,
number numeric NULL DEFAULT NULL,
date date NULL DEFAULT NULL,
hour time NULL DEFAULT NULL,
ts timestamp NULL DEFAULT now() ,
name char(256) NULL DEFAULT NULL,
fullname varchar NULL DEFAULT NULL,
description text NULL DEFAULT NULL
)',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_pk_id PRIMARY KEY (id) ',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_uk_uid UNIQUE (id,uid) ',
                    'CREATE INDEX dc_idx_name ON dc_dc_table USING btree(name,fullname) ',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_fk_contact FOREIGN KEY (name) REFERENCES dc_dc_contact (name) ON UPDATE cascade ON DELETE cascade ',
                ],
                [
                    // Synchro
                    'CREATE TABLE "dc_dc_table" (
id integer NOT NULL DEFAULT 0 ,
status smallint NULL DEFAULT -1 ,
uid bigint NULL DEFAULT NULL,
cost float NULL DEFAULT NULL,
discount real NULL DEFAULT NULL,
number numeric NULL DEFAULT NULL,
date date NULL DEFAULT NULL,
hour time NULL DEFAULT NULL,
ts timestamp NULL DEFAULT now() ,
name char(256) NULL DEFAULT NULL,
fullname text NULL DEFAULT NULL,
description text NULL DEFAULT NULL,
extra text NULL DEFAULT NULL
)',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_pk_id PRIMARY KEY (id) ',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_uk_uid UNIQUE (id,name) ',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_uk_name UNIQUE (name) ',
                    'CREATE INDEX dc_idx_name ON dc_dc_table USING btree(fullname) ',
                    'CREATE INDEX dc_idx_cost ON dc_dc_table USING btree(cost,discount) ',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_fk_contact FOREIGN KEY (name) REFERENCES dc_dc_contact (id) ON UPDATE cascade ON DELETE cascade ',
                    'ALTER TABLE dc_dc_table ADD CONSTRAINT dc_fk_uid FOREIGN KEY (uid) REFERENCES dc_dc_uid (uid) ON UPDATE cascade ON DELETE cascade ',
                ],
            ],

            ['sqlite', 'sqlite', $data, $info,
                [
                    // Init
                    "CREATE TABLE dc_dc_table (id integer NOT NULL DEFAULT 0 , status integer NULL DEFAULT -1 , uid integer NULL DEFAULT NULL, cost float NULL DEFAULT NULL, discount real NULL DEFAULT NULL, number numeric NULL DEFAULT NULL, date timestamp NULL DEFAULT NULL, hour timestamp NULL DEFAULT NULL, ts timestamp NULL DEFAULT '1970-01-01 00:00:00' , name char(256) NULL DEFAULT NULL, fullname varchar NULL DEFAULT NULL, description text NULL DEFAULT NULL, CONSTRAINT dc_pk_id PRIMARY KEY (id), CONSTRAINT dc_uk_uid UNIQUE (id,uid))",
                    'CREATE INDEX dc_idx_name ON dc_dc_table (name,fullname)',
                    "CREATE TRIGGER bir_dc_fk_contact BEFORE INSERT ON dc_dc_table FOR EACH ROW BEGIN SELECT RAISE(ROLLBACK,'insert on table \"dc_dc_table\" violates foreign key constraint \"dc_fk_contact\"') WHERE NEW.name IS NOT NULL AND (SELECT name FROM dc_dc_contact WHERE name = NEW.name) IS NULL; END;",
                    "CREATE TRIGGER bur_dc_fk_contact BEFORE UPDATE ON dc_dc_table FOR EACH ROW BEGIN SELECT RAISE(ROLLBACK,'update on table \"dc_dc_table\" violates foreign key constraint \"dc_fk_contact\"') WHERE NEW.name IS NOT NULL AND (SELECT name FROM dc_dc_contact WHERE name = NEW.name) IS NULL; END;",
                    'CREATE TRIGGER aur_dc_fk_contact AFTER UPDATE ON dc_dc_contact FOR EACH ROW BEGIN UPDATE dc_dc_table SET name = NEW.name WHERE name = OLD.name; END;',
                    'CREATE TRIGGER bdr_dc_fk_contact BEFORE DELETE ON dc_dc_contact FOR EACH ROW BEGIN DELETE FROM dc_dc_table WHERE name = OLD.name; END;',
                ],
                [
                    // Synchro
                    // No synchro with SQLite
                ],
            ],
        ];
    }
}
