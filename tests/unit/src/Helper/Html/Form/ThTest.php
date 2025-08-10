<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ThTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>(?:.*?\n*)?<\/th>/',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<th.*?>Here<\/th>/',
            $rendered
        );
    }

    public function testWithId()
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

    public function testWithColspan()
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

    public function testWithRowspan()
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

    public function testWithHeaders()
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

    public function testWithScope()
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this->assertEquals(
            'th',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
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

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this->assertEquals(
            'th',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Th('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
