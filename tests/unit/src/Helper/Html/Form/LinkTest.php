<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
    }

    public function testWithHref(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->href('#here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
        $this->assertStringContainsString(
            'href="#here"',
            $rendered
        );
    }

    public function testWithDownloadBool(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->download(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
        $this->assertStringContainsString(
            'download',
            $rendered
        );

        $component->download(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
        $this->assertStringNotContainsString(
            'download',
            $rendered
        );
    }

    public function testWithDownloadString(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->download('downloadable_file.txt');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
        $this->assertStringContainsString(
            'download="downloadable_file.txt"',
            $rendered
        );

        $component->download('');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
        $this->assertStringNotContainsString(
            'download="',
            $rendered
        );
    }

    public function testWithText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>Here<\/a>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
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
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this->assertEquals(
            'a',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Link',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Link::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this->assertEquals(
            'a',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Link('my', 'slot');

        $this->assertEquals(
            'slot',
            $component->getElement()
        );
    }
}
