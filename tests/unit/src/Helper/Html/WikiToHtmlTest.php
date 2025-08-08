<?php

use PHPUnit\Framework\TestCase;

class WikiToHtmlTest extends TestCase
{
    public function testHelp()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $help = $wiki->help();
        $this->assertNotEmpty($help);
    }
}
