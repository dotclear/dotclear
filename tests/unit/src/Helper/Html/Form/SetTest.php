<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/(?:.*?\n*)?/',
            $rendered
        );
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '//',
            $rendered
        );
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $component->items([
            new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
            new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/1st value2nd value/',
            $rendered
        );
    }

    public function testWithItemsWithSeparator()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $component
            ->separator('---')
            ->items(
                [
                    new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
                    new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
                ]
            );
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/1st value---2nd value/',
            $rendered
        );
    }

    public function testWithItemsWithFormat()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $component
            ->format('[%s]')
            ->items(
                [
                    new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
                    new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
                ]
            );
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/\[1st value\]\[2nd value\]/',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();

        $this->assertEquals(
            '',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Set',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Set::class,
            $component->getType()
        );
    }
}
