<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PagerTest extends TestCase
{
    public function test()
    {
        $pager = new \Dotclear\Helper\Html\Pager(0, 10);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertNull($pager->base_url);

        $this->assertEquals(
            'page',
            $pager->var_page
        );

        $this->assertEquals(
            '<strong>%s</strong>',
            $pager->html_cur_page
        );

        $this->assertEquals(
            '-',
            $pager->html_link_sep
        );

        $this->assertEquals(
            '&#171;prev.',
            $pager->html_prev
        );

        $this->assertEquals(
            'next&#187;',
            $pager->html_next
        );

        $this->assertEquals(
            '...',
            $pager->html_prev_grp
        );

        $this->assertEquals(
            '...',
            $pager->html_next_grp
        );

        $this->assertEquals(
            '<strong>1</strong>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10, // nb_per_page
                10, // nb_pages_per_group
                10, // nb_elements
                1,  // nb_pages
                1,  // nb_groups
                1,  // current_page
                0,  // index_start
                9,  // index_end
                1,  // current_group
                1,  // index_group_start
                1,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testLargeNumberOfElements()
    {
        $pager = new \Dotclear\Helper\Html\Pager(0, 999);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2">2</a>-<a href="/list.php?page=3">3</a>-<a href="/list.php?page=4">4</a>-<a href="/list.php?page=5">5</a>-<a href="/list.php?page=6">6</a>-<a href="/list.php?page=7">7</a>-<a href="/list.php?page=8">8</a>-<a href="/list.php?page=9">9</a>-<a href="/list.php?page=10">10</a>&nbsp;<a href="/list.php?page=11">...</a>&nbsp;&nbsp;<a href="/list.php?page=2">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                1,   // current_page
                0,   // index_start
                9,   // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testWithBaseUrl()
    {
        $pager           = new \Dotclear\Helper\Html\Pager(0, 999);
        $pager->base_url = '/list.php?page=%1$d&go';

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2&go">2</a>-<a href="/list.php?page=3&go">3</a>-<a href="/list.php?page=4&go">4</a>-<a href="/list.php?page=5&go">5</a>-<a href="/list.php?page=6&go">6</a>-<a href="/list.php?page=7&go">7</a>-<a href="/list.php?page=8&go">8</a>-<a href="/list.php?page=9&go">9</a>-<a href="/list.php?page=10&go">10</a>&nbsp;<a href="/list.php?page=11&go">...</a>&nbsp;&nbsp;<a href="/list.php?page=2&go">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                1,   // current_page
                0,   // index_start
                9,   // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d&go',
            ],
            $pager->debug()
        )
        ;
    }

    public function testLargeWrongEnv()
    {
        $pager = new \Dotclear\Helper\Html\Pager(120, 999);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2">2</a>-<a href="/list.php?page=3">3</a>-<a href="/list.php?page=4">4</a>-<a href="/list.php?page=5">5</a>-<a href="/list.php?page=6">6</a>-<a href="/list.php?page=7">7</a>-<a href="/list.php?page=8">8</a>-<a href="/list.php?page=9">9</a>-<a href="/list.php?page=10">10</a>&nbsp;<a href="/list.php?page=11">...</a>&nbsp;&nbsp;<a href="/list.php?page=2">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                1,   // current_page
                0,   // index_start
                9,   // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testAlmostMiddlePosition()
    {
        $pager = new \Dotclear\Helper\Html\Pager(54, 999);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            530,
            $pager->index_start
        );

        $this->assertEquals(
            539,
            $pager->index_end
        );

        $this->assertEquals(
            '<a href="/list.php?page=53">&#171;prev.</a>&nbsp;&nbsp;<a href="/list.php?page=41">...</a>&nbsp;<a href="/list.php?page=51">51</a>-<a href="/list.php?page=52">52</a>-<a href="/list.php?page=53">53</a>-<strong>54</strong>-<a href="/list.php?page=55">55</a>-<a href="/list.php?page=56">56</a>-<a href="/list.php?page=57">57</a>-<a href="/list.php?page=58">58</a>-<a href="/list.php?page=59">59</a>-<a href="/list.php?page=60">60</a>&nbsp;<a href="/list.php?page=61">...</a>&nbsp;&nbsp;<a href="/list.php?page=55">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                54,  // current_page
                530, // index_start
                539, // index_end
                6,   // current_group
                51,  // index_group_start
                60,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testAlmostEndPosition()
    {
        $pager = new \Dotclear\Helper\Html\Pager(100, 994);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            990,
            $pager->index_start
        );

        $this->assertEquals(
            993,
            $pager->index_end
        );

        $this->assertEquals(
            '<a href="/list.php?page=99">&#171;prev.</a>&nbsp;&nbsp;<a href="/list.php?page=81">...</a>&nbsp;<a href="/list.php?page=91">91</a>-<a href="/list.php?page=92">92</a>-<a href="/list.php?page=93">93</a>-<a href="/list.php?page=94">94</a>-<a href="/list.php?page=95">95</a>-<a href="/list.php?page=96">96</a>-<a href="/list.php?page=97">97</a>-<a href="/list.php?page=98">98</a>-<a href="/list.php?page=99">99</a>-<strong>100</strong>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                994, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                100, // current_page
                990, // index_start
                993, // index_end
                10,  // current_group
                91,  // index_group_start
                100, // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testLargeWithSessionID()
    {
        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1&mysession=bed414bd8914729827ce73664985e8bf36fb0206';

        $pager = new \Dotclear\Helper\Html\Pager(120, 999);

        // Define user session (as in REQUEST)
        session_id('bed414bd8914729827ce73664985e8bf36fb0206');
        session_name('mysession');

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2">2</a>-<a href="/list.php?page=3">3</a>-<a href="/list.php?page=4">4</a>-<a href="/list.php?page=5">5</a>-<a href="/list.php?page=6">6</a>-<a href="/list.php?page=7">7</a>-<a href="/list.php?page=8">8</a>-<a href="/list.php?page=9">9</a>-<a href="/list.php?page=10">10</a>&nbsp;<a href="/list.php?page=11">...</a>&nbsp;&nbsp;<a href="/list.php?page=2">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                1,   // current_page
                0,   // index_start
                9,   // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testLargeWithFragment()
    {
        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $pager           = new \Dotclear\Helper\Html\Pager(120, 999);
        $pager->fragment = 'fragment';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2#fragment">2</a>-<a href="/list.php?page=3#fragment">3</a>-<a href="/list.php?page=4#fragment">4</a>-<a href="/list.php?page=5#fragment">5</a>-<a href="/list.php?page=6#fragment">6</a>-<a href="/list.php?page=7#fragment">7</a>-<a href="/list.php?page=8#fragment">8</a>-<a href="/list.php?page=9#fragment">9</a>-<a href="/list.php?page=10#fragment">10</a>&nbsp;<a href="/list.php?page=11#fragment">...</a>&nbsp;&nbsp;<a href="/list.php?page=2#fragment">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                1,   // current_page
                0,   // index_start
                9,   // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d#fragment',
            ],
            $pager->debug()
        );
    }

    public function testNoParamInURI()
    {
        $pager = new \Dotclear\Helper\Html\Pager(120, 999);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2">2</a>-<a href="/list.php?page=3">3</a>-<a href="/list.php?page=4">4</a>-<a href="/list.php?page=5">5</a>-<a href="/list.php?page=6">6</a>-<a href="/list.php?page=7">7</a>-<a href="/list.php?page=8">8</a>-<a href="/list.php?page=9">9</a>-<a href="/list.php?page=10">10</a>&nbsp;<a href="/list.php?page=11">...</a>&nbsp;&nbsp;<a href="/list.php?page=2">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                1,   // current_page
                0,   // index_start
                9,   // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testNoPageInURI()
    {
        $pager = new \Dotclear\Helper\Html\Pager(120, 999);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?random=76fc2b98';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            9,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?random=76fc2b98&page=2">2</a>-<a href="/list.php?random=76fc2b98&page=3">3</a>-<a href="/list.php?random=76fc2b98&page=4">4</a>-<a href="/list.php?random=76fc2b98&page=5">5</a>-<a href="/list.php?random=76fc2b98&page=6">6</a>-<a href="/list.php?random=76fc2b98&page=7">7</a>-<a href="/list.php?random=76fc2b98&page=8">8</a>-<a href="/list.php?random=76fc2b98&page=9">9</a>-<a href="/list.php?random=76fc2b98&page=10">10</a>&nbsp;<a href="/list.php?random=76fc2b98&page=11">...</a>&nbsp;&nbsp;<a href="/list.php?random=76fc2b98&page=2">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                1,   // current_page
                0,   // index_start
                9,   // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?random=76fc2b98&page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testWithNbElementsPerPage()
    {
        $pager = new \Dotclear\Helper\Html\Pager(0, 999, 20);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            19,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2">2</a>-<a href="/list.php?page=3">3</a>-<a href="/list.php?page=4">4</a>-<a href="/list.php?page=5">5</a>-<a href="/list.php?page=6">6</a>-<a href="/list.php?page=7">7</a>-<a href="/list.php?page=8">8</a>-<a href="/list.php?page=9">9</a>-<a href="/list.php?page=10">10</a>&nbsp;<a href="/list.php?page=11">...</a>&nbsp;&nbsp;<a href="/list.php?page=2">next&#187;</a>',
            $pager->getLinks()
        );
        $this->assertEquals(
            [
                20,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                50,  // nb_pages
                5,   // nb_groups
                1,   // current_page
                0,   // index_start
                19,  // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testWithNbElementsPerPageAndNbPagesPerGroup()
    {
        $pager = new \Dotclear\Helper\Html\Pager(0, 999, 20, 12);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            0,
            $pager->index_start
        );

        $this->assertEquals(
            19,
            $pager->index_end
        );

        $this->assertEquals(
            '<strong>1</strong>-<a href="/list.php?page=2">2</a>-<a href="/list.php?page=3">3</a>-<a href="/list.php?page=4">4</a>-<a href="/list.php?page=5">5</a>-<a href="/list.php?page=6">6</a>-<a href="/list.php?page=7">7</a>-<a href="/list.php?page=8">8</a>-<a href="/list.php?page=9">9</a>-<a href="/list.php?page=10">10</a>-<a href="/list.php?page=11">11</a>-<a href="/list.php?page=12">12</a>&nbsp;<a href="/list.php?page=13">...</a>&nbsp;&nbsp;<a href="/list.php?page=2">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                20,  // nb_per_page
                12,  // nb_pages_per_group
                999, // nb_elements
                50,  // nb_pages
                5,   // nb_groups
                1,   // current_page
                0,   // index_start
                19,  // index_end
                1,   // current_group
                1,   // index_group_start
                12,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
    }

    public function testWithNegativeValues()
    {
        $pager = new \Dotclear\Helper\Html\Pager(-10, -999, -10, -10);

        // Mock global variable
        $_SERVER['REQUEST_URI'] = '/list.php?page=1';

        $this->assertEquals(
            90,
            $pager->index_start
        );

        $this->assertEquals(
            99,
            $pager->index_end
        );

        $this->assertEquals(
            '<a href="/list.php?page=9">&#171;prev.</a>&nbsp;<a href="/list.php?page=1">1</a>-<a href="/list.php?page=2">2</a>-<a href="/list.php?page=3">3</a>-<a href="/list.php?page=4">4</a>-<a href="/list.php?page=5">5</a>-<a href="/list.php?page=6">6</a>-<a href="/list.php?page=7">7</a>-<a href="/list.php?page=8">8</a>-<a href="/list.php?page=9">9</a>-<strong>10</strong>&nbsp;<a href="/list.php?page=11">...</a>&nbsp;&nbsp;<a href="/list.php?page=11">next&#187;</a>',
            $pager->getLinks()
        );

        $this->assertEquals(
            [
                10,  // nb_per_page
                10,  // nb_pages_per_group
                999, // nb_elements
                100, // nb_pages
                10,  // nb_groups
                10,  // current_page
                90,  // index_start
                99,  // index_end
                1,   // current_group
                1,   // index_group_start
                10,  // index_group_end
                '/list.php?page=%1$d',
            ],
            $pager->debug()
        );
        ;
    }
}
