<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public function testEmpty(): void
    {
        $token = new \Dotclear\Helper\OAuth2\Client\Token();

        $this->assertFalse(
            $token->isConfigured()
        );

        $this->assertSame(
            [
                'access_token'  => '',
                'refresh_token' => '',
                'expiry'        => 0,
                'scope'         => [],
            ],
            $token->getConfiguration()
        );

        $this->assertFalse(
            $token->isExpired()
        );

        $this->assertSame(
            0,
            $token->convertToTime()
        );
    }

    public function testFull(): void
    {
        $token = new \Dotclear\Helper\OAuth2\Client\Token(
            [
                'access_token'  => '1234',
                'refresh_token' => '5678',
                'expiry'        => 42,
                'scope'         => ['domain' => true],
                'extra'         => true,
            ]
        );

        $this->assertTrue(
            $token->isConfigured()
        );

        $this->assertSame(
            [
                'access_token'  => '1234',
                'refresh_token' => '5678',
                'expiry'        => 42,
                'scope'         => ['domain' => true],
            ],
            $token->getConfiguration()
        );

        $this->assertTrue(
            $token->isExpired()
        );

        $this->assertTrue(
            1780574149 <= $token->convertToTime(13)
        );
    }
}
