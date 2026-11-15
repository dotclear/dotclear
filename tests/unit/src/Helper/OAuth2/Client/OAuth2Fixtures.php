<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client\Fixtures;

use Dotclear\Helper\OAuth2\Client\Client;
use Dotclear\Helper\OAuth2\Client\Consumer;
use Dotclear\Helper\OAuth2\Client\Http;
use Dotclear\Helper\OAuth2\Client\Methods;
use Dotclear\Helper\OAuth2\Client\Protocols;
use Dotclear\Helper\OAuth2\Client\Provider;
use Dotclear\Helper\OAuth2\Client\Store;
use Dotclear\Helper\OAuth2\Client\Token;
use Dotclear\Helper\OAuth2\Client\User;
use Exception;

class OAuth2UnitHttp extends Http
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $responses = [];

    /**
     * @var list<array{method: Methods, url: string, parameters: string|array, headers: array}>
     */
    public array $requests = [];

    public function request(Methods $method, string $url, string|array $parameters = [], array $headers = []): array
    {
        $this->requests[] = compact('method', 'url', 'parameters', 'headers');

        return array_shift($this->responses) ?? ['content' => '{"access_token":"access"}'];
    }
}

class OAuth2UnitProvider extends Provider
{
    public const PROVIDER_ID          = 'oauth2unit';
    public const PROVIDER_NAME        = 'OAuth2 Unit';
    public const PROVIDER_DESCRIPTION = 'Provider for %s';
    public const PROVIDER_PROTOCOL    = Protocols::OAUTH2;
    public const CONSOLE_URL          = 'https://provider.test/console';
    public const AUTHORIZE_URL        = 'https://provider.test/authorize';
    public const ACCESS_TOKEN_URL     = 'https://provider.test/token';
    public const REVOKE_TOKEN_URL     = 'https://provider.test/revoke';
    public const REQUEST_URL          = 'https://provider.test/api/';
    public const DEFAULT_SCOPE        = ['profile'];
    public const SCOPE_DELIMITER      = ' ';

    public function httpInstance(): Http
    {
        return $this->http;
    }

    public function codeVerifierForTesting(): string
    {
        return $this->getCodeVerifier();
    }
}

class OAuth2UnitDomainProvider extends OAuth2UnitProvider
{
    public const PROVIDER_ID   = 'oauth2unitdomain';
    public const AUTHORIZE_URL = '/authorize';
    public const ACCESS_TOKEN_URL = '/token';
    public const REVOKE_TOKEN_URL = '/revoke';
    public const REQUEST_URL = '/api/';
    public const REQUIRE_DOMAIN = true;
}

class OAuth2UnitDisabledProvider extends OAuth2UnitProvider
{
    public const PROVIDER_ID = 'oauth2unitdisabled';
}

class OAuth2UnitClientFlowProvider extends OAuth2UnitProvider
{
    public const PROVIDER_ID = 'oauth2unitflow';

    public static ?OAuth2UnitHttp $testHttp = null;

    public static bool $emptyUid = false;

    public function __construct(Consumer $consumer, array $config, Http $http)
    {
        parent::__construct($consumer, $config, self::$testHttp ?? $http);
    }

    public function getUser(Token $token): User
    {
        return new User(['uid' => self::$emptyUid ? '' : 'remote-uid']);
    }
}

class OAuth2UnitChallengeProvider extends OAuth2UnitProvider
{
    public const PROVIDER_ID       = 'oauth2unitchallenge';
    public const REQUIRE_CHALLENGE = true;
}

class OAuth2UnitBadConstructorProvider extends OAuth2UnitProvider
{
    public const PROVIDER_ID = 'oauth2unitbad';

    public function __construct(Consumer $consumer, array $config, Http $http)
    {
        throw new Exception('Provider cannot be constructed.');
    }
}

class OAuth2UnitInvalidIdProvider extends OAuth2UnitProvider
{
    public const PROVIDER_ID = 'x';
}

class OAuth2UnitClient extends Client
{
    public const CONTAINER_SINGLETON = false;

    /**
     * @var list<Exception>
     */
    public array $handledErrors = [];

    public function getDisabledProviders(): array
    {
        return ['oauth2unitdisabled'];
    }

    public function getProviderLogo(Provider $provider): string
    {
        return '<img src="provider.svg">';
    }

    protected function requestActionError(Exception $e): bool
    {
        $this->handledErrors[] = $e;

        return true;
    }
}

class OAuth2UnitBaseClient extends Client
{
    public const CONTAINER_SINGLETON = false;

