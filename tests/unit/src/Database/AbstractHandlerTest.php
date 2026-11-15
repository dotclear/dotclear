<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Dotclear\Database\AbstractHandler;
use Dotclear\Database\AbstractSchema;
use Dotclear\Database\Cursor;
use Dotclear\Database\Record;
use PHPUnit\Framework\TestCase;

class Schema extends AbstractSchema
{
    public function db_get_tables(): array
    {
        return [];
    }

    public function db_get_columns(string $table): array
    {
        return [];
    }

    public function db_get_keys(string $table): array
    {
        return [];
    }

    public function db_get_indexes(string $table): array
    {
        return [];
    }

    public function db_get_references(string $table): array
    {
        return [];
    }

    public function db_create_table(string $name, array $fields): void
    {
    }

    public function db_create_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
    }

    public function db_create_primary(string $table, string $name, array $fields): void
    {
    }

    public function db_create_unique(string $table, string $name, array $fields): void
    {
    }

    public function db_create_index(string $table, string $name, string $type, array $fields): void
    {
    }

    public function db_create_reference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, false|string $update, false|string $delete): void
    {
    }

    public function db_alter_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
    }

    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void
    {
    }

    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void
    {
    }

    public function db_alter_index(string $table, string $name, string $newname, string $type, array $fields): void
    {
    }

    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
    }

    public function db_drop_unique(string $table, string $name): void
    {
    }
}

class DatabaseTestResult
{
    public int $pointer = 0;

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string>                $fields
     */
    public function __construct(
        public array $rows,
        public array $fields
    ) {
    }
}

class DatabaseTestAbstractHandler extends AbstractHandler
{
    public const HANDLER_DRIVER = 'test';
    public const HANDLER_SYNTAX = 'test';

    public array $queries = [];
    public int $locks = 0;
    public int $unlocks = 0;
    public bool $persistent = false;
    public bool $fetchResult = true;

    public function db_connect(string $host, string $user, string $password, string $database)
    {
        return (object) ['host' => $host, 'database' => $database];
    }

    public function db_pconnect(string $host, string $user, string $password, string $database)
    {
        $this->persistent = true;

        return $this->db_connect($host, $user, $password, $database);
    }

    public function db_close($handle): void
    {
        $this->__link = null;
    }

    public function db_version($handle): string
    {
        return 'test-version';
    }

    public function db_search_path($handle, $path): string
    {
        return 'searched_' . $path;
    }

    public function db_query($handle, string $query)
    {
        $this->queries[] = $query;

        return new DatabaseTestResult([['id' => 7]], ['id']);
    }

    public function db_exec($handle, string $query)
    {
        $this->queries[] = $query;

        return true;
    }

    public function db_num_fields($res): int
    {
        return $res instanceof DatabaseTestResult ? count($res->fields) : 0;
    }

    public function db_num_rows($res): int
    {
        return $res instanceof DatabaseTestResult ? count($res->rows) : 0;
    }

    public function db_field_name($res, int $position): string
    {
        return $res instanceof DatabaseTestResult ? ($res->fields[$position] ?? '') : '';
    }

    public function db_field_type($res, int $position): string
    {
        return $res instanceof DatabaseTestResult && $position === 0 ? 'integer' : '';
    }

    public function db_fetch_assoc($res): false|array
    {
        if (!$this->fetchResult) {
            return false;
        }

        return $res instanceof DatabaseTestResult ? ($res->rows[$res->pointer++] ?? false) : false;
    }

    public function db_result_seek($res, int $row): bool
    {
        if (!$res instanceof DatabaseTestResult || !isset($res->rows[$row])) {
            return false;
        }

        $res->pointer = $row;

        return true;
    }

    public function db_changes($handle, $res): int
    {
        return 2;
    }

    public function db_last_error($handle): false|string
    {
        return 'test error';
    }

    public function db_escape_string(string $str, $handle = null): string
    {
        return addslashes($str);
    }

    public function db_write_lock(string $table): void
    {
        ++$this->locks;
    }

    public function db_unlock(): void
    {
        ++$this->unlocks;
    }
}

