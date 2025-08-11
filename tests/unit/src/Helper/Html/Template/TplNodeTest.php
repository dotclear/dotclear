<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TtplNodeTest extends TestCase
{
    public function test()
    {
        $instance = new \Dotclear\Helper\Html\Template\TplNode();
        $child    = new \Dotclear\Helper\Html\Template\TplNodeText('content');

        $instance->setChildren(new ArrayObject([$child]));

        $this->assertEquals(
            $child->getParent(),
            $instance
        );

        $dump = var_export($instance, true);
        $instance->setClosing();

        $this->assertEquals(
            $dump,
            var_export($instance, true)
        );
    }
}
