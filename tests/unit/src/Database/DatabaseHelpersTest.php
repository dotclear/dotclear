<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Dotclear\Database\AbstractPdoHandler;
use Dotclear\Database\SessionHandler;
use Dotclear\Exception\DatabaseException;
use Dotclear\Interface\Database\SchemaInterface;
use Dotclear\Schema\Database\PdoSqlite\Handler as SqliteHandler;
use Dotclear\Schema\Database\PdoSqlite\Schema as SqliteSchema;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseTestSqliteHandler extends SqliteHandler
{
    public int $vacuumRequests = 0;

    protected function db_post_connect(PDO $handle): void
    {
    }

    public function vacuum(string $table): void
    {
        ++$this->vacuumRequests;
        parent::vacuum($table);
    }

    public function handle(): mixed
    {
        return $this->link();
    }

    public function schema(): SchemaInterface
    {
        return new SqliteSchema($this);
    }
}

class DatabaseTestGenericPdoHandler extends AbstractPdoHandler
{
    public const HANDLER_NAME = 'SQLite test';
    public const HANDLER_DRIVER = 'pdotest';
    public const HANDLER_SYNTAX = 'sqlite';
    public const HANDLER_PDO = 'sqlite';

    public function __construct()
    {
    }
}

class DatabaseTestUnavailablePdoHandler extends DatabaseTestGenericPdoHandler
{
    public const HANDLER_PDO = 'database_test_unavailable_driver';
}

class DatabaseHelpersTest extends TestCase
{
    public function testHandlerServicesAndSqlHelpers(): void
    {
        DatabaseTestSqliteHandler::precondition();
        $con = new DatabaseTestSqliteHandler('', ':memory:', '', '', false, 'prefix_');

        try {
            $this->assertSame('pdosqlite', $con->driver());
            $this->assertSame('sqlite', $con->syntax());
            $this->assertNotSame('', $con->version());
            $this->assertSame('prefix_', $con->prefix());
            $this->assertSame(':memory:', $con->database());
            $this->assertInstanceOf(PDO::class, $con->link());
            $this->assertInstanceOf(\Dotclear\Schema\Database\PdoSqlite\Schema::class, $con->schema());

            $this->assertSame("strftime('%Y',created)", $con->dateFormat('created', '%Y'));
            $this->assertSame(' LIMIT 5 ', $con->limit(5));
            $this->assertSame(' LIMIT 5 OFFSET 2 ', $con->limit(2, 5));
            $this->assertSame(' LIMIT 5 OFFSET 2 ', $con->limit(['2', '5']));
            $this->assertSame('', $con->limit('invalid'));
            $this->assertSame(' IN (NULL) ', $con->in(null));
            $this->assertSame(" IN ('value') ", $con->in('value'));
            $this->assertSame(" IN ('value',NULL,3) ", $con->in(['value', null, 3, true, new \stdClass()]));
            $this->assertSame(' IN (4) ', $con->in(4));
            $this->assertSame(' ORDER BY id,name DESC ', $con->orderBy('id', ['field' => 'name', 'order' => 'desc']));
            $this->assertSame('', $con->orderBy(['field' => '']));
            $this->assertSame('LOWER(id),LOWER(name)', $con->lexFields('id', 'name'));
            $this->assertSame('first || second', $con->concat('first', 'second'));
            $this->assertSame(["a''b", '', '3'], $con->escape(["a'b", new \stdClass(), 3]));
            $this->assertSame("'a''b'", $con->escapeSystem("a'b"));
            $this->assertSame('1', $con->formatValue(true));
            $this->assertSame('0', $con->formatValue(false));
            $this->assertSame('2.5', $con->formatValue(2.5));
            $this->assertSame('3', $con->formatValue([3, 4]));
            $this->assertSame('', $con->formatValue(new \stdClass()));

            $con->execute('CREATE TABLE sample (id INTEGER PRIMARY KEY, value TEXT)');
            $con->execute("INSERT INTO sample (value) VALUES ('first')");
            $con->execute('CREATE INDEX idx_sample_value ON sample (value)');
            $this->assertSame(1, $con->changes());
            $record = $con->select('SELECT id, value FROM sample');
            $this->assertSame(1, $record->count());
            $this->assertSame('first', $record->field('value'));
            $this->assertSame(['id', 'value'], $record->columns());
            $this->assertSame([], $con->nullRecord()->rows());
            $this->assertSame(' (0)', $con->error());

            $con->writeLock('sample');
            $con->unlock();
            $con->begin();
            $con->rollback();
            $con->begin();
            $con->commit();

            $schema = $con->schema();
            $this->assertContains('sample', $schema->getTables());
            $this->assertArrayHasKey('id', $schema->getColumns('sample'));
            $this->assertCount(1, $schema->getIndexes('sample'));
            $this->assertSame([], $schema->getReferences('sample'));
            $this->assertInstanceOf(\Dotclear\Database\Cursor::class, $con->openCursor('sample'));

            $current = new \Dotclear\Database\Structure($con);
            $current->reverse();
            $this->assertTrue($current->tableExists('sample'));
            $this->assertNotEmpty($current->table('sample')->getIndexes());

            $target = new \Dotclear\Database\Structure($con);
            $targetTable = $target->table('sample');
            foreach ($current->table('sample')->getFields() as $name => $field) {
                $targetTable->field($name, $field['type'], $field['len'], $field['null'], $field['default']);
            }
            $targetTable->field('extra', 'varchar', 20, true, null);
            $this->assertSame(1, $current->synchronize($target));
            $this->assertArrayHasKey('extra', $con->schema()->getColumns('sample'));

            $con->vacuum('sample');
            $this->assertSame(1, $con->vacuumRequests);
        } finally {
            $con->close();
        }

        $this->assertNull($con->link());
    }

