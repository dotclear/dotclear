<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TbodyTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tbody.*?>(?:.*?\n*)?<\/tbody>/',
            $rendered
        );
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tbody.*?><\/tbody>/',
            $rendered
        );
    }

    public function testWithRows()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();
        $component->rows([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tbody.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/tbody>/',
            $rendered
        );
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();
        $component->items([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\None()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tbody.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/tbody>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tbody.*?>(?:.*?\n*)?<\/tbody>/',
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
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this->assertEquals(
            'tbody',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Tbody',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Tbody::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this->assertEquals(
            'tbody',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
