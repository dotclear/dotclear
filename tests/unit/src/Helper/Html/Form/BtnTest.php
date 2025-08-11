<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BtnTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn(null, 'My Btn');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<button.*?>(?:.*?\n*)?<\/button>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My Btn',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $component->text('My Btn');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<button.*?>(?:.*?\n*)?<\/button>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My Btn',
            $rendered
        );
    }

    public function testWithPopovertarget()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $component->popovertarget('My-Popover');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<button.*?>(?:.*?\n*)?<\/button>/',
            $rendered
        );
        $this->assertStringContainsString(
            'popovertarget="My-Popover"',
            $rendered
        );
    }

    public function testWithPopovertargetaction()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $component->popovertargetaction('show');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<button.*?>(?:.*?\n*)?<\/button>/',
            $rendered
        );
        $this->assertStringContainsString(
            'popovertargetaction="show"',
            $rendered
        );
    }

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<button.*?><\/button>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('myid', 'My Btn');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<button.*?>(?:.*?\n*)?<\/button>/',
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
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this->assertEquals(
            'button',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Btn',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Btn::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this->assertEquals(
            'button',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('myid', 'My Btn', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
