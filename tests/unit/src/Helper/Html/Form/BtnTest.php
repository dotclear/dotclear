<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class BtnTest extends TestCase
{
    /**
     * PHPUnit warns about the testWithText() test, saying: Test code or tested code did not remove its own exception handlers
     * Follow advice given here (https://github.com/laravel/framework/issues/49502#issuecomment-2948464945) to avoid this.
     */
    public function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    public function test(): void
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

    public function testWithText(): void
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

    public function testWithPopovertarget(): void
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

    public function testWithPopovertargetaction(): void
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

    public function testWithoutText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<button.*?><\/button>/',
            $rendered
        );
    }

    public function testWithId(): void
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

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this->assertEquals(
            'button',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Btn',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Btn::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this->assertEquals(
            'button',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('myid', 'My Btn', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
