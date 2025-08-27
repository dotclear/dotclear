<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class StrongTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Strong('TEXT');
        $rendered  = $component->render();

        $this->assertEquals(
            '<strong>TEXT</strong>',
            $rendered
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Strong();

        $this->assertEquals(
            'strong',
            $component->getElement()
        );
    }

    public function testWithItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Strong();
        $component
            ->separator(' - ')
            ->items([
                (new \Dotclear\Helper\Html\Form\Link())->href('#')->text('FIRST'),
                (new \Dotclear\Helper\Html\Form\Link())->href('#')->text('SECOND'),
            ]);
        $rendered = $component->render();

        $this->assertEquals(
            '<strong><a href="#">FIRST</a> - <a href="#">SECOND</a></strong>',
            $rendered
        );
    }
}
