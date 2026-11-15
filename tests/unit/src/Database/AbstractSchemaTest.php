<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Dotclear\Database\AbstractSchema;
use Dotclear\Interface\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase;

class DatabaseTestSchema extends AbstractSchema
{
    /**
     * @var list<array{string, list<mixed>}>
     */
    public array $calls = [];

    /**
     * @var array<string, mixed>
     */
    public array $responses = [];

    private function call(string $method, mixed ...$arguments): mixed
    {
        $this->calls[] = [$method, $arguments];

        return $this->responses[$method] ?? null;
    }

    public function db_get_tables(): array
    {
        return $this->call(__FUNCTION__);
    }

    public function db_get_columns(string $table): array
    {
        return $this->call(__FUNCTION__, $table);
    }

    public function db_get_keys(string $table): array
    {
        return $this->call(__FUNCTION__, $table);
    }

    public function db_get_indexes(string $table): array
    {
        return $this->call(__FUNCTION__, $table);
    }

    public function db_get_references(string $table): array
    {
        return $this->call(__FUNCTION__, $table);
    }

    public function db_create_table(string $name, array $fields): void
    {
        $this->call(__FUNCTION__, $name, $fields);
    }

    public function db_create_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $this->call(__FUNCTION__, $table, $name, $type, $len, $null, $default);
    }

    public function db_create_primary(string $table, string $name, array $fields): void
    {
        $this->call(__FUNCTION__, $table, $name, $fields);
    }

    public function db_create_unique(string $table, string $name, array $fields): void
    {
        $this->call(__FUNCTION__, $table, $name, $fields);
    }

    public function db_create_index(string $table, string $name, string $type, array $fields): void
    {
        $this->call(__FUNCTION__, $table, $name, $type, $fields);
    }

    public function db_create_reference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, false|string $update, false|string $delete): void
    {
        $this->call(__FUNCTION__, $name, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    public function db_alter_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $this->call(__FUNCTION__, $table, $name, $type, $len, $null, $default);
    }

    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void
    {
        $this->call(__FUNCTION__, $table, $name, $newname, $fields);
    }

    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void
    {
        $this->call(__FUNCTION__, $table, $name, $newname, $fields);
    }

    public function db_alter_index(string $table, string $name, string $newname, string $type, array $fields): void
    {
        $this->call(__FUNCTION__, $table, $name, $newname, $type, $fields);
    }

    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $this->call(__FUNCTION__, $name, $newname, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    public function db_drop_unique(string $table, string $name): void
    {
        $this->call(__FUNCTION__, $table, $name);
    }
}

class AbstractSchemaTest extends TestCase
{
    public function testTypeConversionAndSchemaMethodDelegation(): void
    {
        $schema = new DatabaseTestSchema($this->createStub(ConnectionInterface::class));
        $length = 12;
        $default = null;

        $this->assertSame('boolean', $schema->dbt2udt('bool', $length, $default));
        $this->assertSame('numeric', $schema->dbt2udt('decimal', $length, $default));
        $this->assertSame('custom', $schema->dbt2udt('custom', $length, $default));
        $this->assertSame('varchar', $schema->udt2dbt('varchar', $length, $default));

        $schema->responses = [
            'db_get_tables'     => ['items'],
            'db_get_columns'    => ['id' => ['type' => 'int']],
            'db_get_keys'       => ['primary'],
            'db_get_indexes'    => ['index'],
            'db_get_references' => ['reference'],
        ];

        $this->assertSame(['items'], $schema->getTables());
        $this->assertSame(['id' => ['type' => 'int']], $schema->getColumns('items'));
        $this->assertSame(['primary'], $schema->getKeys('items'));
        $this->assertSame(['index'], $schema->getIndexes('items'));
        $this->assertSame(['reference'], $schema->getReferences('items'));

        $schema->createTable('items', ['id' => 'integer']);
        $schema->createField('items', 'name', 'varchar', 20, true, null);
        $schema->createPrimary('items', 'pk_items', ['id']);
        $schema->createUnique('items', 'uk_items_name', ['name']);
        $schema->createIndex('items', 'idx_items_name', 'btree', ['name']);
        $schema->createReference('fk_items', 'items', ['parent_id'], 'parents', ['id'], 'cascade', false);
        $schema->alterField('items', 'name', 'text', null, true, null);
        $schema->alterPrimary('items', 'pk_items', 'pk_items_new', ['id']);
        $schema->alterUnique('items', 'uk_items_name', 'uk_items_name_new', ['name']);
        $schema->alterIndex('items', 'idx_items_name', 'idx_items_name_new', 'btree', ['name']);
        $schema->alterReference('fk_items', 'fk_items_new', 'items', ['parent_id'], 'parents', ['id'], 'cascade', 'restrict');
        $schema->dropUnique('items', 'uk_items_name');
        $schema->flushStack();

        $this->assertSame([
            ['db_get_tables', []],
            ['db_get_columns', ['items']],
            ['db_get_keys', ['items']],
            ['db_get_indexes', ['items']],
            ['db_get_references', ['items']],
            ['db_create_table', ['items', ['id' => 'integer']]],
            ['db_create_field', ['items', 'name', 'varchar', 20, true, null]],
            ['db_create_primary', ['items', 'pk_items', ['id']]],
            ['db_create_unique', ['items', 'uk_items_name', ['name']]],
            ['db_create_index', ['items', 'idx_items_name', 'btree', ['name']]],
            ['db_create_reference', ['fk_items', 'items', ['parent_id'], 'parents', ['id'], 'cascade', false]],
            ['db_alter_field', ['items', 'name', 'text', null, true, null]],
            ['db_alter_primary', ['items', 'pk_items', 'pk_items_new', ['id']]],
            ['db_alter_unique', ['items', 'uk_items_name', 'uk_items_name_new', ['name']]],
            ['db_alter_index', ['items', 'idx_items_name', 'idx_items_name_new', 'btree', ['name']]],
            ['db_alter_reference', ['fk_items', 'fk_items_new', 'items', ['parent_id'], 'parents', ['id'], 'cascade', 'restrict']],
            ['db_drop_unique', ['items', 'uk_items_name']],
        ], $schema->calls);
    }
}
