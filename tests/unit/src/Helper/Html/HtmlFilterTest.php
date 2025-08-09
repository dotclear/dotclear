<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlFilterTest extends TestCase
{
    public function testTidySimple()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        if (extension_loaded('tidy') && class_exists('tidy')) {
            $this->assertSame(
                '<p>test</p>',
                $filter->apply('<p>test</I>')
            );
        } else {
            $this->assertSame(
                '<p>test',
                $filter->apply('<p>test</I>')
            );
        }
    }

    public function testTidyComplex()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $str    = <<<EODTIDY
            <p>Hello</p>
            <div aria-role="navigation">
             <a href="javascript:alert('bouh!')">Bouh</a>
             <p data-customattribute="will be an error">bla</p>
             <img src="/public/sample.jpg" />
             <p>bla</p>
            </div>
            <div>
             <p>Hi there!</p>
             <div>
              <p>Opps, a mistake</px>
             </div>
            </div>
            EODTIDY;
        $validStr = <<<EODTIDYV
            <p>Hello</p>
            <div><a href="#">Bouh</a>
            <p>bla</p>
            <img src="/public/sample.jpg" />
            <p>bla</p>
            </div>
            <div>
            <p>Hi there!</p>
            <div>
            <p>Opps, a mistake</p>
            </div>
            </div>
            EODTIDYV;
        $validStrMiniTidy = <<<EODTIDYVMT
            <p>Hello</p>
            <div>
             <a href="#">Bouh</a>
             <p>bla</p>
             <img src="/public/sample.jpg" />
             <p>bla</p>
            </div>
            <div>
             <p>Hi there!</p>
             <div>
              <p>Opps, a mistake
            EODTIDYVMT;
        if (extension_loaded('tidy') && class_exists('tidy')) {
            $this->assertSame(
                $validStr,
                $filter->apply($str)
            );
        } else {
            $this->assertSame(
                $validStrMiniTidy,
                $filter->apply($str, false)
            );
        }
    }

    public function testTidyOnerror()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        if (extension_loaded('tidy') && class_exists('tidy')) {
            $this->assertSame(
                '<p>test</p>',
                $filter->apply('<p onerror="alert(document.domain)">test</I>')
            );
        } else {
            $this->assertSame(
                '<p>test',
                $filter->apply('<p onerror="alert(document.domain)">test</I>')
            );
        }
    }

    public function testSimple()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->assertSame(
            '<p>test',
            $filter->apply('<p>test</I>', false)
        );
    }

    public function testSimpleAttr()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeAttributes('id');

        $this->assertSame(
            '<p>test',
            $filter->apply('<p id="para">test</I>', false)
        );
    }

    public function testSimpleTagAttr()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeTagAttributes('p', 'id');

        $this->assertSame(
            '<p>test<span id="sp">x</span>',
            $filter->apply('<p id="para">test<span id="sp">x</span></I>', false)
        );
    }

    public function testSimpleURI()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->assertSame(
            '',
            $filter->apply('<img src="ssh://localhost/sample.jpg" />', false)
        );
    }

    public function testSimpleOwnTags()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->setTags(['span' => []]);

        $this->assertSame(
            'test<span id="sp">x</span>',
            $filter->apply('<p id="para">test<span id="sp">x</span></I>', false)
        );
    }

    public function testRemovedAttr()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeTagAttributes('a', ['href']);

        $this->assertSame(
            '<a title="test" target="#">test</a>',
            $filter->apply('<a href="#" title="test" target="#">test</a>', false)
        );
    }

    public function testRemovedAttrs()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeTagAttributes('a', ['target', 'href']);

        $this->assertSame(
            '<a title="test">test</a>',
            $filter->apply('<a href="#" title="test" target="#">test</a>', false)
        );
    }

    public function testKeepAria()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter(keep_aria: true);
        $str    = <<<EOD
            <p aria-role="navigation" data-test="test" onclick="window.message('test')">text</p>
            EOD;
        $validStr = <<<EODV
            <p aria-role="navigation">text</p>
            EODV;

        $this->assertSame(
            $validStr,
            $filter->apply($str, false)
        );
    }

    public function testKeepData()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter(keep_data: true);
        $str    = <<<EOD
            <p aria-role="navigation" data-test="test" onclick="window.message('test')">text</p>
            EOD;
        $validStr = <<<EODV
            <p data-test="test">text</p>
            EODV;

        $this->assertSame(
            $validStr,
            $filter->apply($str, false)
        );
    }

    public function testKeepJs()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter(keep_js: true);
        $str    = <<<EOD
            <p aria-role="navigation" data-test="test" onclick="window.message('test')">text</p>
            EOD;
        $validStr = <<<EODV
            <p onclick="window.message('test')">text</p>
            EODV;

        $this->assertSame(
            $validStr,
            $filter->apply($str, false)
        );
    }

    public function testOnerror()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->assertSame(
            '<p>test',
            $filter->apply('<p onerror="alert(document.domain)">test</I>', false)
        );
    }

    public function testAccesskey()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->assertSame(
            '<a accesskey="x">test</a>',
            $filter->apply('<a accesskey="x">test</a>', false)
        );
    }

    public static function dataProviderTestAll()
    {
        require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'Html', 'HtmlFilter.php']);

        foreach ($dataTest as $data) {
            yield $data;
        }
    }

    #[DataProvider('dataProviderTestAll')]
    public function testAll($title, $payload, $expected)
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter(true, true);

        $this->assertSame(
            $expected,
            $filter->apply($payload, false)
        );
    }
}
