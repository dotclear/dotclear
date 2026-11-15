<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

require_once __DIR__ . '/OAuth2Fixtures.php';

use Dotclear\Helper\OAuth2\Client\Consumer;
use Dotclear\Helper\OAuth2\Client\Token;
use Dotclear\Helper\OAuth2\Client\User;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitEmptyStore;
use Dotclear\Tests\Helper\OAuth2\Client\Fixtures\OAuth2UnitStore;
use PHPUnit\Framework\TestCase;

class StoreTest extends TestCase
{
    public function testStoreSessionStateAndRedirect(): void
    {
        $previousSession = $_SESSION ?? null;
        $_SESSION        = [];
        $store           = new OAuth2UnitStore('https://site.test/callback');

        try {
            $this->assertSame('https://site.test/callback', $store->getRedirectUrl());
            $this->assertSame('https://site.test/callback', $store->getRedir());
            $this->assertSame('', $store->getState('unknown'));

            $_SESSION[OAuth2UnitStore::CONTAINER_ID] = 'invalid';
            $this->assertSame('', $store->getState('state'));
            $this->assertSame('https://site.test/callback', $store->getRedir());

            $store->setState('provider', 'state');
            $this->assertSame('provider', $store->getState('state'));
            $this->assertSame('https://site.test/callback', $store->getRedir());
            $store->setRedir('/return');
            $this->assertSame('/return', $store->getRedir());

            $store->delState('missing');
            $this->assertSame('provider', $store->getState('state'));
            $store->delState('provider');
            $this->assertSame('', $store->getState('state'));

            $store->setState('provider', 'next-state');
            $store->delStates();
            $this->assertSame('', $store->getState('next-state'));
            $this->assertSame('/return', $store->getRedir());

            $store->delRedir();
            $this->assertSame('https://site.test/callback', $store->getRedir());

            $_SESSION[OAuth2UnitStore::CONTAINER_ID] = ['state' => ['bad' => 42], 'redir' => 42];
            $this->assertSame('', $store->getState('bad'));
            $this->assertSame('https://site.test/callback', $store->getRedir());

            $store->setState('provider', 'replacement');
            $this->assertSame(
                ['bad' => 42, 'replacement' => 'provider'],
                $_SESSION[OAuth2UnitStore::CONTAINER_ID]['state']
            );
            $store->setRedir('/replacement');
            $this->assertSame('/replacement', $store->getRedir());

            $_SESSION[OAuth2UnitStore::CONTAINER_ID] = 'invalid';
            $store->setRedir('/reset-container');
            $this->assertSame(
                ['redir' => '/reset-container'],
                $_SESSION[OAuth2UnitStore::CONTAINER_ID]
            );
        } finally {
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }
    }

    public function testStoreDataHelpersAndEmptyContainerId(): void
    {
        $store = new OAuth2UnitStore('https://site.test/callback');
        $this->assertFalse($store->hasConsumer('provider'));
        $store->setConsumer('provider', 'key', 'secret', 'domain');
        $this->assertTrue($store->hasConsumer('provider'));
        $this->assertSame('key', $store->getConsumer('provider')->get('key'));
        $this->assertSame('', $store->getLocalUser('provider')->get('user_id'));
        $type = OAuth2UnitStore::getTypeForTesting('a-provider-name-that-is-too-long');
        $this->assertSame(31, strlen($type));
        $this->assertStringStartsWith('oauth2_a-provider', $type);

        $token = new Token(['access_token' => 'access']);
        $store->setToken('provider', 'user', $token);
        $this->assertSame($token, $store->getToken('provider', 'user'));
        $store->delToken('provider', 'user');
        $this->assertFalse($store->getToken('provider', 'user')->isConfigured());

        $user = new User(['uid' => 'remote', 'user_id' => 'local']);
        $store->setUser('provider', $user, 'local');
        $this->assertSame('local', $store->getUser('provider', 'remote')->get('user_id'));
        $store->delUser('provider', 'local');

        $previousSession = $_SESSION ?? null;
        $_SESSION        = [];
        $emptyStore      = new OAuth2UnitEmptyStore('https://site.test/callback');
        try {
            $emptyStore->setState('provider', 'state');
            $emptyStore->setRedir('/return');
            $emptyStore->delState('provider');
            $emptyStore->delStates();
            $emptyStore->delRedir();
            $this->assertSame('', $emptyStore->getState('state'));
            $this->assertSame('https://site.test/callback', $emptyStore->getRedir());
            $this->assertSame([], $_SESSION);
        } finally {
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }
    }
}
