<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

use Dotclear\Helper\OAuth2\Client\Exception\InvalidResponse;
use PHPUnit\Framework\TestCase;

class AbstractProviderTester extends \Dotclear\Helper\OAuth2\Client\Provider
{
}

class AbstractProviderTest extends TestCase
{
    public function testBasic(): void
    {
        $consumer = new \Dotclear\Helper\OAuth2\Client\Consumer([
            'provider' => 'dotclear',
            'key'      => '1234',
            'secret'   => '5678',
            'domain'   => 'dotclear.org',
        ]);

        $http = new \Dotclear\Helper\OAuth2\Client\Http();

        $provider = new AbstractProviderTester(
            $consumer,
            [
                'state' => 'ABCD',
            ],
            $http
        );

        $this->assertSame(
            'OAUTH2',
            $provider->getProtocol()
        );

        $this->assertSame(
            'undefined',
            $provider->getId()
        );

        $this->assertSame(
            'Undefined',
            $provider->getName()
        );

        $this->assertSame(
            'no description available',
            $provider->getDescription()
        );

        $this->assertSame(
            'images/oauth2/undefined.svg',
            $provider->getIcon()
        );

        $this->assertSame(
            '',
            $provider->getConsoleUrl()
        );

        $this->assertFalse(
            $provider->requireDomain()
        );

        $this->assertSame(
            171,
            strlen($provider->getPKCEVerifier())
        );

        $this->assertSame(
            '47DEQpj8HBSa-_TImW-5JCeuQeRkm5NMpJWZG3hSuFU',
            $provider->getPKCEChallenge('')
        );

        $this->assertSame(
            'jhqF5e7oIof2j74nrwF5wwYxZpLqMZIxQn-N8vub0gA',
            $provider->getPKCEChallenge('dotclear')
        );

        $this->assertSame(
            '',
            $provider->getRedirectUrl()
        );

        $this->assertStringStartsWith(
            '?client_id=1234&redirect_uri=&response_type=code&state=',
            $provider->buildAuthorizeUrl()
        );

        $this->assertSame(
            '',
            $provider->getAuthorizeUrl()
        );

        /*
        $this->expectException(InvalidResponse::class);

        $token = $provider->requestAccessToken([
            'state' => 'ABCD',
            'code'  => '200',
        ]);

        $this->assertFalse(
            $token->isConfigured()
        );
        */

        $this->assertSame(
            '',
            $provider->getAccessTokenUri()
        );

        $this->assertSame(
            '',
            $provider->getRefreshTokenUri()
        );

        $this->assertSame(
            '',
            $provider->getRevokeTokenUri()
        );

        $this->assertSame(
            '',
            $provider->getRequestUrl()
        );
    }
}
