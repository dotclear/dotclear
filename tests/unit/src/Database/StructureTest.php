<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class StructureTest extends TestCase
{
    private \Dotclear\Database\Structure $structure;
    /**
     * @var array<array-key, mixed> $info
     */
    private array $info;

    private function getConnection(string $driver, string $driver_folder, string $syntax): MockObject
    {
        // Build a mock handler for the driver
        $handlerClass = implode('\\', ['Dotclear', 'Schema', 'Database', $driver_folder, 'Handler']);
        // @phpstan-ignore argument.templateType, argument.type
        $mock = $this->getMockBuilder($handlerClass)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'link',
                'driver',
                'syntax',
                'escapeStr',
                'execute',
                'select',
                'schema',
            ])
            ->getMock();

        // Common return values
        $info = [
            'con'  => $mock,
            'cols' => 0,
            'rows' => 0,
            'info' => [
                'name' => [],
                'type' => [],
            ],
        ];

        $mock->method('link')->willReturn($mock);
        $mock->method('driver')->willReturn($driver);
        $mock->method('syntax')->willReturn($syntax);
        $mock->method('escapeStr')->willReturnCallback(fn ($str) => addslashes((string) $str));
        $mock->method('execute')->willReturn(true);
        $mock->method('select')->willReturn(
            $syntax !== 'sqlite' ?
            // @phpstan-ignore argument.type
            new \Dotclear\Database\Record(null, $info) :
            // @phpstan-ignore argument.type
            new \Dotclear\Database\StaticRecord(null, $info)
        );

        return $mock;
    }

    private function getSchema(mixed $con, string $schema_folder): MockObject
    {
        // Build a mock handler for the driver
        $schemaClass = implode('\\', ['Dotclear', 'Schema', 'Database', $schema_folder, 'Schema']);
        // @phpstan-ignore argument.templateType, argument.type
        $mock = $this->getMockBuilder($schemaClass)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'flushStack',
                'getTables',
                'getColumns',
                'getKeys',
                'getIndexes',
                'getReferences',
            ])
            ->getMock();

        // Mock con protected property
        // @phpstan-ignore argument.type
        $reflection          = new ReflectionClass($schemaClass);
        $reflection_property = $reflection->getProperty('con');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($mock, $con);

        // Update $con->schema()
        $con->method('schema')->willReturn($mock);

        // Common return values
        $table_exists = fn ($table) => in_array($table, ['dc_table', 'dc_dc_table']);

        $mock->method('getTables')->willReturnCallback(function (): array {
            $res    = [];
            $tables = $this->structure->getTables();
            if (count($tables)) {
                $res = array_keys($tables);
            }

            return $res;
        });
        $mock->method('getColumns')->willReturnCallback(fn ($table) => $table_exists($table) ? $this->info['dc_table']['columns'] : []);
        $mock->method('getKeys')->willReturnCallback(fn ($table) => $table_exists($table) ? $this->info['dc_table']['keys'] : []);
        $mock->method('getIndexes')->willReturnCallback(fn ($table) => $table_exists($table) ? $this->info['dc_table']['indexes'] : []);
        $mock->method('getReferences')->willReturnCallback(fn ($table) => $table_exists($table) ? $this->info['dc_table']['references'] : []);

        return $mock;
    }

    #[DataProvider('dataProviderTest')]
    public function test(string $driver, string $driver_folder, string $syntax, string $schema): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);

        // @phpstan-ignore argument.type
        $structure = new \Dotclear\Database\Structure($con, 'dc_');

        $this->assertEquals(
            $driver,
            $structure->driver()
        );
        ;

        // Add a table

        $structure->table('table');
        $tables = $structure->getTables();

        $this->assertEquals(
            1,
            count($tables)
        );
        $this->assertEquals(
            ['dc_table'],
            array_keys($tables)
        );

        $table = $tables['dc_table'];

        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertNotNull(
            $table
        );
        $this->assertTrue(
            $structure->tableExists('table')
        );
        ;

        // Use magic to create a second table

        $this->assertEquals(
            [],
            $structure->table_bis->getFields()
        );
        $this->assertEquals(
            [],
            $structure->table->getFields()
        );

        $tables = $structure->getTables();

        $this->assertEquals(
            2,
            count($tables)
        );
        $this->assertEquals(
            ['dc_table', 'dc_table_bis'],
            array_keys($tables)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTest(): array
    {
        return [
            // driver, driver_folder, syntax, schema_folder
            ['mysqli', 'Mysqli', 'mysql', 'Mysql'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'Mysqlimb4'],
            ['pgsql', 'Pgsql', 'postgresql', 'Pgsql'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'PdoSqlite'],
        ];
    }

    /**
     * @param array<array-key, mixed>   $data
     * @param array<array-key, mixed>   $info
     * @param list<string>              $sql
     * @param list<string>              $sql_bis
     */
    #[DataProvider('dataProviderTestSynchronize')]
    public function testSynchronize(string $driver, string $driver_folder, string $syntax, string $schema_folder, array $data, array $info, array $sql, array $sql_bis): void
    {
        if ($syntax === 'sqlite') {
            $this->expectNotToPerformAssertions();
        } else {
            $con    = $this->getConnection($driver, $driver_folder, $syntax);
            $schema = $this->getSchema($con, $schema_folder);

            // Prepare current structure
            // @phpstan-ignore argument.type
            $current_str = new \Dotclear\Database\Structure($con, 'dc_');

            // Prepare new structure (empty)
            // @phpstan-ignore argument.type
            $update_str = new \Dotclear\Database\Structure($con, 'dc_');

            $this->structure = $current_str;
            $this->info      = $info;

            $this->assertEquals(
                0,
                $current_str->synchronize($update_str)
            );

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

            // Expect SQL calls on synchronize
            $matcher = $this->exactly(count($sql));
            $con->expects($matcher)->method('execute')->with(
                $this->callback(function ($query) use ($sql, $matcher) {
                    // @phpstan-ignore method.internalClass
                    $expected = $sql[$matcher->numberOfInvocations() - 1];

                    return $query === $expected;
                })
            );

            $synchro = $current_str->synchronize($update_str);

            $this->assertEquals(
                4,
                $synchro
            );
        }
    }

    /**
     * @param array<array-key, mixed>   $data
     * @param array<array-key, mixed>   $info
     * @param list<string>              $sql
     * @param list<string>              $sql_bis
     */
    #[DataProvider('dataProviderTestSynchronize')]
    public function testSynchronizeWithModifications(string $driver, string $driver_folder, string $syntax, string $schema_folder, array $data, array $info, array $sql, array $sql_bis): void
    {
        if ($syntax === 'sqlite') {
            $this->expectNotToPerformAssertions();
        } else {
            $con    = $this->getConnection($driver, $driver_folder, $syntax);
            $schema = $this->getSchema($con, $schema_folder);

            // Prepare current structure
            // @phpstan-ignore argument.type
            $current_str = new \Dotclear\Database\Structure($con, 'dc_');

            // Prepare new structure (empty)
            // @phpstan-ignore argument.type
            $update_str = new \Dotclear\Database\Structure($con, 'dc_');

            $this->structure = $current_str;
            $this->info      = $info;

            $this->assertEquals(
                0,
                $current_str->synchronize($update_str)
            );

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

            $synchro = $current_str->synchronize($update_str);

            $this->assertEquals(
                4,
                $synchro
            );

            // Test structure modifications (not for SQlite)

            $this->structure = $update_str;

            // Add some stuff to current structure, then run synchronize and test execute queries

            // Prepare new structure
            // @phpstan-ignore argument.type
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

            // @phpstan-ignore method.alreadyNarrowedType
            $this->assertNotNull(
                $update_str->getTables()
            );
            $this->assertTrue(
                $update_str->tableExists($data['table'])
            );
            $this->assertNotNull(
                // @phpstan-ignore method.notFound
                $schema->getTables()
            );
            $this->assertNotNull(
                // @phpstan-ignore method.notFound
                $schema->getColumns($data['table'])
            );
            $this->assertNotNull(
                // @phpstan-ignore method.notFound
                $schema->getKeys($data['table'])
            );
            $this->assertNotNull(
                // @phpstan-ignore method.notFound
                $schema->getIndexes($data['table'])
            );
            $this->assertNotNull(
                // @phpstan-ignore method.notFound
                $schema->getReferences($data['table'])
            );

            // Check new structure

            // @phpstan-ignore method.alreadyNarrowedType
            $this->assertNotNull(
                $update_bis_str->getTables()
            );
            $this->assertTrue(
                $update_bis_str->tableExists($data['table'])
            );

            $matcher_bis = $this->exactly(count($sql_bis));
            $con->expects($matcher_bis)->method('execute')->with(
                $this->callback(function ($query) use ($sql_bis, $matcher_bis) {
                    // @phpstan-ignore method.internalClass
                    $expected = $sql_bis[$matcher_bis->numberOfInvocations() - 1];

                    return $query === $expected;
                })
            );

            // Run synchronize again and test execute queries
            $synchro = $update_str->synchronize($update_bis_str);

            $this->assertEquals(
                4,
                $synchro
            );
        }
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestSynchronize(): array
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
            // driver, driver_folder, syntax_folder, queries
            ['mysqli', 'Mysqli', 'mysql', 'Mysqli', $data, $info,
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
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'Mysqlimb4', $data, $info,
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

            ['pgsql', 'Pgsql', 'postgresql', 'Pgsql', $data, $info,
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

            ['pdosqlite', 'PdoQqlite', 'sqlite', 'PdoSqlite', $data, $info,
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
