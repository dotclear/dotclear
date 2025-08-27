<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class ThTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>(?:.*?\n*)?<\/th>/',
            $rendered
        );
    }

    public function testWithText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>Here<\/th>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>(?:.*?\n*)?<\/th>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="myid"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="myid"',
            $rendered
        );
    }

    public function testWithColspan(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->colspan(2);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>(?:.*?\n*)?<\/th>/',
            $rendered
        );
        $this->assertStringContainsString(
            'colspan=2',
            $rendered
        );
    }

    public function testWithRowspan(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->rowspan(4);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>(?:.*?\n*)?<\/th>/',
            $rendered
        );
        $this->assertStringContainsString(
            'rowspan=4',
            $rendered
        );
    }

    public function testWithHeaders(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->headers('id1 id2');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>(?:.*?\n*)?<\/th>/',
            $rendered
        );
        $this->assertStringContainsString(
            'headers="id1 id2"',
            $rendered
        );
    }

    public function testWithScope(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->scope('row');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>(?:.*?\n*)?<\/th>/',
            $rendered
        );
        $this->assertStringContainsString(
            'scope="row"',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this->assertEquals(
            'th',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Th',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Th::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this->assertEquals(
            'th',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Th('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
