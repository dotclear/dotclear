<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

require_once __DIR__ . '/OAuth2Fixtures.php';

use Dotclear\Helper\Container\Factories;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\OAuth2\Client\Exception\InvalidService;
use Dotclear\Helper\OAuth2\Client\Methods;
use Dotclear\Helper\OAuth2\Client\Token;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitBaseClient;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitClient;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitClientFlowProvider;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitDisabledProvider;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitFaultyGetRedirStore;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitFaultyRedirectStore;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitFaultyStore;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitFaultyStatesStore;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitHttp;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitProvider;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitStore;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitWrappedRefreshToken;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private function registerProviders(): void
    {
        Factories::addService(OAuth2UnitClient::CONTAINER_ID, OAuth2UnitProvider::class, OAuth2UnitProvider::class);
        Factories::addService(OAuth2UnitClient::CONTAINER_ID, OAuth2UnitDisabledProvider::class, OAuth2UnitDisabledProvider::class);
        Factories::addService(OAuth2UnitClient::CONTAINER_ID, OAuth2UnitClientFlowProvider::class, OAuth2UnitClientFlowProvider::class);
    }

    public function testRedirectValidationProviderChecksAndButtons(): void
    {
        $this->registerProviders();
        $store = new OAuth2UnitStore('https://site.test/oauth/callback');
        $store->setConsumer(OAuth2UnitProvider::PROVIDER_ID, 'client', 'secret');
        $client = new OAuth2UnitClient($store);

        $this->assertSame($store, $client->store());
        $this->assertTrue($client->checkRedirectUrl());
        $this->assertSame('https://site.test/oauth/callback', $client->getRedirectUrl());
        $this->assertTrue($client->checkProvider(OAuth2UnitProvider::PROVIDER_ID));
        $this->assertFalse($client->checkProvider(null));
        $this->assertFalse($client->checkProvider(''));
        $this->assertFalse($client->checkProvider('unknown'));
        $this->assertFalse($client->checkProvider(OAuth2UnitDisabledProvider::PROVIDER_ID));
        $this->assertSame(['oauth2unitdisabled'], $client->getDisabledProviders());
        $this->assertSame(
            '<img src="provider.svg">',
            $client->getProviderLogo($client->services()->getProvider($store->getConsumer(OAuth2UnitProvider::PROVIDER_ID)))
        );

        $this->assertNull($client->getActionButton('user', 'unknown', '/return'));
        $button = $client->getActionButton('user', OAuth2UnitProvider::PROVIDER_ID, '/return', true);
        $this->assertInstanceOf(Link::class, $button);
        $rendered = $button->render();
        $this->assertStringContainsString('authorize=oauth2unit', $rendered);
        $this->assertStringContainsString('redir=%2Freturn', $rendered);
        $this->assertStringContainsString('Authorize OAuth2 Unit connection', $rendered);

        $store->setToken(OAuth2UnitProvider::PROVIDER_ID, 'user', new Token(['access_token' => 'access']));
        $button = $client->getActionButton('user', OAuth2UnitProvider::PROVIDER_ID, '/return', true);
        $this->assertInstanceOf(Link::class, $button);
        $this->assertStringContainsString('revoke=oauth2unit', $button->render());
        $this->assertStringContainsString('Revoke OAuth2 Unit connection', $button->render());

        foreach ([
            ['http://site.test/callback', 'Services does not work on unsecured protocol.'],
            ['https://127.0.0.1/callback', 'Services does not work on IP address.'],
            ['https://sub.localhost/callback', 'Services does not work on local network.'],
        ] as [$url, $message]) {
            $invalidClient = new OAuth2UnitClient(new OAuth2UnitStore($url));
            $this->assertFalse($invalidClient->checkRedirectUrl());
            try {
                $invalidClient->checkRedirectUrl(true);
                $this->fail('Expected an invalid redirect URL to be rejected');
            } catch (InvalidService $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
    }

    public function testActionDispatchAndStoreErrors(): void
    {
        $this->registerProviders();
        $client = new OAuth2UnitClient(new OAuth2UnitStore('https://site.test/callback'));
        $previousRequest = $_REQUEST ?? null;
        $previousSession = $_SESSION ?? null;

        try {
            $_REQUEST = [];
            $client->requestAction('user');

            foreach ([
                ['authorize' => 'missing'],
                ['state' => 'unknown-state'],
                ['refresh' => 'missing'],
                ['revoke' => 'missing'],
            ] as $request) {
                $_REQUEST = $request;
                $client->requestAction('user');
            }

            $_REQUEST = ['authorize' => OAuth2UnitProvider::PROVIDER_ID];
            $faultyClient = new OAuth2UnitClient(new OAuth2UnitFaultyStore('https://site.test/callback'));
            $faultyClient->requestAction('user');
            $this->assertCount(1, $faultyClient->handledErrors);
            $this->assertSame('Store lookup failed.', $faultyClient->handledErrors[0]->getMessage());
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            if ($previousRequest === null) {
                unset($_REQUEST);
            } else {
                $_REQUEST = $previousRequest;
            }
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }
    }

    public function testActionButtonFailuresAreHidden(): void
    {
        $this->registerProviders();
        $client = new OAuth2UnitClient(new OAuth2UnitFaultyStore('https://site.test/callback'));

        $this->assertNull($client->getActionButton('user', OAuth2UnitProvider::PROVIDER_ID, '/return'));
    }

    public function testDefaultClientExtensionMethods(): void
    {
        $client = new OAuth2UnitBaseClient(new OAuth2UnitStore('https://site.test/callback'));
        $previousSession = $_SESSION ?? null;

        try {
            $this->assertSame([], $client->getDisabledProviders());
            $client->setDisabledProviders(['oauth2unit']);
            $this->assertSame('', $client->getProviderLogo(new OAuth2UnitProvider(
                new \Dotclear\Helper\OAuth2\Client\Consumer(['provider' => OAuth2UnitProvider::PROVIDER_ID]),
                [],
                new OAuth2UnitHttp()
            )));
            $this->assertFalse($client->requestActionErrorForTesting(new \RuntimeException('error')));
            $this->assertFalse($client->checkUserForTesting(''));
            $this->assertTrue($client->checkUserForTesting('user'));
        } finally {
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }
    }

    public function testAuthorizationAccessRefreshAndRevokeActions(): void
    {
        $this->registerProviders();
        $previousRequest = $_REQUEST ?? null;
        $previousSession = $_SESSION ?? null;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION        = [];

        try {
            $redirectStore = new OAuth2UnitFaultyRedirectStore('https://site.test/callback');
            $redirectStore->setConsumer(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'client', 'secret');
            $client   = new OAuth2UnitClient($redirectStore);
            $_REQUEST = ['authorize' => OAuth2UnitClientFlowProvider::PROVIDER_ID, 'redir' => '/after'];
            $client->requestAction('user');
            $this->assertSame('Redirect stored.', $client->handledErrors[0]->getMessage());
            $this->assertSame('/after', $redirectStore->getRedir());

            $accessStore = new OAuth2UnitFaultyStatesStore('https://site.test/callback');
            $accessStore->setConsumer(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'client', 'secret');
            $accessStore->setState(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'access-state');
            OAuth2UnitClientFlowProvider::$testHttp = new OAuth2UnitHttp();
            OAuth2UnitClientFlowProvider::$testHttp->responses[] = ['content' => '{"access_token":"access"}'];
            $client   = new OAuth2UnitClient($accessStore);
            $_REQUEST = [
                7          => 'removed numeric-key',
                'state'    => 'access-state',
                'code'     => 'code',
                'provider' => OAuth2UnitClientFlowProvider::PROVIDER_ID,
            ];
            $client->requestAction('local-user');
            $this->assertSame('States deleted.', $client->handledErrors[0]->getMessage());
            $this->assertCount(1, $accessStore->userWrites);
            $this->assertSame('local-user', $accessStore->userWrites[0]['user_id']);
            $this->assertCount(1, $accessStore->tokenWrites);

            $linkedStore = new OAuth2UnitFaultyStatesStore('https://site.test/callback');
            $linkedStore->setConsumer(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'client', 'secret');
            $linkedStore->setState(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'linked-state');
            $linkedStore->linkUser(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'remote-uid', 'linked-user');
            OAuth2UnitClientFlowProvider::$testHttp = new OAuth2UnitHttp();
            OAuth2UnitClientFlowProvider::$testHttp->responses[] = ['content' => '{"access_token":"linked-access"}'];
            $client   = new OAuth2UnitClient($linkedStore);
            $_REQUEST = ['state' => 'linked-state', 'code' => 'code'];
            $client->requestAction('');
            $this->assertSame('States deleted.', $client->handledErrors[0]->getMessage());
            $this->assertSame('linked-user', $_SESSION['user_id']);
            $this->assertCount(1, $linkedStore->tokenWrites);

            $refreshStore = new OAuth2UnitStore('https://site.test/callback');
            $refreshStore->setConsumer(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'client', 'secret');
            $refreshStore->setToken(
                OAuth2UnitClientFlowProvider::PROVIDER_ID,
                'local-user',
                new OAuth2UnitWrappedRefreshToken(['access_token' => 'old-access'])
            );
            OAuth2UnitClientFlowProvider::$testHttp = new OAuth2UnitHttp();
            OAuth2UnitClientFlowProvider::$testHttp->responses[] = ['content' => '{"access_token":"refreshed"}'];
            $client   = new OAuth2UnitClient($refreshStore);
            $_REQUEST = ['refresh' => OAuth2UnitClientFlowProvider::PROVIDER_ID];
            $client->requestAction('local-user');
            $this->assertSame('refreshed', $refreshStore->getToken(
                OAuth2UnitClientFlowProvider::PROVIDER_ID,
                'local-user'
            )->get('access_token'));
            $this->assertSame(Methods::POST, OAuth2UnitClientFlowProvider::$testHttp->requests[0]['method']);

            $noRefreshStore = new OAuth2UnitStore('https://site.test/callback');
            $noRefreshStore->setConsumer(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'client', 'secret');
            $noRefreshStore->setToken(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'local-user', new Token());
            $noRefreshStore->tokenWrites = [];
            $client   = new OAuth2UnitClient($noRefreshStore);
            $_REQUEST = ['refresh' => OAuth2UnitClientFlowProvider::PROVIDER_ID];
            $client->requestAction('local-user');
            $this->assertSame([], $noRefreshStore->tokenWrites);

            $revokeStore = new OAuth2UnitFaultyGetRedirStore('https://site.test/callback');
            $revokeStore->setConsumer(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'client', 'secret');
            $revokeStore->setToken(
                OAuth2UnitClientFlowProvider::PROVIDER_ID,
                'local-user',
                new Token(['access_token' => 'revoke-me'])
            );
            OAuth2UnitClientFlowProvider::$testHttp = new OAuth2UnitHttp();
            $client   = new OAuth2UnitClient($revokeStore);
            $_REQUEST = ['revoke' => OAuth2UnitClientFlowProvider::PROVIDER_ID, 'redir' => 42];
            $client->requestAction('local-user');
            $this->assertSame('Redirect lookup failed.', $client->handledErrors[0]->getMessage());
            $this->assertCount(1, $revokeStore->tokenDeletes);
            $this->assertCount(1, $revokeStore->userDeletes);
        } finally {
            OAuth2UnitClientFlowProvider::$testHttp = null;
            OAuth2UnitClientFlowProvider::$emptyUid = false;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            if ($previousRequest === null) {
                unset($_REQUEST);
            } else {
                $_REQUEST = $previousRequest;
            }
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }
    }

    public function testAccessActionRejectsInvalidProviderUserData(): void
    {
        $this->registerProviders();
        $previousRequest = $_REQUEST ?? null;
        $previousSession = $_SESSION ?? null;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION        = [];

        try {
            foreach ([true, false] as $emptyUid) {
                $store = new OAuth2UnitStore('https://site.test/callback');
                $store->setConsumer(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'client', 'secret');
                $store->setState(OAuth2UnitClientFlowProvider::PROVIDER_ID, 'invalid-user-state');
                OAuth2UnitClientFlowProvider::$emptyUid = $emptyUid;
                OAuth2UnitClientFlowProvider::$testHttp = new OAuth2UnitHttp();
                OAuth2UnitClientFlowProvider::$testHttp->responses[] = ['content' => '{"access_token":"access"}'];
                $client   = new OAuth2UnitClient($store);
                $_REQUEST = ['state' => 'invalid-user-state', 'code' => 'code'];
                $client->requestAction('');

                $this->assertCount(1, $client->handledErrors);
                $this->assertInstanceOf(\Dotclear\Helper\OAuth2\Client\Exception\InvalidUser::class, $client->handledErrors[0]);
            }
        } finally {
            OAuth2UnitClientFlowProvider::$testHttp = null;
            OAuth2UnitClientFlowProvider::$emptyUid = false;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            if ($previousRequest === null) {
                unset($_REQUEST);
            } else {
                $_REQUEST = $previousRequest;
            }
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }
    }
}