    public function checkUserForTesting(string $user_id): bool
    {
        return $this->checkUser($user_id);
    }

    public function requestActionErrorForTesting(Exception $e): bool
    {
        return $this->requestActionError($e);
    }
}

class OAuth2UnitStore extends Store
{
    public const CONTAINER_ID = 'OAuth2UnitStore';

    public static function getTypeForTesting(string $provider): string
    {
        return parent::getType($provider);
    }

    /**
     * @var array<string, Consumer>
     */
    public array $consumers = [];

    /**
     * @var array<string, Token>
     */
    public array $tokens = [];

    /**
     * @var array<string, User>
     */
    public array $users = [];

    /**
     * @var list<array{provider: string, key: string, secret: string, domain: string}>
     */
    public array $consumerWrites = [];

    /**
     * @var list<array{provider: string, user_id: string, token: ?Token}>
     */
    public array $tokenWrites = [];

    /**
     * @var list<array{provider: string, user: User, user_id: string}>
     */
    public array $userWrites = [];

    /**
     * @var list<array{provider: string, user_id: string}>
     */
    public array $tokenDeletes = [];

    /**
     * @var list<array{provider: string, user_id: string}>
     */
    public array $userDeletes = [];

    public function getConsumer(string $provider): Consumer
    {
        return $this->consumers[$provider] ?? new Consumer(['provider' => $provider]);
    }

    public function setConsumer(string $provider, string $key = '', string $secret = '', string $domain = ''): void
    {
        $this->consumerWrites[] = compact('provider', 'key', 'secret', 'domain');
        $this->consumers[$provider] = new Consumer(compact('provider', 'key', 'secret', 'domain'));
    }

    public function getToken(string $provider, string $user): Token
    {
        return $this->tokens[$provider . ':' . $user] ?? new Token();
    }

    public function setToken(string $provider, string $user_id, ?Token $token = null): void
    {
        $this->tokenWrites[] = compact('provider', 'user_id', 'token');
        if ($token !== null) {
            $this->tokens[$provider . ':' . $user_id] = $token;
        }
    }

    public function delToken(string $provider, string $user_id): void
    {
        $this->tokenDeletes[] = compact('provider', 'user_id');
        unset($this->tokens[$provider . ':' . $user_id]);
    }

    public function getUser(string $provider, string $uid): User
    {
        return $this->users[$provider . ':' . $uid] ?? new User();
    }

    public function setUser(string $provider, User $user, string $user_id): void
    {
        $this->userWrites[] = compact('provider', 'user', 'user_id');
        $uid = $user->get('uid');
        if (is_string($uid)) {
            $this->users[$provider . ':' . $uid] = new User([
                'user_id'     => $user_id,
                'uid'         => $uid,
                'displayname' => $user->get('displayname'),
                'email'       => $user->get('email'),
                'avatar'      => $user->get('avatar'),
            ]);
        }
    }

    public function delUser(string $provider, string $user_id): void
    {
        $this->userDeletes[] = compact('provider', 'user_id');
    }

    public function linkUser(string $provider, string $uid, string $user_id): void
    {
        $this->users[$provider . ':' . $uid] = new User(['user_id' => $user_id, 'uid' => $uid]);
    }
}

class OAuth2UnitEmptyStore extends OAuth2UnitStore
{
    public const CONTAINER_ID = '';
}

class OAuth2UnitFaultyStore extends OAuth2UnitStore
{
    public function getConsumer(string $provider): Consumer
    {
        throw new Exception('Store lookup failed.');
    }
}

class OAuth2UnitFaultyRedirectStore extends OAuth2UnitStore
{
    public function setRedir(string $redir): void
    {
        parent::setRedir($redir);
        throw new Exception('Redirect stored.');
    }
}

class OAuth2UnitFaultyTokenStore extends OAuth2UnitStore
{
    public function setToken(string $provider, string $user_id, ?Token $token = null): void
    {
        parent::setToken($provider, $user_id, $token);
        throw new Exception('Token stored.');
    }
}

class OAuth2UnitFaultyStatesStore extends OAuth2UnitStore
{
    public function delStates(): void
    {
        parent::delStates();
        throw new Exception('States deleted.');
    }
}

class OAuth2UnitFaultyGetRedirStore extends OAuth2UnitStore
{
    public function getRedir(): string
    {
        throw new Exception('Redirect lookup failed.');
    }
}

class OAuth2UnitWrappedRefreshToken extends Token
{
    public function get(string $key): mixed
    {
        if ($key === 'refresh_token') {
            return new Token(['access_token' => 'refresh-token']);
        }

        return parent::get($key);
    }
}
