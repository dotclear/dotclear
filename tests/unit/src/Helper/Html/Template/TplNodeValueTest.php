<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TtplNodeValueTest extends TestCase
{
    public function test()
    {
        $instance = new \Dotclear\Helper\Html\Template\TplNodeValue('tag', ['attr' => true], 'str_attr');

        $this->assertEquals(
            'tag',
            $instance->getTag()
        );
    }
}
