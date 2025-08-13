<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client;

use ArrayObject, Exception, Throwable;
use Dotclear\Helper\Container\{ Container, Factories };
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\OAuth2\Client\Exception\{ InvalidService, InvalidUser};

/**
 * @brief   oAuth2 client helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class Client extends Container
{
    public const CONTAINER_ID = 'OAuth2ClientProvider';

    /**
     * Client services instance.
     *
     * @var     Services    $services
     */
    protected Services $service;

    /**
     * Create oAuth2 client instance.
     *
     * @param   Store  $store   The client store instance
     */
    public function __construct(protected Store $store)
    {
        // Get oAuth2 client providers, see self::getDefaultServices()
        $providers = Factories::getFactory(self::CONTAINER_ID);

        // Load providers container
        parent::__construct($providers);

        // create services providers instance
        $this->service = new Services();

        // Check required configuration
        if (!$this->checkRedirectUrl()) {
            return;
        }

        // Disable providers
        foreach ($this->getDisabledProviders() as $id) {
            $this->service->addDisabledProvider($id);
        }

        // Register providers
        foreach ($providers->dump() as $provider) {
            if (is_string($provider)) {
                $this->service->addProvider($provider);
            }
        }
    }

    /**
     * Client store instance.
     *
     * @return  Store   The client store instance
     */
    public function store(): Store
    {
        return $this->store;
    }

    /**
     * Client services instance.
     *
     * @return  Services    The client services instance
     */
    public function services(): Services
    {
        return $this->service;
    }

    /**
     * Check session.
     */
    protected function checkSession(): void
    {
        // We need session to store flow state
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check if provider is enabled.
     *
     * @param   null|string     $provider   The provider ID
     *
     * @return  bool    True if OK
     */
    public function checkProvider(?string $provider): bool
    {
        return !empty($provider)
            && $this->service->hasProvider($provider)
            && !$this->service->hasDisabledProvider($provider);
    }

    /**
     * Get disabled providers.
     *
     * @return  string[]    The providers IDs
     */
    public function getDisabledProviders(): array
    {
        return [];
    }

    /**
     * Set providers as disabled.
     *
     * Usefull to disable providers by blog.
     *
     * @param   string[]    $providers  The providers IDs
     */
    public function setDisabledProviders(array $providers): void
    {

    }

    /**
     * Get HTML represention of provider logo.
     *
     * @param   Provider    $provider   The provider
     *
     * @return  string  The HTML provider Logo
     */
    public function getProviderLogo(Provider $provider): string
    {
        return '';
    }

    /**
     * Get redirect URL.
     *
     * @return  string  The redirect URL
     */
    public function getRedirectUrl(): string
    {
        return defined('OAUTH2_REDIRECT_URL') ? OAUTH2_REDIRECT_URL : $this->store->getRedirectUrl();
    }

    /**
     * Check redirect URL form.
     *
     * @param   bool    $add_error  Throw error if check failed
     *
     * @return  bool    True if OK
     */
    public function checkRedirectUrl(bool $add_error = false): bool
    {
        $https = false !== strpos($this->getRedirectUrl(), 'https://');
        if (!$https && $add_error) {
            throw new InvalidService(__('Services does not work on unsecured protocol.'));
        }

        $no_ip = filter_var($this->getRedirectUrl(), FILTER_VALIDATE_IP) === false;
        if (!$no_ip && $add_error) {
            throw new InvalidService(__('Services does not work on IP address.'));
        }

        $no_local = false === strpos($this->getRedirectUrl(), 'localhost');
        if (!$no_local && $add_error) {
            throw new InvalidService(__('Services does not work on local network.'));
        }

        return $https && $no_ip && $no_local;
    }

    /**
     * Get HTML button to (dis)connect user.
     *
     * @param   string  $user_id    The user ID
     * @param   string  $service    The service ID
     * @param   string  $redir      The optionnal redirect URL
     * @param   bool    $register   Get the authorize button
     *
     * @return  null|Link  The action button
     */
    public function getActionButton(string $user_id, string $service, string $redir, bool $register = false): ?Link
    {
        if (!$this->checkProvider($service)) {
            return null;
        }
        try {
            $url      = $this->getRedirectUrl();
            $sign     = str_ends_with($url, '?') ? '' : (strpos($url, '?') !== false ? '&' : '?');
            $provider = $this->service->getProvider($this->store->getConsumer($service), ['redirect_uri' => $url]);
            $token    = $this->store->getToken($service, $user_id);
            $icon     = $this->getProviderLogo($provider);
            $url      .= $sign . (empty($token->get('access_token')) ? 'authorize=' : 'revoke=') . $service . '&redir=' . urlencode($redir);
            if (empty($token->get('access_token'))) {
                $text = $register ? __('Authorize %s connection') : __('Connect with %s');
            } else {
                $text = $register ? __('Revoke %s connection') : __('Disconnect from %s');

            }
        } catch (Throwable) {
            // silently bypass error and hide button
            return null;
        }

        return (new Link())
            ->class(['button', empty($token->get('access_token')) ? '' : ' delete'])
            ->href($url)
            ->text($icon . ' ' . sprintf($text, $provider->getName()))
            ->title(Html::escapeHTML($provider::getDescription()));
    }

    /**
     * oauth flow : process.
     *
     * @param   string  $user_id    The user ID
     */
    public function requestAction(string $user_id): void
    {
        try {
            if (!empty($_REQUEST['authorize'])) {
                $this->requestAuthorizationCode();
            } elseif (!empty($_REQUEST['state'])) {
                $this->requestAccessToken($user_id);
            } elseif (!empty($_REQUEST['refresh'])) {
                $this->refreshAccessToken($user_id);
            } elseif (!empty($_REQUEST['revoke'])) {
                $this->revokeAccessToken($user_id);
            }
        } catch (Exception $e) {
            if (empty($_REQUEST['oauth2_error'])) { //prevent loop on php error
                if (!$this->requestActionError($e)) {
                    Http::redirect($this->store->getRedir() . (strpos($this->store->getRedir(), '?') ? '&' : '?') . 'oauth2_error=1');
                }
            }
        }
    }

    /**
     * Do action on flow error.
     *
     * @param   Exception   $e  The flow error exception
     *
     * @return  bool    False to use redirection
     */
    protected function requestActionError(Exception $e): bool
    {
        return false;
    }

    /**
     * oAuth flow : request authorization code.
     */
    protected function requestAuthorizationCode(): void
    {
        $this->checkSession();
        
        $service = $_REQUEST['authorize'] ?? '';
        if (!$this->checkProvider($service)) {
            return;
        }
        $this->store->delStates();

        $provider = $this->service->getProvider($this->store->getConsumer($service), ['redirect_uri' => $this->getRedirectUrl()]);

        $this->store->setState($service, $provider->state->state);
        $this->store->setRedir($_REQUEST['redir'] ?? $this->getRedirectUrl());

        Http::redirect($provider->buildAuthorizeUrl());
    }

    /**
     * oAuth flow : request access token.
     *
     * @param   string  $user_id    The user ID
     */
    protected function requestAccessToken(string $user_id): void
    {
        $this->checkSession();

        $state   = $_REQUEST['state'] ?? '';
        $service = $this->store->getState($state);
        if (!$this->checkProvider($service)) {
            return;
        }

        $provider = $this->service->getProvider($this->store->getConsumer($service), [
            'state'        => $state,
            'redirect_uri' => $this->getRedirectUrl(),
        ]);
        $token = $provider->requestAccessToken($_REQUEST);

        // Find relation between blog user and provider user
        $user = $provider->getUser($token);
        if ($user->get('uid') == '') {
            throw new InvalidUser(__('Failed to retrieve user ID from this provider'));
        } elseif ($user_id == '') {
            $user_id = $this->store->getUser($provider->getId(), $user->get('uid'))->get('user_id');
            if (empty($user_id)) {
                throw new InvalidUser(__('No user ID linked to this provider'));
            } else {
                if ($this->checkUser($user_id)) {
                    $this->store->setToken($service, $user_id, $token);
                    $this->store->delStates();

                    Http::redirect($this->getRedirectUrl());
                }
            }
        } else {
            $this->store->setUser($provider->getId(), $user, $user_id);
            $this->store->setToken($service, $user_id, $token);
            $this->store->delStates();

            Http::redirect($this->store->getRedir());
        }
    }

    /**
     * Check user and set it to session.
     *
     * @param   string  $user_id    The user ID
     *
     * @return  bool    True if session is added
     */
    protected function checkUser(string $user_id): bool
    {
        if (!empty($user_id)) {
            $_SESSION['user_id'] = $user_id;

            return true;
        }

        return false;
    }

    /**
     * oAuth flow : refresh access token.
     *
     * @param   string  $user_id    The user ID
     */
    protected function refreshAccessToken(string $user_id): void
    {
        $this->checkSession();

        $service = $_REQUEST['refresh'] ?? '';
        if (!$this->checkProvider($service)) {
            return;
        }

        $provider = $this->service->getProvider($this->store->getConsumer($service), [
            'redirect_uri' => $this->getRedirectUrl(),
        ]);

        $token = $this->store->getToken($service, $user_id);
        $token = $provider->requestRefreshToken($token->get('refresh_token'));
        $this->store->setToken($service, $user_id, $token);
    }

    /**
     * oAuth flow : revoke access token.
     *
     * @param   string  $user_id    The user ID
     */
    protected function revokeAccessToken(string $user_id): void
    {
        $this->checkSession();

        $service = $_REQUEST['revoke'] ?? '';
        if (!$this->checkProvider($service)) {
            return;
        }

        $provider = $this->service->getProvider($this->store->getConsumer($service), [
            'redirect_uri' => $this->getRedirectUrl(),
        ]);

        $token = $this->store->getToken($service, $user_id);
        $provider->requestRevokeToken($token);

        $this->store->delToken($service, $user_id);
        $this->store->delUser($service, $user_id);
        $this->store->delStates();
        $this->store->setRedir($_REQUEST['redir'] ?? $this->getRedirectUrl());

        Http::redirect($this->store->getRedir());
    }
}