<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class ImgTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg', 'my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<img .*?>/',
            $rendered
        );
        $this->assertStringNotContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');

        $this->assertEquals(
            'img',
            $component->getDefaultElement()
        );
    }

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');
        $rendered  = $component->render();

        $this->assertEquals(
            '<img src="img.jpg">',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAAlt(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');
        $component->alt('textual alternative');
        $rendered = $component->render();

        $this->assertEquals(
            '<img src="img.jpg" alt="textual alternative">',
            $rendered
        );
    }

    public function testWithAllImageAttributes(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Img('old.jpg', 'image-id');
        $component
            ->src('photo.jpg')
            ->alt('A photograph')
            ->height(400)
            ->loading('lazy')
            ->sizes('(max-width: 600px) 480px, 800px')
            ->srcset('small.jpg 480w, large.jpg 800w')
            ->width(800);

        $this->assertSame(
            '<img src="photo.jpg" alt="A photograph" height="400" loading="lazy" sizes="(max-width: 600px) 480px, 800px" srcset="small.jpg 480w, large.jpg 800w" width="800" id="image-id">',
            $component->render()
        );
    }
}
