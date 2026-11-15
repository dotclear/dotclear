<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Helper\WebAuthn\Type\TypeEnum;
use Dotclear\Helper\WebAuthn\Type\TransportsEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AllowCredentialsOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use stdClass;

/**
 * @brief   WebAuthn public key excluded credentials descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class AllowCredentialsOption implements AllowCredentialsOptionInterface
{
    /**
     * Known credentials.
     *
     * @var     string[]    $credentials
     */
    protected array $credentials;

    /**
     * Load services from container.
     *
     * @param   StoreInterface          $store      The store instance
     * @param   ByteBufferInterface     $buffer     The byte buffer interface
     */
    public function __construct(
        protected StoreInterface $store,
        protected ByteBufferInterface $buffer
    ) {
    }

    /**
     * Configure allowed credentials.
     *
     * By default, no credentials are set and authenticator uses its discoverable credentials.
     * Allowed credentials can be set from a list of credentials IDs (binary)
     * or from all credentials of a user.
     *
     * @param   array<string, mixed>    $config     The configuration: ['credentials' => string[]] or ['user_id' => string]
     */
    public function configure(array $config = []): self
    {
        $this->credentials = [];

        if (isset($config['credentials']) && is_array($config['credentials'])) {
            $this->credentials = array_values(array_filter($config['credentials'], fn ($id): bool => is_string($id) && $id !== ''));
        } elseif (isset($config['user_id']) && is_string($config['user_id']) && $config['user_id'] !== '') {
            $this->credentials = array_values(array_filter(
                array_map(fn (CredentialInterface $credential): string => $credential->credentialId(), $this->store->getCredentials('', $config['user_id'])),
                fn (string $id): bool => $id !== ''
            ));
        }

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->credentials);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::GET) {
            $arguments->allowCredentials = [];

            foreach ($this->credentials as $id) {
                $tmp             = new stdClass();
                $tmp->id         = $this->buffer->fromBinary($id);
                $tmp->type       = TypeEnum::PUBLICKEY->value; // only public-key is supported
                $tmp->transports = TransportsEnum::values();

                $arguments->allowCredentials[] = $tmp;
            }
        }
    }
}
