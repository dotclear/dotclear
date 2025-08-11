<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TrTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tr.*?>(?:.*?\n*)?<\/tr>/',
            $rendered
        );
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tr.*?><\/tr>/',
            $rendered
        );
    }

    public function testWithCols()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();
        $component->cols([
            (new \Dotclear\Helper\Html\Form\Th())->text('1st value'),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Td())->text('2nd value'),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tr.*?><th>1st value<\/th><td>2nd value<\/td><\/tr>/',
            $rendered
        );
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();
        $component->items([
            (new \Dotclear\Helper\Html\Form\Th())->text('1st value'),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Td())->text('2nd value'),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tr.*?><th>1st value<\/th><td>2nd value<\/td><\/tr>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tr.*?>(?:.*?\n*)?<\/tr>/',
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
        $component = new \Dotclear\Helper\Html\Form\Tr();

        $this->assertEquals(
            'tr',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Tr',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Tr::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();

        $this->assertEquals(
            'tr',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
