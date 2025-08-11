<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg', 'my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="image" .*?>/',
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
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg', 'my');

        $this->assertEquals(
            'input',
            $component->getDefaultElement()
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAAlt()
    {
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg', 'my');
        $component->alt('textual alternative');
        $rendered = $component->render();

        $this->assertEquals(
            '<input type="image" name="my" id="my" src="img.jpg" alt="textual alternative">',
            $rendered
        );
    }
}
