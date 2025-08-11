<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TdTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<td.*?>(?:.*?\n*)?<\/td>/',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<td.*?>Here<\/td>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Td('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<td.*?>(?:.*?\n*)?<\/td>/',
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
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->colspan(2);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<td.*?>(?:.*?\n*)?<\/td>/',
            $rendered
        );
        $this->assertStringContainsString(
            'colspan=2',
            $rendered
        );
    }

    public function testWithRowspan()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->rowspan(4);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<td.*?>(?:.*?\n*)?<\/td>/',
            $rendered
        );
        $this->assertStringContainsString(
            'rowspan=4',
            $rendered
        );
    }

    public function testWithHeaders()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->headers('id1 id2');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<td.*?>(?:.*?\n*)?<\/td>/',
            $rendered
        );
        $this->assertStringContainsString(
            'headers="id1 id2"',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();

        $this->assertEquals(
            'td',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Td',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Td::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();

        $this->assertEquals(
            'td',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Td('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
