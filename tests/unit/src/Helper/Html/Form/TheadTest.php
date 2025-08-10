<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TheadTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<thead.*?>(?:.*?\n*)?<\/thead>/',
            $rendered
        );
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<thead.*?><\/thead>/',
            $rendered
        );
    }

    public function testWithRows()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();
        $component->rows([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<thead.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/thead>/',
            $rendered
        );
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();
        $component->items([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<thead.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/thead>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<thead.*?>(?:.*?\n*)?<\/thead>/',
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
        $component = new \Dotclear\Helper\Html\Form\Thead();

        $this->assertEquals(
            'thead',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Thead',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Thead::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();

        $this->assertEquals(
            'thead',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
