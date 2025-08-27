<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TFootTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tfoot.*?>(?:.*?\n*)?<\/tfoot>/',
            $rendered
        );
    }

    public function testWithEmptyItems(): void
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

    public function testWithRows(): void
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

    public function testWithItems(): void
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

    public function testWithId(): void
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

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this->assertEquals(
            'tfoot',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
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

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this->assertEquals(
            'tfoot',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
