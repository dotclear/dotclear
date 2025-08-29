<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Template;

use PHPUnit\Framework\TestCase;

class TtplNodeTest extends TestCase
{
    public function test(): void
    {
        $instance = new \Dotclear\Helper\Html\Template\TplNode();
        $child    = new \Dotclear\Helper\Html\Template\TplNodeText('content');

        // @phpstan-ignore argument.type
        $instance->setChildren(new \ArrayObject([$child]));

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
