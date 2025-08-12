<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client;

use Throwable;

/**
 * @brief   oAuth2 client services class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Services
{
    /**
     * Http request instance.
     *
     * @var     Http    $http
     */
    protected $http;

    /**
     * Providers id/classname.
     *
     * @var     array<string, string>   $providers
     */
    protected $providers = [];

    /**
     * Disabled providers ID.
     *
     * @var     string[]    $disabled
     */
    protected $disabled = [];

    /**
     * Services constructor.
     *
     * By default use OAuth2 helpers HTTP class.
     *
     * @param   null|Http   $http   The HTTP requester instance
     */
    public function __construct(?Http $http = null)
    {
        $this->http = $http ?? new Http();
    }

    /**
     * Disabled usage of a provider (only from this class).
     *
     * But you can still call static method if provider exists.
     *
     * @param   string  $id     Provider id
     */
    public function addDisabledProvider(string $id): void
    {
        $this->disabled[] = $id;
    }

    /**
     * Check if a provider is disabled.
     *
     * @param   string  $id     The provider ID
     *
     * @return  bool    True if disabled
     */
    public function hasDisabledProvider(string $id): bool
    {
        return in_array($id, $this->disabled);
    }

    /**
     * Add a provider by classname.
     *
     * If a provider with same ID was registered, it will be overwritten.
     *
     * @param   string  $class  The provider classname
     */
    public function addProvider(string $class): void
    {
        if (is_subclass_of($class, Provider::CLASS) 
            && preg_match('/^[a-zA-Z][\w]{2,}$/',(string) $class::PROVIDER_ID)
        ) {
            $this->providers[(string) $class::PROVIDER_ID] = $class;
        }
    }

    /**
     * Check if a provider exists.
     *
     * @param   string  $id     The provider ID
     *
     * @return  bool    True if it exists (even if it is disabled)
     */
    public function hasProvider(string $id): bool
    {
        return isset($this->providers[$id]);
    }

    /**
     * Load new instance of a provider.
     *
     * @param   Consumer                                        $consumer   The consumer instance
     * @param   array<string, int|string|array<int,string>>     $config     The provider configuration
     * @param   Http                                            $http       The HTTP requester instance
     *
     * @return  Provider    The provider instance
     */
    public function getProvider(Consumer $consumer, array $config = [], ?Http $http = null): Provider
    {
        if (!$this->hasProvider($consumer->get('provider')) || $this->hasDisabledProvider($consumer->get('provider'))) {
            throw new Exception\InvalidService(sprintf(__('Failed to load provider "%s"'), $consumer->get('provider')));
        }

        $class = $this->providers[$consumer->get('provider')];

        try {
            /** @var Provider $provider */
            $provider = new $class($consumer, $config, $http ?? $this->http);
        } catch (Throwable) {
            throw new Exception\InvalidService(sprintf(__('Failed to load provider "%s"'), $consumer->get('provider')));
        }

        // @phpstan-ignore-next-line
        return $provider;
    }

    /**
     * Get all providers id/classname.
     *
     * @return  array<string, string>   The providers classname by id
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
