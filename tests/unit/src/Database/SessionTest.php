<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private function getConnection(string $driver, string $syntax): MockObject
    {
        // Build a mock handler for the driver
        $driverClass  = ucfirst($driver);
        $handlerClass = implode('\\', ['Dotclear', 'Schema', 'Database', $driverClass, 'Handler']);
        // @phpstan-ignore argument.templateType, argument.type
        $mock = $this->getMockBuilder($handlerClass)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'link',
                'select',
                'openCursor',
                'changes',
                'vacuum',
                'escapeStr',
                'driver',
                'syntax',
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
        $mock->method('select')->willReturn(
            $driver !== 'sqlite' ?
            // @phpstan-ignore argument.type
            new \Dotclear\Database\Record([], $info) :
            // @phpstan-ignore argument.type
            new \Dotclear\Database\StaticRecord([], $info)
        );
        // @phpstan-ignore argument.type
        $mock->method('openCursor')->willReturn(new \Dotclear\Database\Cursor($mock, 'dc_table'));
        $mock->method('changes')->willReturn(1);
        $mock->method('escapeStr')->willReturnCallback(fn ($str) => addslashes((string) $str));
        $mock->method('driver')->willReturn($driver);
        $mock->method('syntax')->willReturn($syntax);

        return $mock;
    }

    #[DataProvider('dataProviderTest')]
    public function test(string $driver, string $syntax): void
    {
        $con = $this->getConnection($driver, $syntax);
        // @phpstan-ignore argument.type
        $session = new \Dotclear\Database\Session($con, 'dc_session', 'ck_session', ttl: '60 minutes');

        $session->start();

        $this->assertEquals(
            'ck_session',
            session_name()
        );
        $this->assertEquals(
            ['ck_session', 'mycookie', -1, '/', '', false],
            $session->getCookieParameters('mycookie', -1)
        );

        $session->destroy();

        $session->createFromCookieName('mycookie');
        $session->start();

        $this->assertEquals(
            ['ck_session', 'mycookie', -1, '/', '', false],
            $session->getCookieParameters('mycookie', -1)
        );

        $session->destroy();
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTest(): array
    {
        return [
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }
}
