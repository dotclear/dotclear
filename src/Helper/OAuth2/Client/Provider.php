<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client;

/**
 * @brief   oAuth2 client provider class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class Provider
{
    /**
     * The provider ID
     *
     * @var     string  PROVIDER_ID
     */
    public const PROVIDER_ID = 'undefined';
    /**
     * The provider name.
     *
     * Can be translated in self::getName()
     *
     * @var     string  PROVIDER_NAME
     */
    public const PROVIDER_NAME = 'Undefined';
    /**
     * The provider short description.
     *
     * Can be translated in self::getDescription()
     *
     * @var     string  PROVIDER_DESCRIPTION
     */
    public const PROVIDER_DESCRIPTION = 'no description available';
    /**
     * The provider auth protocol.
     *
     * @var     Protocols  PROVIDER_PROTOCOL
     */
    public const PROVIDER_PROTOCOL = Protocols::OAUTH2;
    /**
     * The provider application console URL.
     *
     * @var     string  CONSOLE_URL
     */
    public const CONSOLE_URL = '';
    /**
     * The provider flow authorization request URL.
     *
     * @var     string  AUTHORIZE_URL
     */
    public const AUTHORIZE_URL = '';
    /**
     * The provider flow token request URL.
     *
     * @var     string  ACCESS_TOKEN_URL
     */
    public const ACCESS_TOKEN_URL = '';
    /**
     * The provider flow revoke token URL.
     *
     * @var     string  REVOKE_TOKEN_URL
     */
    public const REVOKE_TOKEN_URL = '';
    /**
     * The provider API request URL.
     *
     * @var     string  REQUEST_URL
     */
    public const REQUEST_URL = '';
    /**
     * The provider application scopes.
     *
     * @var     string[]    DEFAULT_SCOPE
     */
    public const DEFAULT_SCOPE = [];
    /**
     * The provider application scopes delimiter.
     *
     * @var     string  SCOPE_DELIMITER
     */
    public const SCOPE_DELIMITER = ',';
    /**
     * The provider application required domain.
     *
     * @var     bool    REQUIRE_DOMAIN
     */
    public const REQUIRE_DOMAIN = false;
    /**
     * The application required code challenge.
     *
     * @var     bool    REQUIRE_DOMAIN
     */
    public const REQUIRE_CHALLENGE = false;

    /**
     * List of scopes.
     */
    public readonly Scope $scope;

    /**
     * Key state.
     */
    public readonly State $state;

    /**
     * Redirect url.
     */
    protected readonly string $redirect_uri;

    /**
     * Create new provider instance.
     *
     * @param   Consumer                $consumer   The consumer instance
     * @param   array<string, mixed>    $config     The provider configuration
     * @param   Http                    $http       The HTTP requester instance
     */
    public function __construct(
        public readonly Consumer $consumer,
        array $config,
        protected readonly Http $http
    ) {
        // translation purpose only
        __('Allow user connection using %s application.');

        $this->scope        = new Scope($config['scope'] ?? '', static::DEFAULT_SCOPE, static::SCOPE_DELIMITER);
        $this->state        = new State($config['state'] ?? '');
        $this->redirect_uri = isset($config['redirect_uri']) && is_string($config['redirect_uri']) ? $config['redirect_uri'] : '';
    }

    /**
     * Get provider protocol.
     *
     * @return  string  Protocol
     */
    public static function getProtocol(): string
    {
        return static::PROVIDER_PROTOCOL->name;
    }

    /**
     * Get provider id.
     *
     * @return  string  Id
     */
    public static function getId(): string
    {
        return static::PROVIDER_ID;
    }

    /**
     * Get provider name.
     *
     * @return  string  Name
     */
    public static function getName(): string
    {
        return __(static::PROVIDER_NAME);
    }

    /**
     * Get provider short description.
     *
     * @return  string  Description
     */
    public static function getDescription(): string
    {
        return sprintf(__(static::PROVIDER_DESCRIPTION), static::getName());
    }

    /**
     * Get provider icon URI.
     *
     * @return  string  Icon URI
     */
    public static function getIcon(): string
    {
        return sprintf('images/oauth2/%s.svg', static::PROVIDER_ID);
    }

    /**
     * Get provider console url.
     *
     * This is the URL where you can setup your apps.
     *
     * @return  string  Console url
     */
    public static function getConsoleUrl(): string
    {
        return static::CONSOLE_URL;
    }

    /**
     * Does provider required domain.
     *
     * This is a custom base URI for request.
     *
     * @return  bool    True if provider requires domain
     */
    public function requireDomain(): bool
    {
        return static::REQUIRE_DOMAIN;
    }

    /**
     * Generate a PKCE code verifier.
     *
     * @return  string  The code
     */
    public static function getPKCEVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(128)), '+/', '-_'), '=');
    }

    /**
     * Get PKCE code challenge.
     *
     * Take code verifier from session and use sha256 hash.
     *
     * @param   string  $code_verifier  The code verifier
     *
     * @return  string The code challenge
     */
    public static function getPKCEChallenge(string $code_verifier): string
    {
        $hash = hash('sha256', $code_verifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    /**
     * Get parsed redirect URL.
     *
     * @return  string  The redirect URL
     */
    public function getRedirectUrl(): string
    {
        return str_replace('PROVIDER', static::getId(), $this->redirect_uri);
    }

    /**
     * Build authorize URL.
     *
     * @return  string  The authorize URL
     */
    public function buildAuthorizeUrl(): string
    {
        $parameters          = $this->getAuthorizeParameters();
        $parameters['state'] = $this->state->state;
        if (count($this->scope->scope)) {
            $parameters['scope'] = $this->scope->toString();
        }

        if (static::REQUIRE_CHALLENGE) {
            $_SESSION['code_verifier'] = static::getPKCEVerifier();

            $parameters['code_challenge']        = static::getPKCEChallenge($_SESSION['code_verifier']);
            $parameters['code_challenge_method'] = 'S256';
        }

        return $this->getAuthorizeUrl() . (str_contains($this->getAuthorizeUrl(), '?') ? '&' : '?') . http_build_query($parameters);
    }

    /**
     * Get authorize request parameters.
     *
     * @return  array<string, string>   The authorize request parameters
     */
    protected function getAuthorizeParameters(): array
    {
        return [
            'client_id'     => $this->consumer->get('key'),
            'redirect_uri'  => $this->getRedirectUrl(),
            'response_type' => 'code',
        ];
    }

    /**
     * Get authorization uri.
     *
     * @return  string  The authorization uri
     */
    public function getAuthorizeUrl(): string
    {
        return (static::REQUIRE_DOMAIN ? $this->consumer->get('domain') : '') . static::AUTHORIZE_URL;
    }

    /**
     * Request access token.
     *
     * @param   array<string, mixed>     $response  The response from provider
     *
     * @return  Token   The token instance
     */
    public function requestAccessToken(array $response): Token
    {
        if (!empty($response['error'])) {
            throw new Exception\Unauthorized($response['error']);
        }
        if (empty($response['state']) || !$this->state->check($response['state'])) {
            throw new Exception\InvalidResponse(__('Invalid response state'));
        }
        if (empty($response['code'])) {
            throw new Exception\InvalidResponse(__('Invalid response code'));
        }

        return $this->getAccessToken(
            $this->getAccessTokenParameters($response['code']),
            $this->getAccessTokenHeaders($response['code'])
        );
    }

    /**
     * Get access token request parameters.
     *
     * @param   string  $code   The code from provider response
     *
     * @return  string|array<string, string>   The access token request parameters
     */
    protected function getAccessTokenParameters(string $code): string|array
    {
        $parameters = [
            'client_id'     => $this->consumer->get('key'),
            'client_secret' => $this->consumer->get('secret'),
            'redirect_uri'  => $this->getRedirectUrl(),
            'grant_type'    => GrantTypes::AUTHORIZATION_CODE->value,
            'code'          => $code,
        ];

        if (static::REQUIRE_CHALLENGE) {
            $code = $_SESSION['code_verifier'] ?? '';
            if (!$code) {
                throw new Exception\InvalidResponse('PKCE code verifier not found');
            }

            $parameters['code_verifier'] = $code;
            //$parameters['device_id'] = $this->session->get('device_id');

            unset($_SESSION['code_verifier']);
        }

        return $parameters;
    }

    /**
     * Get access token request headers.
     *
     * @param   string  $code   The code from provider response
     *
     * @return  array<string, string>   The access token request headers
     */
    protected function getAccessTokenHeaders(string $code): array
    {
        return ['Accept' => 'application/json'];
    }

    /**
     * Get access token (request).
     *
     * @param   string|array<string, string>    $parameters     The request parameters
     * @param   array<string, string>           $headers        The request headers
     *
     * @return  Token   The token instance
     */
    public function getAccessToken(string|array $parameters, array $headers): Token
    {
        $response = $this->http->request(Methods::POST, $this->getAccessTokenUri(), $parameters, $headers);

        if (empty($response['content'])) {
            throw new Exception\InvalidResponse(__('Invalid response content'));
        }

        return $this->parseToken($response['content']);
    }

    /**
     * Get request token uri.
     *
     * @return  string  The request token uri
     */
    public function getAccessTokenUri(): string
    {
        return (static::REQUIRE_DOMAIN ? $this->consumer->get('domain') : '') . static::ACCESS_TOKEN_URL;
    }

    /**
     * Request refresh token.
     *
     * @param   Token  $token   The user token
     *
     * @return  Token   The token instance
     */
    public function requestRefreshToken(Token $token): Token
    {
        return $this->getRefreshToken(
            $this->getRefreshTokenParameters($token),
            $this->getRefreshTokenHeaders($token)
        );
    }

    /**
     * Get refresh token request parameters.
     *
     * @param   Token  $token   The user token
     *
     * @return  string|array<string, string>   The refresh token request parameters
     */
    protected function getRefreshTokenParameters(Token $token): string|array
    {
        return [
            'client_id'     => $this->consumer->get('key'),
            'client_secret' => $this->consumer->get('secret'),
            'grant_type'    => GrantTypes::REFRESH_TOKEN->value,
            'refresh_token' => $token->get('refresh_token'),
        ];
    }

    /**
     * Get refresh token request headers.
     *
     * @param   Token  $token   The user token
     *
     * @return  array<string, string>   The refresh token request headers
     */
    protected function getRefreshTokenHeaders(Token $token): array
    {
        return ['Accept' => 'application/json'];
    }

    /**
     * Get refresh token (request).
     *
     * @param   string|array<string, string>    $parameters     The request parameters
     * @param   array<string, string>           $headers        The request headers
     *
     * @return  Token   The token instance
     */
    public function getRefreshToken(string|array $parameters, array $headers): Token
    {
        $response = $this->http->request(Methods::POST, $this->getRefreshTokenUri(), $parameters, $headers);

        if (empty($response['content'])) {
            throw new Exception\InvalidResponse(__('Invalid response content'));
        }

        return $this->parseToken($response['content']);
    }

    /**
     * Get refresh token uri.
     *
     * @return  string  The refresh token uri
     */
    public function getRefreshTokenUri(): string
    {
        return $this->getAccessTokenUri();
    }

    /**
     * Request revoke token.
     *
     * @param   Token   $token  The token
     */
    public function requestRevokeToken(Token $token): void
    {
        $this->getRevokeToken(
            $this->getRevokeTokenParameters($token),
            $this->getRevokeTokenHeaders($token)
        );
    }

    /**
     * Get revoke token request parameters.
     *
     * @param   Token   $token  The token
     *
     * @return  string|array<string, string>   The revoke token request parameters
     */
    protected function getRevokeTokenParameters(Token $token): string|array
    {
        return [
            'token' => $token->get('access_token'),
        ];
    }

    /**
     * Get revoke token request headers.
     *
     * @param   Token  $token  The token
     *
     * @return  array<string, string>   The revoke token request headers
     */
    protected function getRevokeTokenHeaders(Token $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token->get('access_token'),
            'content-type'  => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * Get revoke token (request).
     *
     * @param   string|array<string, string>    $parameters     The request parameters
     * @param   array<string, string>           $headers        The request headers
     */
    public function getRevokeToken(string|array $parameters, array $headers): void
    {
        // We don't take care of response
        $this->http->request(Methods::POST, $this->getRevokeTokenUri(), $parameters, $headers);
    }

    /**
     * Get revoke token uri.
     *
     * @return  string  The revoke token uri
     */
    public function getRevokeTokenUri(): string
    {
        return (static::REQUIRE_DOMAIN ? $this->consumer->get('domain') : '') . static::REVOKE_TOKEN_URL;
    }

    /**
     * Parse request token response.
     *
     * @param   string  $content    The response content
     *
     * @return  Token   The token instance
     */
    protected function parseToken(string $content): Token
    {
        $token = json_decode($content, true);
        if (!$token || !is_array($token)) {
            throw new Exception\InvalidResponse(__('Invalid response format'));
        }

        $this->parseResponseError($token);

        if (!empty($token['scope']) && is_string($token['scope'])) {
            $token['scope'] = $this->scope->toArray($token['scope']);
        }

        return new Token($token);
    }

    /**
     * Parse request token response error.
     *
     * @param   array<string, string>   $response   The response content
     */
    protected function parseResponseError(array $response): void
    {
        $message = '';
        if (!empty($response['error'])) {
            if (isset($response['error_description']) && $response['error_description'] !== '') {
                $message = $response['error_description'];
            } elseif (isset($response['error_reason']) && $response['error_reason'] !== '') {
                $message = $response['error_reason'];
            } else {
                $message = $response['error'];
            }
        } elseif (!empty($response['error_message'])) {
            $message = $response['error_message'];
        }

        if (!empty($message)) {
            throw new Exception\Unauthorized($message);
        }
    }

    /**
     * Query provider API.
     *
     * @param   Methods                 $method     The HTTP query method
     * @param   string                  $endpoint   The query endoint (URI)
     * @param   array<string, mixed>    $query      The query parameters
     * @param   Token                   $token      The user token
     * @param   bool                    $json       Parse reponse as json
     *
     * @return  string|array<string, mixed>  The response
     */
    public function request(Methods $method, string $endpoint, array $query, Token $token, bool $json = true): string|array
    {
        return $this->getRequest(
            $method,
            $endpoint,
            $this->getRequestParameters($method, $endpoint, $query, $token),
            $this->getRequestHeaders($method, $endpoint, $query, $token),
            $json
        );
    }

    /**
     * Get requets parameters.
     *
     * @param   Methods                 $method     The HTTP query method
     * @param   string                  $endpoint   The query endoint (URI)
     * @param   array<string, mixed>    $query      The query parameters
     * @param   Token                   $token      The user token
     *
     * @return  array<string, mixed>    The parameters
     */
    protected function getRequestParameters(Methods $method, string $endpoint, array $query, Token $token): array
    {
        /*
        // transmit token through parameters
        if (!empty($token->get('access_token'))) {
            $query['access_token'] = $token->get('access_token');
        }
        //*/

        // transmit token through headers
        return $query;
    }

    /**
     * Get request headers.
     *
     * @param   Methods                 $method     The HTTP query method
     * @param   string                  $endpoint   The query endoint (URI)
     * @param   array<string, mixed>    $query      The query parameters
     * @param   Token                   $token      The access token
     *
     * @return  array<string, mixed>    The headers
     */
    protected function getRequestHeaders(Methods $method, string $endpoint, array $query, Token $token): array
    {
        /*
        // transmit token through parameters
        return [];
        //*/

        // transmit token through headers
        return empty($token->get('access_token')) ? [] : ['Authorization' => 'Bearer ' . $token->get('access_token')];
    }

    /**
     * Do request.
     *
     * @param   Methods                 $method         The HTTP request method
     * @param   string                  $endpoint       The query endoint (URI)
     * @param   array<string, mixed>    $parameters     The query parameters
     * @param   array<string, mixed>    $headers        The query headers
     * @param   bool                    $json           Parse reponse as json
     *
     * @return  string|array<string, mixed>  The response
     */
    protected function getRequest(Methods $method, string $endpoint, array $parameters, array $headers, bool $json): string|array
    {
        $response = $this->http->request($method, $this->getRequestUrl() . $endpoint, $parameters, $headers);

        if (empty($response['content'])) {
            throw new Exception\InvalidResponse(__('Invalid response content'));
        }

        return $this->parseRequest($response['content'], $json);
    }

    /**
     * Get API base uri.
     *
     * @return  string  The API base uri
     */
    public function getRequestUrl(): string
    {
        return (static::REQUIRE_DOMAIN ? $this->consumer->get('domain') : '') . static::REQUEST_URL;
    }

    /**
     * Parse API response.
     *
     * @param   string  $content    The response content
     * @param   bool    $json       Parse reponse as json
     *
     * @return  string|array<string, mixed>  The response
     */
    protected function parseRequest(string $content, bool $json): string|array
    {
        if ($json) {
            $rsp = json_decode($content, true);

            return is_array($rsp) ? $rsp : [];
        }

        return $content;
    }

    /**
     * Get provider user info.
     *
     * @param   Token   $token  The user token
     *
     * @return  User    The user info
     */
    public function getUser(Token $token): User
    {
        return new User();
    }

    /**
     * Get provider user uniq ID.
     *
     * @param   Token   $token  The user token
     *
     * @return  string  The user UID
     */
    public function getUserUID(Token $token): string
    {
        return '';
    }
}
