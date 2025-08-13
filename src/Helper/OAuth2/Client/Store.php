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
 * @brief   oAuth2 client storage class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class Store
{
    public const CONTAINER_ID = 'OAuth2ClientConsumer';

    /**
     * Create new store instance.
     *
     * @param   string  $redirect_url   Redirect URL
     */
    public function __construct(protected string $redirect_url)
    {
    }

    /**
     * Get default redirect URL.
     *
     * @return  string  The redirect URL
     */
    public function getRedirectUrl(): string
    {
        return $this->redirect_url;
    }

    /**
     * Check if a consumer is configured.
     *
     * @param   string  $provider   The provider ID
     *
     * @return  bool    True if has configured provider
     */
    public function hasConsumer(string $provider): bool
    {
        return $this->getConsumer($provider)->isConfigured();
    }

    /**
     * Get a consumer representation.
     *
     * Read configured consumer from a database.
     *
     * @param   string  $provider   The provider ID
     *
     * @return  Consumer    The consumer instance
     */
    abstract public function getConsumer(string $provider): Consumer;

    /**
     * Set (save) a consumer.
     *
     * Write a configured consumer to a database.
     *
     * @param   string  $provider   The provider ID
     * @param   string  $key        The consumer key
     * @param   string  $secret     The consumer secret
     * @param   string  $domain     The consumer domain
     */
    abstract public function setConsumer(string $provider, string $key = '', string $secret = '', string $domain = ''): void;

    /**
     * Get a token (user).
     *
     * Read a configured user token from a database.
     *
     * @param   string  $provider   The provider ID
     * @param   string  $user       The user ID
     *
     * @return  Token   The token
     */
    abstract public function getToken(string $provider, string $user): Token;

    /**
     * Set (save) a token (user).
     *
     * Write a configured user token to a database.
     *
     * @param   string      $provider   The provider ID
     * @param   string      $user_id    The user ID
     * @param   null|Token  $token      The token instance
     */
    abstract public function setToken(string $provider, string $user_id, ?Token $token = null): void;

    /**
     * Unset (drop) a token (user).
     *
     * @param   string      $provider   The provider ID
     * @param   string      $user_id    The user ID
     */
    abstract public function delToken(string $provider, string $user_id): void;

    /**
     * Get user relation between provider account and blog account.
     *
     * @param   string  $provider   The provider ID
     * @param   string  $uid        The provider user ID
     *
     * @return  User  The blog user ID or an empty string
     */
    abstract public function getUser(string $provider, string $uid): User;

    /**
     * Set user relation between provider account and blog account.
     *
     * @param   string  $provider   The provider ID
     * @param   User    $user       The provider user ID
     * @param   string  $user_id    The blog user ID
     */
    abstract public function setUser(string $provider, User $user, string $user_id): void;

    /**
     * Unset user relation between provider account and blog account.
     *
     * @param   string      $provider   The provider ID
     * @param   string      $user_id    The user ID
     */
    abstract public function delUser(string $provider, string $user_id): void;

    /**
     * Get provider user info from local user.
     *
     * @param   string      $provider   The provider ID
     *
     * @return  User    The user instance
     */
    public function getLocalUser(string $provider): User
    {
        return new User([]);
    }

    /**
     * Get flow provider state from session.
     *
     * @param   string      $state   The state
     *
     * @return  string  The flow provider state
     */
    public function getState(string $state): string
    {
        if (isset($_SESSION[static::CONTAINER_ID])
            && isset($_SESSION[static::CONTAINER_ID]['state'])
            && is_array($_SESSION[static::CONTAINER_ID]['state'])
            && array_key_exists($state, $_SESSION[static::CONTAINER_ID]['state'])
        ) {
            return $_SESSION[static::CONTAINER_ID]['state'][$state];
        }

        return '';
    }

    /**
     * Set flow provider state to session.
     *
     * @param   string      $provider   The provider ID
     * @param   string      $state      The provider state
     */
    public function setState(string $provider, string $state): void
    {
        $_SESSION[static::CONTAINER_ID]['state'][$state] = $provider;
    }

    /**
     * Delete flow provider state from session.
     *
     * @param   string      $provider   The provider ID
     */
    public function delState(string $provider): void
    {
        if (isset($_SESSION[static::CONTAINER_ID])
            && isset($_SESSION[static::CONTAINER_ID]['state'])
            && is_array($_SESSION[static::CONTAINER_ID]['state'])
            && false !== ($state = array_search($provider, $_SESSION[static::CONTAINER_ID]['state']))
        ) {
            unset($_SESSION[static::CONTAINER_ID]['state'][$state]);
        }
    }

    /**
     * Delete all flow states from session.
     */
    public function delStates(): void
    {
        if (isset($_SESSION[static::CONTAINER_ID])
            && isset($_SESSION[static::CONTAINER_ID]['state'])
        ) {
            unset($_SESSION[static::CONTAINER_ID]['state']);
        }
    }

    /**
     * Get flow redirection from session.
     *
     * @return  string  The flow redirection
     */
    public function getRedir(): string
    {
        if (isset($_SESSION[static::CONTAINER_ID])
            && isset($_SESSION[static::CONTAINER_ID]['redir'])
        ) {
            return $_SESSION[static::CONTAINER_ID]['redir'];
        }

        return $this->redirect_url;
    }

    /**
     * Set flow redirection to session.
     *
     * @param   string      $redir  The flow redirection
     */
    public function setRedir(string $redir): void
    {
        $_SESSION[static::CONTAINER_ID]['redir'] = $redir;
    }

    /**
     * Delete flow redirection from session.
     */
    public function delRedir(): void
    {
        if (isset($_SESSION[static::CONTAINER_ID])
            && isset($_SESSION[static::CONTAINER_ID]['redir'])
        ) {
            unset($_SESSION[static::CONTAINER_ID]['redir']);
        }
    }

    /**
     * Create specific credential type.
     *
     * Used for token and user stored in credential db.
     *
     * @param   string  $provider   The provider
     * @param   bool    $is_token   True for token else user
     */
    protected static function getType(string $provider, bool $is_token): string
    {
        return substr(static::CONTAINER_ID . '_' . ($is_token ? 'token' : 'user') . '_' . $provider, 0, 63);
    }
}
