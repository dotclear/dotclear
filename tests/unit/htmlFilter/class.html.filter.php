<?php

# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/html.filter/class.html.filter.php';
require_once CLEARBRICKS_PATH . '/common/lib.html.php';

use atoum;

/**
 * html.filter test.
 */
class htmlFilter extends atoum
{
    public function testTidySimple()
    {
        $filter = new \htmlFilter();

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
        $filter = new \htmlFilter();
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
        $filter = new \htmlFilter();

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
        $filter = new \htmlFilter();

        $this->string($filter->apply('<p>test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testSimpleAttr()
    {
        $filter = new \htmlFilter();
        $filter->removeAttributes('id');

        $this->string($filter->apply('<p id="para">test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testSimpleTagAttr()
    {
        $filter = new \htmlFilter();
        $filter->removeTagAttributes('p', 'id');

        $this->string($filter->apply('<p id="para">test<span id="sp">x</span></I>', false))
            ->isIdenticalTo('<p>test<span id="sp">x</span>');
    }

    public function testSimpleURI()
    {
        $filter = new \htmlFilter();

        $this->string($filter->apply('<img src="ssh://localhost/sample.jpg" />', false))
            ->isIdenticalTo('<img src="#" />');
    }

    public function testSimpleOwnTags()
    {
        $filter = new \htmlFilter();
        $filter->setTags(['span' => []]);

        $this->string($filter->apply('<p id="para">test<span id="sp">x</span></I>', false))
            ->isIdenticalTo('test<span id="sp">x</span>');
    }

    public function testRemovedAttr()
    {
        $filter = new \htmlFilter();
        $filter->removeTagAttributes('a', ['href']);

        $this->string($filter->apply('<a href="#" title="test" target="#">test</a>', false))
            ->isIdenticalTo('<a title="test" target="#">test</a>');
    }

    public function testRemovedAttrs()
    {
        $filter = new \htmlFilter();
        $filter->removeTagAttributes('a', ['target', 'href']);

        $this->string($filter->apply('<a href="#" title="test" target="#">test</a>', false))
            ->isIdenticalTo('<a title="test">test</a>');
    }

    public function testComplex()
    {
        $filter = new \htmlFilter();
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
        $filter = new \htmlFilter(true);
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
        $filter = new \htmlFilter();

        $this->string($filter->apply('<p onerror="alert(document.domain)">test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testAccesskey()
    {
        $filter = new \htmlFilter();

        $this->string($filter->apply('<a accesskey="x">test</a>', false))
            ->isIdenticalTo('<a accesskey="x">test</a>');
    }

    /**
     * @dataProvider testAllDataProvider
     */
    protected function testAllDataProvider()
    {
        require_once __DIR__ . '/../fixtures/data/class.html.filter.php';

        return array_values($dataTest);
    }

    public function testAll($title, $payload, $expected)
    {
        $filter = new \htmlFilter(true, true);

        $this->string($result = $filter->apply($payload, false))
            ->isIdenticalTo($expected);
    }
}
