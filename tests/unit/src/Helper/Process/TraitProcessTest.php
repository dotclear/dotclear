<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use PHPUnit\Framework\TestCase;

class TraitProcessTester
{
    use \Dotclear\Helper\Process\TraitProcess;

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

class TraitMiniProcessTester
{
    use \Dotclear\Helper\Process\TraitProcess;

    public static function init(): bool
    {
        return self::status(false);
    }
}

class TraitProcessTest extends TestCase
{
    public function test(): void
    {
        $this->assertEquals(
            true,
            TraitProcessTester::init()
        );
        $this->assertEquals(
            true,
            TraitProcessTester::process()
        );
        $this->expectOutputString('<p>Hello</p>');
        TraitProcessTester::render();
    }

    public function testMini(): void
    {
        $this->assertEquals(
            false,
            TraitMiniProcessTester::init()
        );
        $this->assertEquals(
            false,
            TraitMiniProcessTester::process()
        );
        $this->expectOutputString('');
        TraitMiniProcessTester::render();
    }
}
