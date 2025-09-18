<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use PHPUnit\Framework\TestCase;

class AbstractProcessTester extends \Dotclear\Helper\Process\AbstractProcess
{
    public static function init(): bool
    {
        return self::status(true);
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        echo '<p>Hello</p>';
    }
}

class AbstractProcessTest extends TestCase
{
    public function test(): void
    {
        $this->assertEquals(
            true,
            AbstractProcessTester::init()
        );
        $this->assertEquals(
            true,
            AbstractProcessTester::process()
        );
        $this->expectOutputString('<p>Hello</p>');
        AbstractProcessTester::render();
    }
}
