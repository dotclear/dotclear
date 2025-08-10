<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SpanTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Span('TEXT');
        $rendered  = $component->render();

        $this->assertEquals(
            '<span>TEXT</span>',
            $rendered
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Span();

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Span();
        $component
            ->separator(' - ')
            ->items([
                (new \Dotclear\Helper\Html\Form\Link())->href('#')->text('FIRST'),
                (new \Dotclear\Helper\Html\Form\Link())->href('#')->text('SECOND'),
            ]);
        $rendered = $component->render();

        $this->assertEquals(
            '<span><a href="#">FIRST</a> - <a href="#">SECOND</a></span>',
            $rendered
        );
    }
}
