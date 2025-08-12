<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Data;

use Dotclear\Helper\Network\HttpClient;
use Dotclear\Helper\WebAuthn\Exception\StoreException;
use Dotclear\Interface\Helper\WebAuthn\Data\ProviderInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;

/**
 * @brief   WebAuthn passkey providers data helper.
 *
 * This helps to retrieve passkey friendly name from its AAGUID through wwww deposit.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Provider implements ProviderInterface
{
	/**
	 * The passkey providers source list URL.
	 *
	 * @var 	string 	$url
	 */
	protected string $url = 'https://raw.githubusercontent.com/passkeydeveloper/passkey-authenticator-aaguids/refs/heads/main/combined_aaguid.json';

	/**
	 * The passkey providers stack.
	 *
	 * @var 	array<string,string> 	stack
	 */
	protected array $stack = [];

    /**
     * Load services from container.
     *
     * @param   StoreInterface     $store     The store instance
     */
    public function __construct(
        protected StoreInterface $store,
    ) {
    	$this->stack = $this->store->getProviders();
    }

    public function setURL(string $url): void
    {
    	$this->url = $url;
    }

    public function getProvider(string $uuid): string
    {
        if ($this->stack === []) {
            $this->updateProviders();
        }

        return $this->stack[$uuid] ?? __('Unknown passkey');
    }

    public function updateProviders(): void
    {
    	$this->stack = [];

        $content = (string) HttpClient::quickGet($this->url);
    	$data    = json_decode($content, true);
        if (empty($data) || !is_array($data)) {
            throw new StoreException('Failed to get passkey providers list');
        }
        foreach($data as $uuid => $entry) {
            $this->stack[$uuid] = $entry['name'];
        }

    	$this->store->setProviders($this->stack);
    }
}