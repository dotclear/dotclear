<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TtplNodeTextTest extends TestCase
{
    public function test()
    {
        $instance = new \Dotclear\Helper\Html\Template\TplNodeText('content');

        $this->assertEquals(
            'TEXT',
            $instance->getTag()
        );
    }
}
