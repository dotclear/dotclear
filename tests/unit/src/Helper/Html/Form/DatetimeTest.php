<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class DatetimeTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime('my', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="datetime-local".*?>/',
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
            'size="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="1962-05-13T14:45"',
            $rendered
        );
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="datetime-local".*?>/',
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
            'size="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="1962-05-13T14:45"',
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime(null, 'value');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime(['myname','myid'], 'value');
        $component->size(13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="datetime-local".*?>/',
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
            'maxlength="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="1962-05-13T14:45"',
            $rendered
        );
    }

    public function testMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime(['myname','myid'], 'value');
        $component->maxlength(21);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="datetime-local".*?>/',
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
            'size="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="21"',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="1962-05-13T14:45"',
            $rendered
        );
    }

    public function testPattern()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime(['myname','myid'], 'value');
        $component->pattern('[0-9]{2}-[0-9]{2}');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="datetime-local".*?>/',
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
            'size="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{2}-[0-9]{2}"',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="1962-05-13T14:45"',
            $rendered
        );
    }

    public function testPlaceholder()
    {
        $component = new \Dotclear\Helper\Html\Form\Datetime(['myname','myid'], 'value');
        $component->placeholder('2023-03-17');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="datetime-local".*?>/',
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
            'size="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="16"',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="2023-03-17"',
            $rendered
        );
    }
}
