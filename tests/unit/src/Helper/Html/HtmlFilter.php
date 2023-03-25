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
 * @tags HtmlFilter
 */
class HtmlFilter extends atoum
{
    public function testTidySimple()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        if (extension_loaded('tidy') && class_exists('tidy')) {
            $this->string($filter->apply('<p>test</I>'))
                ->isIdenticalTo('<p>test</p>' . "\n");
        } else {
            $this->string($filter->apply('<p>test</I>'))
            ->isIdenticalTo('<p>test');
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
            $this->string($filter->apply($str))
                ->isIdenticalTo($validStr . "\n");
        } else {
            $this->string($filter->apply($str), false)
                ->isIdenticalTo($validStrMiniTidy);
        }
    }

    public function testTidyOnerror()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        if (extension_loaded('tidy') && class_exists('tidy')) {
            $this->string($filter->apply('<p onerror="alert(document.domain)">test</I>'))
                ->isIdenticalTo('<p>test</p>' . "\n");
        } else {
            $this->string($filter->apply('<p onerror="alert(document.domain)">test</I>'))
                ->isIdenticalTo('<p>test');
        }
    }

    public function testSimple()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->string($filter->apply('<p>test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testSimpleAttr()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeAttributes('id');

        $this->string($filter->apply('<p id="para">test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testSimpleTagAttr()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeTagAttributes('p', 'id');

        $this->string($filter->apply('<p id="para">test<span id="sp">x</span></I>', false))
            ->isIdenticalTo('<p>test<span id="sp">x</span>');
    }

    public function testSimpleURI()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->string($filter->apply('<img src="ssh://localhost/sample.jpg" />', false))
            ->isIdenticalTo('<img src="#" />');
    }

    public function testSimpleOwnTags()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->setTags(['span' => []]);

        $this->string($filter->apply('<p id="para">test<span id="sp">x</span></I>', false))
            ->isIdenticalTo('test<span id="sp">x</span>');
    }

    public function testRemovedAttr()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeTagAttributes('a', ['href']);

        $this->string($filter->apply('<a href="#" title="test" target="#">test</a>', false))
            ->isIdenticalTo('<a title="test" target="#">test</a>');
    }

    public function testRemovedAttrs()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $filter->removeTagAttributes('a', ['target', 'href']);

        $this->string($filter->apply('<a href="#" title="test" target="#">test</a>', false))
            ->isIdenticalTo('<a title="test">test</a>');
    }

    public function testComplex()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();
        $str    = <<<EOD
            <p>Hello</p>
            <div aria-role="navigation">
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
            EOD;
        $validStr = <<<EODV
            <p>Hello</p>
            <div>
             <p>bla</p>
             <img src="/public/sample.jpg" />
             <p>bla</p>
            </div>
            <div>
             <p>Hi there!</p>
             <div>
              <p>Opps, a mistake
            EODV;
        $this->string($filter->apply($str, false))
            ->isIdenticalTo($validStr);
    }

    public function testComplexWithAria()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter(true);
        $str    = <<<EODA
            <p>Hello</p>
            <div aria-role="navigation">
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
            EODA;
        $validStr = <<<EODVA
            <p>Hello</p>
            <div aria-role="navigation">
             <p>bla</p>
             <img src="/public/sample.jpg" />
             <p>bla</p>
            </div>
            <div>
             <p>Hi there!</p>
             <div>
              <p>Opps, a mistake
            EODVA;
        $this->string($filter->apply($str, false))
            ->isIdenticalTo($validStr);
    }

    public function testOnerror()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->string($filter->apply('<p onerror="alert(document.domain)">test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testAccesskey()
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter();

        $this->string($filter->apply('<a accesskey="x">test</a>', false))
            ->isIdenticalTo('<a accesskey="x">test</a>');
    }

    /**
     * @dataProvider testAllDataProvider
     */
    protected function testAllDataProvider()
    {
        require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'Html', 'HtmlFilter.php']);

        return array_values($dataTest);
    }

    public function testAll($title, $payload, $expected)
    {
        $filter = new \Dotclear\Helper\Html\HtmlFilter(true, true);

        $this->string($filter->apply($payload, false))
            ->isIdenticalTo($expected);
    }
}
