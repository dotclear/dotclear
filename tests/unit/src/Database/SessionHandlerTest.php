<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Dotclear\Database\SessionHandler;
use Dotclear\Schema\Database\PdoSqlite\Handler as SqliteHandler;
use PDO;
use PHPUnit\Framework\TestCase;

class SessionHandlerTestSqliteHandler extends SqliteHandler
{
    protected function db_post_connect(PDO $handle): void
    {
    }
}

class SessionHandlerTest extends TestCase
{
    private SessionHandlerTestSqliteHandler $con;

    private SessionHandler $handler;

    protected function setUp(): void
    {
        SessionHandlerTestSqliteHandler::precondition();

        $this->con = new SessionHandlerTestSqliteHandler('', ':memory:');
        $this->con->execute('CREATE TABLE sessions (ses_id TEXT PRIMARY KEY, ses_time INTEGER, ses_start INTEGER, ses_value TEXT)');
        $this->handler = new SessionHandler($this->con, 'sessions', '1 day');
    }

    protected function tearDown(): void
    {
        $this->con->close();
    }

    public function testCreateSidReturnsDotclearSessionIdentifier(): void
    {
        $id = $this->handler->create_sid();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $id);
    }

    public function testValidateIdChecksFormatAndStorage(): void
    {
        $existingId = str_repeat('a', 40);
        $this->con->execute("INSERT INTO sessions (ses_id, ses_time, ses_start, ses_value) VALUES ('$existingId', 1, 1, 'session-data')");

        $this->assertTrue($this->handler->validateId($existingId));
        $this->assertFalse($this->handler->validateId(str_repeat('b', 40)));
        $this->assertFalse($this->handler->validateId('invalid-id'));
    }

    public function testUpdateTimestampOnlyUpdatesExistingSessionTimestamp(): void
    {
        $existingId = str_repeat('a', 40);
        $this->con->execute("INSERT INTO sessions (ses_id, ses_time, ses_start, ses_value) VALUES ('$existingId', 1, 1, 'session-data')");

        $this->assertTrue($this->handler->updateTimestamp($existingId, 'session-data'));

        $record = $this->con->select("SELECT ses_time, ses_value FROM sessions WHERE ses_id = '$existingId'");
        $updatedAt = (int) $record->field('ses_time');
        $this->assertGreaterThan(1, $updatedAt);
        $this->assertLessThanOrEqual(time(), $updatedAt);
        $this->assertSame('session-data', $record->field('ses_value'));
        $this->assertFalse($this->handler->updateTimestamp(str_repeat('b', 40), 'session-data'));
        $this->assertFalse($this->handler->updateTimestamp('invalid-id', 'session-data'));
    }
}
