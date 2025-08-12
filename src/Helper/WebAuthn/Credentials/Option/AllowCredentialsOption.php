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

    public function configure(array $config = []): self
    {
        // For now, not used
        $this->credentials = [];

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
                $tmp = new stdClass();
                $tmp->id = $this->buffer->fromBinary($id);
                $tmp->type = TypeEnum::PUBLICKEY->value; // only public-key is supported
                $tmp->transports = TransportsEnum::values();
                $arguments->excludeCredentials[] = $tmp;
            }
        }
    }
}