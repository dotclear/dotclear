<?php

# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.html.php';

use atoum;

/**
 * Test the form class
 */
class html extends atoum
{
    /** Simple test. Don't need to test PHP functions
     */
    public function testEscapeHTML()
    {
        $str = '"<>&';
        $this
            ->string(\html::escapeHTML($str))
            ->isEqualTo('&quot;&lt;&gt;&amp;');
        $this
            ->string(\html::escapeHTML(null))
            ->isEqualTo('');
    }

    public function testDecodeEntities()
    {
        $this
            ->string(\html::decodeEntities('&lt;body&gt;', true))
            ->isEqualTo('&lt;body&gt;');
        $this
            ->string(\html::decodeEntities('&lt;body&gt;'))
            ->isEqualTo('<body>');
    }

    /**
     * html::clean is a wrapper of a PHP native function
     * Simple test
     */
    public function testClean()
    {
        $this
            ->string(\html::clean('<b>test</b>'))
            ->isEqualTo('test');
    }

    public function testEscapeJS()
    {
        $this
            ->string(\html::escapeJS('<script>alert("Hello world");</script>'))
            ->isEqualTo('&lt;script&gt;alert(\"Hello world\");&lt;/script&gt;');
    }

    /**
     * html::escapeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testEscapeURL()
    {
        $this
            ->string(\html::escapeURL('https://www.dotclear.org/?q=test&test=1'))
            ->isEqualTo('https://www.dotclear.org/?q=test&amp;test=1');
    }

    /**
     * html::sanitizeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testSanitizeURL()
    {
        $this
            ->string(\html::sanitizeURL('https://www.dotclear.org/'))
            ->isEqualTo('https%3A//www.dotclear.org/');
    }

    /**
     * Test removing host prefix
     */
    public function testStripHostURL()
    {
        $this
            ->string(\html::stripHostURL('https://www.dotclear.org/best-blog-engine/'))
            ->isEqualTo('/best-blog-engine/');

        $this
            ->string(\html::stripHostURL('dummy:/not-well-formed-url.d'))
            ->isEqualTo('dummy:/not-well-formed-url.d');
    }

    public function testAbsoluteURLs()
    {
        \html::$absolute_regs[] = '/(<param\s+name="movie"\s+value=")(.*?)(")/msu';

        $this
            ->string(\html::absoluteURLs('<a href="/best-blog-engine-ever/">Clickme</a>', 'https://dotclear.org/'))
            ->isEqualTo('<a href="https://dotclear.org/best-blog-engine-ever/">Clickme</a>');

        $this
            ->string(\html::absoluteURLs('<a href="best-blog-engine-ever/">Clickme</a>', 'https://dotclear.org/'))
            ->isEqualTo('<a href="https://dotclear.org/best-blog-engine-ever/">Clickme</a>');

        $this
            ->string(\html::absoluteURLs('<a href="#anchor">Clickme</a>', 'https://dotclear.org/'))
            ->isEqualTo('<a href="https://dotclear.org/#anchor">Clickme</a>');

        $this
            ->string(\html::absoluteURLs('<a href="index.php">Clickme</a>', '/'))
            ->isEqualTo('<a href="/index.php">Clickme</a>');

        $this
            ->string(\html::absoluteURLs('<a href="lib">Clickme</a>', '/var/tmp'))
            ->isEqualTo('<a href="/var/lib">Clickme</a>');

        $this
            ->string(\html::absoluteURLs('<param name="movie" value="my-movie.flv" />', 'https://dotclear.org/'))
            ->isEqualTo('<param name="movie" value="https://dotclear.org/my-movie.flv" />');
    }
}
