<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testEmpty(): void
    {
        $state = new \Dotclear\Helper\OAuth2\Client\State();

        $this->assertFalse(
            $state->check(null)
        );

        $this->assertFalse(
            $state->check('ABCD')
        );
    }

    public function testFull(): void
    {
        $state = new \Dotclear\Helper\OAuth2\Client\State('ABCD');

        $this->assertFalse(
            $state->check(null)
        );

        $this->assertFalse(
            $state->check('abcd')
        );

        $this->assertTrue(
            $state->check('ABCD')
        );
    }
}
