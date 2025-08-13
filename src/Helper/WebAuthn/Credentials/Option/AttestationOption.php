<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\AttestationEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AttestationOptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key attestation descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class AttestationOption implements AttestationOptionInterface
{
    /**
     * The attestation type.
     *
     * @var     AttestationEnum   $attestation
     */
    protected AttestationEnum $attestation;

    public function configure(array $config = []): self //array $formats = [], ?array $certificats = null): self
    {
        $this->attestation = isset($config['attestation']) && is_a($config['attestation'], AttestationEnum::class) ? 
            $config['attestation'] : AttestationEnum::NONE;

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->attestation);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::CREATE) {
            $arguments->attestation = $this->attestation->value;
        }
    }
}