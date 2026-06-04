<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

use Dotclear\Helper\OAuth2\Client\Exception\InvalidClient;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    public function testEmpty(): void
    {
        $this->expectException(InvalidClient::class);

        $consumer = new \Dotclear\Helper\OAuth2\Client\Consumer();

        $this->expectExceptionMessage(
            'Missing client configuration key "provider"'
        );

        $this->assertFalse(
            $consumer->isConfigured()
        );

        $this->assertSame(
            [
                'access_consumer'  => '',
                'refresh_consumer' => '',
                'expiry'           => 0,
                'scope'            => [],
            ],
            $consumer->getConfiguration()
        );
    }

    public function testAlmostEmpty(): void
    {
        $consumer = new \Dotclear\Helper\OAuth2\Client\Consumer(
            [
                'provider' => 'dotclear',
            ]
        );

        $this->assertFalse(
            $consumer->isConfigured()
        );

        $this->assertSame(
            [
                'provider' => 'dotclear',
                'key'      => '',
                'secret'   => '',
                'domain'   => '',
            ],
            $consumer->getConfiguration()
        );
    }

    public function testFull(): void
    {
        $consumer = new \Dotclear\Helper\OAuth2\Client\Consumer(
            [
                'provider' => 'dotclear',
                'key'      => '1234',
                'secret'   => '5678',
                'domain'   => 'dotclear.org',
                'extra'    => true,
            ]
        );

        $this->assertTrue(
            $consumer->isConfigured()
        );

        $this->assertSame(
            [
                'provider' => 'dotclear',
                'key'      => '1234',
                'secret'   => '5678',
                'domain'   => 'dotclear.org',
            ],
            $consumer->getConfiguration()
        );
    }
}
