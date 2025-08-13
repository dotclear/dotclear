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
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\ChallengeOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use stdClass;

/**
 * @brief   WebAuthn public key challenge descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class ChallengeOption implements ChallengeOptionInterface
{
    /**
     * The challenge.
     */
    protected ByteBufferInterface $challenge;

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
        $this->challenge = $this->buffer->randomBuffer((int) ($config['length'] ?? 32));

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->challenge);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::CREATE || $method === CredentialMethodEnum::GET) {
            $arguments->challenge = $this->challenge;
            $this->store->setChallenge($arguments->challenge);
        }
    }
}
