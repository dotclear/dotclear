<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use PHPUnit\Framework\TestCase;

class TraitDynamicPropertiesTester
{
    use \Dotclear\Helper\TraitDynamicProperties;
}

class TraitDynamicPropertiesTest extends TestCase
{
    public function test(): void
    {
        $tdp = new TraitDynamicPropertiesTester();

        $this->assertNull(
            // @phpstan-ignore property.notFound
            $tdp->myProperty
        );
        $this->assertFalse(
            isset($tdp->myProperty)
        );

        $tdp->myProperty = 42;
        $this->assertEquals(
            42,
            $tdp->myProperty
        );
        $this->assertTrue(
            isset($tdp->myProperty)
        );

        unset($tdp->myProperty);
        $this->assertNull(
            // @phpstan-ignore property.notFound
            $tdp->myProperty
        );
        $this->assertFalse(
            isset($tdp->myProperty)
        );
    }
}
