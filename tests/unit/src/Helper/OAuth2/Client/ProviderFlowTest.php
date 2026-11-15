<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

require_once __DIR__ . '/OAuth2Fixtures.php';

use Dotclear\Helper\OAuth2\Client\Consumer;
use Dotclear\Helper\OAuth2\Client\Exception\InvalidResponse;
use Dotclear\Helper\OAuth2\Client\Exception\Unauthorized;
use Dotclear\Helper\OAuth2\Client\Methods;
use Dotclear\Helper\OAuth2\Client\Token;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitChallengeProvider;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitDomainProvider;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitHttp;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitProvider;
use PHPUnit\Framework\TestCase;

class ProviderFlowTest extends TestCase
{
    public function testAuthorizationAndTokenFlows(): void
    {
        $http = new OAuth2UnitHttp();
        $provider = new OAuth2UnitProvider(
            new Consumer(['provider' => OAuth2UnitProvider::PROVIDER_ID, 'key' => 'client', 'secret' => 'secret']),
            ['state' => 'signed-state', 'scope' => 'email', 'redirect_uri' => 'https://site.test/PROVIDER'],
            $http
        );

        $authorizeUrl = $provider->buildAuthorizeUrl();
        $this->assertStringStartsWith('https://provider.test/authorize?', $authorizeUrl);
        $this->assertStringContainsString('state=signed-state', $authorizeUrl);
        $this->assertStringContainsString('scope=profile+email', $authorizeUrl);
        $this->assertSame('https://site.test/oauth2unit', $provider->getRedirectUrl());

        $http->responses[] = ['content' => '{"access_token":"access","refresh_token":"refresh","scope":"profile email"}'];
        $token = $provider->requestAccessToken(['state' => 'signed-state', 'code' => 'auth-code']);
        $this->assertSame('access', $token->get('access_token'));
        $this->assertSame(['profile', 'email'], $token->get('scope'));
        $this->assertSame(Methods::POST, $http->requests[0]['method']);
        $this->assertSame('https://provider.test/token', $http->requests[0]['url']);
        $this->assertSame('authorization_code', $http->requests[0]['parameters']['grant_type']);
        $this->assertSame('auth-code', $http->requests[0]['parameters']['code']);
        $this->assertSame(['Accept' => 'application/json'], $http->requests[0]['headers']);

        $http->responses[] = ['content' => '{"access_token":"renewed","refresh_token":"next"}'];
        $refreshed = $provider->requestRefreshToken($token);
        $this->assertSame('renewed', $refreshed->get('access_token'));
        $this->assertSame('refresh_token', $http->requests[1]['parameters']['grant_type']);
        $this->assertSame('refresh', $http->requests[1]['parameters']['refresh_token']);

        $provider->requestRevokeToken($refreshed);
        $this->assertSame('https://provider.test/revoke', $http->requests[2]['url']);
        $this->assertSame(['token' => 'renewed'], $http->requests[2]['parameters']);
        $this->assertSame('Bearer renewed', $http->requests[2]['headers']['Authorization']);

        $http->responses[] = ['content' => '{"result":"ok"}'];
        $this->assertSame(
            ['result' => 'ok'],
            $provider->request(Methods::GET, 'users', ['limit' => 3], $refreshed)
        );
        $this->assertSame(['Authorization' => 'Bearer renewed'], $http->requests[3]['headers']);

        $http->responses[] = ['content' => 'plain response'];
        $this->assertSame(
            'plain response',
            $provider->request(Methods::GET, 'users', [], new Token(), false)
        );
        $this->assertSame([], $http->requests[4]['headers']);

        $http->responses[] = ['content' => 'not json'];
        $this->assertSame([], $provider->request(Methods::GET, 'users', [], new Token()));
        $this->assertSame('', $provider->getUserUID($token));
        $this->assertFalse($provider->getUser($token)->isConfigured());
    }

