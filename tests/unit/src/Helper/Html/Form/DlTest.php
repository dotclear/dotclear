<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class DlTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dl.*?>(?:.*?\n*)?<\/dl>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dl.*?>(?:.*?\n*)?<\/dl>/',
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
        $component = new \Dotclear\Helper\Html\Form\Dl();

        $this->assertEquals(
            'dl',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Dl',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Dl::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl();

        $this->assertEquals(
            'dl',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