    public function testPdoHandlerFailureAndSessionStorage(): void
    {
        try {
            new DatabaseTestSqliteHandler('', '/database/path/that/does/not/exist/test.sqlite');
            $this->fail('Expected an invalid SQLite path to fail');
        } catch (DatabaseException $exception) {
            $this->assertSame('Unable to connect to database', $exception->getMessage());
        }

        $con = new DatabaseTestSqliteHandler('', ':memory:');
        try {
            $con->execute('CREATE TABLE sessions (ses_id TEXT PRIMARY KEY, ses_time INTEGER, ses_start INTEGER, ses_value TEXT)');
            $sessions = new SessionHandler($con, 'sessions', '1 day');

            $this->assertTrue($sessions->open('', 'test'));
            $this->assertSame('', $sessions->read('invalid'));
            $this->assertTrue($sessions->write(str_repeat('a', 40), 'first-data'));
            $this->assertSame('first-data', $sessions->read(str_repeat('a', 40)));
            $this->assertTrue($sessions->write(str_repeat('a', 40), 'updated-data'));
            $this->assertSame('updated-data', $sessions->read(str_repeat('a', 40)));
            $this->assertTrue($sessions->write('invalid-id', 'invalid-data'));
            $this->assertSame('invalid-data', $sessions->read('another-invalid-id'));

            $con->execute("INSERT INTO sessions (ses_id, ses_time, ses_start, ses_value) VALUES ('expired', 1, 1, 'old')");
            $this->assertSame(1, $sessions->gc(60));
            $this->assertSame(1, $con->vacuumRequests);
            $this->assertTrue($sessions->destroy(str_repeat('a', 40)));
            $this->assertSame('', $sessions->read(str_repeat('a', 40)));
            $this->assertSame(2, $con->vacuumRequests);
            $this->assertTrue($sessions->close());
        } finally {
            $con->close();
        }
    }

    public function testPdoHandlerDriverUtilitiesAndErrorPaths(): void
    {
        $pdo = new DatabaseTestGenericPdoHandler();
        $this->assertSame('sqlite:host=dbhost;port=3307;dbname=db;user=user;password=pass;', $pdo->db_dsn('dbhost:3307', 'user', 'pass', 'db'));
        $this->assertSame('sqlite:', $pdo->db_dsn('', '', '', ''));

        try {
            DatabaseTestUnavailablePdoHandler::precondition();
            $this->fail('Expected unavailable PDO driver to fail its precondition');
        } catch (DatabaseException $exception) {
            $this->assertSame('PHP SQLite test PDO driver is not available', $exception->getMessage());
        }

        $con = new DatabaseTestSqliteHandler('', ':memory:');
        try {
            $handle = $con->handle();
            $this->assertSame($handle->getAttribute(PDO::ATTR_SERVER_VERSION), $con->db_version($handle));
            $this->assertSame('', $con->db_version(null));
            $this->assertSame('path', $con->db_search_path(null, 'path'));

            $result = $con->db_query($handle, 'SELECT 42 AS answer');
            $this->assertInstanceOf(\PDOStatement::class, $result);
            $this->assertSame(1, $con->db_num_fields($result));
            $this->assertSame('answer', $con->db_field_name($result, 0));
            $this->assertContains($con->db_field_type($result, 0), ['integer', 'varchar']);
            $this->assertSame(0, $con->db_num_rows($result));
            $this->assertSame([], $con->db_fetch_assoc($result));
            $this->assertFalse($con->db_result_seek($result, 0));
            $this->assertSame(0, $con->db_changes($handle, null));
            $this->assertFalse($con->db_last_error(null));
            $this->assertSame("a\\'b", $con->db_escape_string("a'b"));
            $this->assertSame("a''b", $con->db_escape_string("a'b", $handle));

            $this->assertNull($con->db_query(null, 'SELECT 1'));
            $this->assertNull($con->db_exec(null, 'SELECT 1'));
            $this->assertSame(0, $con->db_num_fields(null));
            $this->assertSame('', $con->db_field_name(null, 0));
            $this->assertSame('', $con->db_field_type(null, 0));
            $this->assertFalse($con->db_fetch_assoc(null));
            $this->assertFalse($con->db_result_seek(null, 0));
            $this->assertSame(0, $con->db_changes(null, null));

            $handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            try {
                $con->db_query($handle, 'INVALID SQL');
                $this->fail('Expected invalid SQL to throw');
            } catch (DatabaseException) {
                $this->addToAssertionCount(1);
            }

            try {
                $pdo->db_pconnect('', '', '', '/no/such/database/directory/test.sqlite');
                $this->fail('Expected invalid persistent connection to throw');
            } catch (DatabaseException $exception) {
                $this->assertSame('Unable to connect to database', $exception->getMessage());
            }

            $pdo->db_write_lock('sample');
            $pdo->db_unlock();

            $temporaryDirectory = sys_get_temp_dir() . '/database-pconnect-' . bin2hex(random_bytes(6));
            $this->assertTrue(mkdir($temporaryDirectory));
            $workingDirectory = getcwd();
            try {
                chdir($temporaryDirectory);
                $persistentHandle = $pdo->db_pconnect('', '', '', ':memory:');
                $this->assertInstanceOf(PDO::class, $persistentHandle);
                $pdo->db_close($persistentHandle);
                unset($persistentHandle);
                $pdo->db_close(null);
            } finally {
                chdir($workingDirectory);
                $persistentDatabase = $temporaryDirectory . '/dbname=:memory:;host=;';
                if (file_exists($persistentDatabase)) {
                    unlink($persistentDatabase);
                }
                rmdir($temporaryDirectory);
            }
        } finally {
            $con->close();
        }
    }
}
