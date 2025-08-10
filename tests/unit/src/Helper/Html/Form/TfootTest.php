<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TFootTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tfoot.*?>(?:.*?\n*)?<\/tfoot>/',
            $rendered
        );
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tfoot.*?><\/tfoot>/',
            $rendered
        );
    }

    public function testWithRows()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();
        $component->rows([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tfoot.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/tfoot>/',
            $rendered
        );
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();
        $component->items([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tfoot.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/tfoot>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tfoot.*?>(?:.*?\n*)?<\/tfoot>/',
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this->assertEquals(
            'tfoot',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Tfoot',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Tfoot::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this->assertEquals(
            'tfoot',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
