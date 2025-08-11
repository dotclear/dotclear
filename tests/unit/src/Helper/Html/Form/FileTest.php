<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\File('my', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="file" .*?>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\File('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="file" .*?>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'value=',
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\File();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\File(null, 'value');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
