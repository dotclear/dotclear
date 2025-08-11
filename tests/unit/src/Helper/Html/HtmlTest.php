<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html;

use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    /** Simple test. Don't need to test PHP functions
     */
    public function testEscapeHTML()
    {
        $str = '"<>&';
        $this->assertEquals(
            '&quot;&lt;&gt;&amp;',
            \Dotclear\Helper\Html\Html::escapeHTML($str)
        );

        $this->assertEquals(
            '',
            \Dotclear\Helper\Html\Html::escapeHTML(null)
        );
    }

    public function testDecodeEntities()
    {
        $this->assertEquals(
            '&lt;body&gt;',
            \Dotclear\Helper\Html\Html::decodeEntities('&lt;body&gt;', true)
        );

        $this->assertEquals(
            '<body>',
            \Dotclear\Helper\Html\Html::decodeEntities('&lt;body&gt;')
        );
    }

    /**
     * Html::clean is a wrapper of a PHP native function
     * Simple test
     */
    public function testClean()
    {
        $this->assertEquals(
            'test',
            \Dotclear\Helper\Html\Html::clean('<b>test</b>')
        );
    }

    public function testEscapeJS()
    {
        $this->assertEquals(
            '&lt;script&gt;alert(\"Hello world\");&lt;/script&gt;',
            \Dotclear\Helper\Html\Html::escapeJS('<script>alert("Hello world");</script>')
        );
    }

    /**
     * Html::escapeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testEscapeURL()
    {
        $this->assertEquals(
            'https://www.dotclear.org/?q=test&amp;test=1',
            \Dotclear\Helper\Html\Html::escapeURL('https://www.dotclear.org/?q=test&test=1')
        );
    }

    /**
     * Html::sanitizeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testSanitizeURL()
    {
        $this->assertEquals(
            'https%3A//www.dotclear.org/',
            \Dotclear\Helper\Html\Html::sanitizeURL('https://www.dotclear.org/')
        );
    }

    /**
     * Test removing host prefix
     */
    public function testStripHostURL()
    {
        $this->assertEquals(
            '/best-blog-engine/',
            \Dotclear\Helper\Html\Html::stripHostURL('https://www.dotclear.org/best-blog-engine/')
        );

        $this->assertEquals(
            'dummy:/not-well-formed-url.d',
            \Dotclear\Helper\Html\Html::stripHostURL('dummy:/not-well-formed-url.d')
        );
    }

    public function testAbsoluteURLs()
    {
        \Dotclear\Helper\Html\Html::$absolute_regs[] = '/(<param\s+name="movie"\s+value=")(.*?)(")/msu';

        $this->assertEquals(
            '<a href="https://dotclear.org/best-blog-engine-ever/">Clickme</a>',
            \Dotclear\Helper\Html\Html::absoluteURLs('<a href="/best-blog-engine-ever/">Clickme</a>', 'https://dotclear.org/')
        );

        $this->assertEquals(
            '<a href="https://dotclear.org/best-blog-engine-ever/">Clickme</a>',
            \Dotclear\Helper\Html\Html::absoluteURLs('<a href="best-blog-engine-ever/">Clickme</a>', 'https://dotclear.org/')
        );

        $this->assertEquals(
            '<a href="https://dotclear.org/#anchor">Clickme</a>',
            \Dotclear\Helper\Html\Html::absoluteURLs('<a href="#anchor">Clickme</a>', 'https://dotclear.org/')
        );

        $this->assertEquals(
            '<a href="/index.php">Clickme</a>',
            \Dotclear\Helper\Html\Html::absoluteURLs('<a href="index.php">Clickme</a>', '/')
        );

        $this->assertEquals(
            '<a href="/var/lib">Clickme</a>',
            \Dotclear\Helper\Html\Html::absoluteURLs('<a href="lib">Clickme</a>', '/var/tmp')
        );

        $this->assertEquals(
            '<param name="movie" value="https://dotclear.org/my-movie.flv" />',
            \Dotclear\Helper\Html\Html::absoluteURLs('<param name="movie" value="my-movie.flv" />', 'https://dotclear.org/')
        );
    }

    public function testJsJson()
    {
        $this->assertEquals(
            '<script type="application/json" id="data-data">' . "\n" .
            '{"text":"string","value":42,"flag":true}' . "\n" .
            '</script>' . "\n",
            \Dotclear\Helper\Html\Html::jsJson(
                'data',
                [
                    'text'  => 'string',
                    'value' => 42,
                    'flag'  => true,
                ]
            )
        );
    }
}
