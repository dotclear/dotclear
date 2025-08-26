<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private function getConnection(string $driver, string $syntax): \Dotclear\Database\AbstractHandler
    {
        // Build a mock handler for the driver
        $driverClass = ucfirst($driver);
        $mock        = $this->getMockBuilder("Dotclear\\Database\\Driver\\$driverClass\\Handler")
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
            'info' => null,
            'cols' => 0,
            'rows' => 0,
        ];

        $mock->method('link')->willReturn($mock);
        $mock->method('select')->willReturn(
            $driver !== 'sqlite' ?
            new \Dotclear\Database\Record([], $info) :
            new \Dotclear\Database\StaticRecord([], $info)
        );
        $mock->method('openCursor')->willReturn(new \Dotclear\Database\Cursor($mock, 'dc_table'));
        $mock->method('changes')->willReturn(1);
        $mock->method('escapeStr')->willReturnCallback(fn ($str) => addslashes((string) $str));
        $mock->method('driver')->willReturn($driver);
        $mock->method('syntax')->willReturn($syntax);

        return $mock;
    }

    #[DataProvider('dataProviderTest')]
    public function test(string $driver, string $syntax)
    {
        $con     = $this->getConnection($driver, $syntax);
        $session = new \Dotclear\Database\Session($con, 'dc_session', 'ck_session');

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
    }

    public static function dataProviderTest()
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
