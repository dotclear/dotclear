<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Color('my', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="color".*?>/',
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
        $this->assertStringContainsString(
            'size="7"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="7"',
            $rendered
        );
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Color('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="color".*?>/',
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
        $this->assertStringContainsString(
            'size="7"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="7"',
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Color();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Color(null, 'value');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Color(['myname','myid'], 'value');
        $component->size(13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="color".*?>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="myname"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="myid"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="13"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="7"',
            $rendered
        );
    }

    public function testMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Color(['myname','myid'], 'value');
        $component->maxlength(21);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="color".*?>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="myname"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="myid"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="7"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="21"',
            $rendered
        );
    }
}
