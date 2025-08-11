<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class ImgTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg', 'my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<img .*?>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');

        $this->assertEquals(
            'img',
            $component->getDefaultElement()
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');
        $rendered  = $component->render();

        $this->assertEquals(
            '<img src="img.jpg">',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAAlt()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');
        $component->alt('textual alternative');
        $rendered = $component->render();

        $this->assertEquals(
            '<img src="img.jpg" alt="textual alternative">',
            $rendered
        );
    }
}