class AbstractHandlerTest extends TestCase
{
    public function testHandlerFacadeAndSqlFormatting(): void
    {
        DatabaseTestAbstractHandler::precondition();
        $con = new DatabaseTestAbstractHandler('localhost', 'database', 'user', 'password', false, 'prefix_');

        $this->assertSame('test', $con->driver());
        $this->assertSame('test', $con->syntax());
        $this->assertSame('test-version', $con->version());
        $this->assertSame('searched_prefix_', $con->prefix());
        $this->assertSame('database', $con->database());
        $this->assertNotNull($con->link());

        $record = $con->select('SELECT id');
        $this->assertInstanceOf(Record::class, $record);
        $this->assertSame(1, $record->count());
        $this->assertSame(7, $record->field('id'));
        $this->assertSame(['id'], $record->columns());
        $this->assertSame([], $con->nullRecord()->rows());
        $this->assertTrue($con->execute('UPDATE items'));
        $this->assertSame(2, $con->changes());
        $this->assertSame('test error', $con->error());

        $this->assertSame("TO_CHAR(created,'YYYY')", $con->dateFormat('created', 'YYYY'));
        $this->assertSame(' LIMIT 4 ', $con->limit([4]));
        $this->assertSame(' LIMIT 4 OFFSET 2 ', $con->limit([2, 4]));
        $this->assertSame('', $con->limit(['invalid']));
        $this->assertSame('', $con->limit(2, 'invalid'));
        $this->assertSame(" IN ('value') ", $con->in('value'));
        $this->assertSame(' IN (1) ', $con->in(1));
        $this->assertSame(" IN ('x',NULL,4) ", $con->in(['x', null, 4, 1.5, false]));
        $this->assertSame(' ORDER BY LOWER(name) DESC,id ', $con->orderBy(['field' => 'name', 'collate' => true, 'order' => 'desc'], 'id'));
        $this->assertSame('', $con->orderBy(['field' => '']));
        $this->assertSame('LOWER(id),LOWER(name)', $con->lexFields('id', 'name'));
        $this->assertSame('LOWER(name)', $con->lexFields(['name']));
        $this->assertSame('left || right', $con->concat('left', 'right'));
        $this->assertSame("a\\'b", $con->escapeStr("a'b"));
        $this->assertSame('"a"b"', $con->escapeSystem('a"b'));
        $this->assertSame("a\\'b", $con->escape("a'b"));
        $this->assertSame(["a\\'b", ''], $con->escape(["a'b", new \stdClass()]));
        $this->assertSame('NULL', $con->formatValue(null));
        $this->assertSame('', $con->formatValue(new \stdClass()));
        $this->assertInstanceOf(Cursor::class, $con->openCursor('items'));
        $con->vacuum('items');

        $this->assertSame(0, $con->locks);
        $con->writeLock('items');
        $con->unlock();
        $this->assertSame(1, $con->locks);
        $this->assertSame(1, $con->unlocks);

        $con->begin();
        $con->writeLock('items');
        $con->unlock();
        $con->commit();
        $con->begin();
        $con->rollback();
        $this->assertSame(['BEGIN', 'COMMIT', 'BEGIN', 'ROLLBACK'], array_slice($con->queries, -4));
        $this->assertSame(1, $con->locks);
        $this->assertSame(1, $con->unlocks);

        $this->assertInstanceOf(Schema::class, $con->schema());

        $persistent = new DatabaseTestAbstractHandler('', 'database', '', '', true);
        $this->assertTrue($persistent->persistent);
        $persistent->close();
        $this->assertNull($persistent->link());

        $con->close();
        $this->assertNull($con->link());
    }

    public function testRecordHandlesMissingRowsAndConnections(): void
    {
        $con = new DatabaseTestAbstractHandler('', 'database');
        $con->fetchResult = false;

        $record = $con->select('SELECT id');
        $this->assertSame(1, $record->count());
        $this->assertNull($record->field('id'));

        $disconnected = new Record(null, [
            'con'  => null,
            'cols' => 1,
            'rows' => 1,
            'info' => ['name' => ['id'], 'type' => ['integer']],
        ]);
        $this->assertFalse($disconnected->index(0));
        $this->assertSame([], $disconnected->rows());

        $con->close();
    }
}