    public function testInvalidAuthorizationAndProviderResponses(): void
    {
        $http = new OAuth2UnitHttp();
        $provider = new OAuth2UnitProvider(
            new Consumer(['provider' => OAuth2UnitProvider::PROVIDER_ID]),
            ['state' => 'expected'],
            $http
        );

        try {
            $provider->requestAccessToken(['error' => 'access_denied']);
            $this->fail('Expected an OAuth error response to be rejected');
        } catch (Unauthorized $exception) {
            $this->assertStringContainsString('access_denied', $exception->getMessage());
        }

        foreach ([
            [],
            ['state' => 42, 'code' => 'code'],
            ['state' => 'wrong', 'code' => 'code'],
            ['state' => 'expected', 'code' => []],
        ] as $response) {
            try {
                $provider->requestAccessToken($response);
                $this->fail('Expected an invalid authorization response to be rejected');
            } catch (InvalidResponse) {
                $this->assertTrue(true);
            }
        }

        foreach ([
            '',
            'not json',
            '[]',
            '{"error":"denied","error_description":"description"}',
            '{"error":"denied","error_reason":"reason"}',
            '{"error":"denied"}',
            '{"error_message":"message"}',
        ] as $content) {
            $http->responses[] = ['content' => $content];
            try {
                $provider->getAccessToken([], []);
                $this->fail('Expected an invalid token response to be rejected');
            } catch (Unauthorized|InvalidResponse $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }

        $http->responses[] = ['content' => false];
        try {
            $provider->getAccessToken([], []);
            $this->fail('Expected a non-string response body to be rejected');
        } catch (InvalidResponse $exception) {
            $this->assertSame('Invalid response content.', $exception->getMessage());
        }

        $http->responses[] = ['content' => ''];
        try {
            $provider->getRefreshToken([], []);
            $this->fail('Expected an empty refresh response to be rejected');
        } catch (InvalidResponse $exception) {
            $this->assertSame('Invalid response content.', $exception->getMessage());
        }

        $http->responses[] = ['content' => null];
        try {
            $provider->request(Methods::GET, 'users', [], new Token());
            $this->fail('Expected an empty API response to be rejected');
        } catch (InvalidResponse $exception) {
            $this->assertSame('Invalid response content.', $exception->getMessage());
        }
    }

    public function testDomainAndPkceProviderConfiguration(): void
    {
        $consumer = new Consumer([
            'provider' => OAuth2UnitDomainProvider::PROVIDER_ID,
            'key'      => 'client',
            'secret'   => 'secret',
            'domain'   => 'https://tenant.test',
        ]);
        $provider = new OAuth2UnitDomainProvider($consumer, ['redirect_uri' => 'https://site.test/callback'], new OAuth2UnitHttp());

        $this->assertTrue($provider->requireDomain());
        $this->assertSame('https://tenant.test/authorize', $provider->getAuthorizeUrl());
        $this->assertSame('https://tenant.test/token', $provider->getAccessTokenUri());
        $this->assertSame($provider->getAccessTokenUri(), $provider->getRefreshTokenUri());
        $this->assertSame('https://tenant.test/revoke', $provider->getRevokeTokenUri());
        $this->assertSame('https://tenant.test/api/', $provider->getRequestUrl());

        $previousSession = $_SESSION ?? null;
        $_SESSION        = [];
        try {
            $challenge = new OAuth2UnitChallengeProvider(
                new Consumer(['provider' => OAuth2UnitChallengeProvider::PROVIDER_ID, 'key' => 'client']),
                ['state' => 'pkce-state'],
                $http = new OAuth2UnitHttp()
            );
            $url = $challenge->buildAuthorizeUrl();
            $this->assertMatchesRegularExpression('/code_challenge=[\w%-]+/', $url);
            $this->assertStringContainsString('code_challenge_method=S256', $url);
            $this->assertNotSame('', $challenge->codeVerifierForTesting());

            $http->responses[] = ['content' => '{"access_token":"pkce-access"}'];
            $pkceToken = $challenge->requestAccessToken(['state' => 'pkce-state', 'code' => 'pkce-code']);
            $this->assertSame('pkce-access', $pkceToken->get('access_token'));
            $this->assertNotSame('', $http->requests[0]['parameters']['code_verifier']);
            $this->assertSame('', $challenge->codeVerifierForTesting());

            $withoutVerifier = new OAuth2UnitChallengeProvider(
                new Consumer(['provider' => OAuth2UnitChallengeProvider::PROVIDER_ID]),
                ['state' => 'missing-verifier'],
                new OAuth2UnitHttp()
            );
            try {
                $withoutVerifier->requestAccessToken(['state' => 'missing-verifier', 'code' => 'code']);
                $this->fail('Expected a missing PKCE verifier to be rejected');
            } catch (InvalidResponse $exception) {
                $this->assertSame('PKCE code verifier not found', $exception->getMessage());
            }
        } finally {
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }
    }
}
