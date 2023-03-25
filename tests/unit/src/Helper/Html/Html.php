<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\Html;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

/**
 * @tags Html
 */
class Html extends atoum
{
    /** Simple test. Don't need to test PHP functions
     */
    public function testEscapeHTML()
    {
        $str = '"<>&';
        $this
            ->string(\Dotclear\Helper\Html\Html::escapeHTML($str))
            ->isEqualTo('&quot;&lt;&gt;&amp;');
        $this
            ->string(\Dotclear\Helper\Html\Html::escapeHTML(null))
            ->isEqualTo('');
    }

    public function testDecodeEntities()
    {
        $this
            ->string(\Dotclear\Helper\Html\Html::decodeEntities('&lt;body&gt;', true))
            ->isEqualTo('&lt;body&gt;');
        $this
            ->string(\Dotclear\Helper\Html\Html::decodeEntities('&lt;body&gt;'))
            ->isEqualTo('<body>');
    }

    /**
     * Html::clean is a wrapper of a PHP native function
     * Simple test
     */
    public function testClean()
    {
        $this
            ->string(\Dotclear\Helper\Html\Html::clean('<b>test</b>'))
            ->isEqualTo('test');
    }

    public function testEscapeJS()
    {
        $this
            ->string(\Dotclear\Helper\Html\Html::escapeJS('<script>alert("Hello world");</script>'))
            ->isEqualTo('&lt;script&gt;alert(\"Hello world\");&lt;/script&gt;');
    }

    /**
     * Html::escapeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testEscapeURL()
    {
        $this
            ->string(\Dotclear\Helper\Html\Html::escapeURL('https://www.dotclear.org/?q=test&test=1'))
            ->isEqualTo('https://www.dotclear.org/?q=test&amp;test=1');
    }

    /**
     * Html::sanitizeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testSanitizeURL()
    {
        $this
            ->string(\Dotclear\Helper\Html\Html::sanitizeURL('https://www.dotclear.org/'))
            ->isEqualTo('https%3A//www.dotclear.org/');
    }

    /**
     * Test removing host prefix
     */
    public function testStripHostURL()
    {
        $this
            ->string(\Dotclear\Helper\Html\Html::stripHostURL('https://www.dotclear.org/best-blog-engine/'))
            ->isEqualTo('/best-blog-engine/');

        $this
            ->string(\Dotclear\Helper\Html\Html::stripHostURL('dummy:/not-well-formed-url.d'))
            ->isEqualTo('dummy:/not-well-formed-url.d');
    }

    public function testAbsoluteURLs()
    {
        \Dotclear\Helper\Html\Html::$absolute_regs[] = '/(<param\s+name="movie"\s+value=")(.*?)(")/msu';

        $this
            ->string(\Dotclear\Helper\Html\Html::absoluteURLs('<a href="/best-blog-engine-ever/">Clickme</a>', 'https://dotclear.org/'))
            ->isEqualTo('<a href="https://dotclear.org/best-blog-engine-ever/">Clickme</a>');

        $this
            ->string(\Dotclear\Helper\Html\Html::absoluteURLs('<a href="best-blog-engine-ever/">Clickme</a>', 'https://dotclear.org/'))
            ->isEqualTo('<a href="https://dotclear.org/best-blog-engine-ever/">Clickme</a>');

        $this
            ->string(\Dotclear\Helper\Html\Html::absoluteURLs('<a href="#anchor">Clickme</a>', 'https://dotclear.org/'))
            ->isEqualTo('<a href="https://dotclear.org/#anchor">Clickme</a>');

        $this
            ->string(\Dotclear\Helper\Html\Html::absoluteURLs('<a href="index.php">Clickme</a>', '/'))
            ->isEqualTo('<a href="/index.php">Clickme</a>');

        $this
            ->string(\Dotclear\Helper\Html\Html::absoluteURLs('<a href="lib">Clickme</a>', '/var/tmp'))
            ->isEqualTo('<a href="/var/lib">Clickme</a>');

        $this
            ->string(\Dotclear\Helper\Html\Html::absoluteURLs('<param name="movie" value="my-movie.flv" />', 'https://dotclear.org/'))
            ->isEqualTo('<param name="movie" value="https://dotclear.org/my-movie.flv" />');
    }
}
