<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

require_once __DIR__ . '/OAuth2Fixtures.php';

use Dotclear\Helper\OAuth2\Client\Consumer;
use Dotclear\Helper\OAuth2\Client\Exception\InvalidService;
use Dotclear\Helper\OAuth2\Client\Services;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitBadConstructorProvider;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitHttp;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitInvalidIdProvider;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitProvider;
use PHPUnit\Framework\TestCase;

class ServicesTest extends TestCase
{
    public function testProviderRegistrationAndLookup(): void
    {
        $http     = new OAuth2UnitHttp();
        $services = new Services($http);
        $services->addProvider(OAuth2UnitProvider::class);
        $services->addProvider(OAuth2UnitInvalidIdProvider::class);
        $services->addProvider(\stdClass::class);

        $this->assertTrue($services->hasProvider(OAuth2UnitProvider::PROVIDER_ID));
        $this->assertFalse($services->hasProvider(OAuth2UnitInvalidIdProvider::PROVIDER_ID));
        $this->assertFalse($services->hasProvider('missing'));
        $this->assertSame(
            [OAuth2UnitProvider::PROVIDER_ID => OAuth2UnitProvider::class],
            $services->getProviders()
        );

        $consumer = new Consumer([
            'provider' => OAuth2UnitProvider::PROVIDER_ID,
            'key'      => 'client',
            'secret'   => 'secret',
        ]);
        $replacement = new OAuth2UnitHttp();
        $provider   = $services->getProvider($consumer, ['state' => 'state'], $replacement);
        $this->assertInstanceOf(OAuth2UnitProvider::class, $provider);
        $this->assertSame($replacement, $provider->httpInstance());

        $services->addDisabledProvider(OAuth2UnitProvider::PROVIDER_ID);
        $this->assertTrue($services->hasDisabledProvider(OAuth2UnitProvider::PROVIDER_ID));
        $this->assertFalse($services->hasDisabledProvider('missing'));
        try {
            $services->getProvider($consumer);
            $this->fail('Expected a disabled provider to be rejected');
        } catch (InvalidService $exception) {
            $this->assertSame('Failed to load provider "oauth2unit".', $exception->getMessage());
        }
    }

    public function testInvalidProviderRequests(): void
    {
        $services = new Services();
        $services->addProvider(OAuth2UnitBadConstructorProvider::class);

        foreach ([
            new Consumer(['provider' => '']),
            new Consumer(['provider' => 'missing']),
            new Consumer(['provider' => OAuth2UnitBadConstructorProvider::PROVIDER_ID]),
        ] as $consumer) {
            try {
                $services->getProvider($consumer);
                $this->fail('Expected an invalid provider to be rejected');
            } catch (InvalidService $exception) {
                $this->assertStringStartsWith('Failed to load provider "', $exception->getMessage());
            }
        }
    }
}
