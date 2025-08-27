<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TbodyTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<tbody.*?>(?:.*?\n*)?<\/tbody>/',
            $rendered
        );
    }

    public function testWithEmptyItems(): void
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

    public function testWithRows(): void
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

    public function testWithItems(): void
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

    public function testWithId(): void
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

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this->assertEquals(
            'tbody',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
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

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this->assertEquals(
            'tbody',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
