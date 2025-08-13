<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AttestationFormatsOptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key attestation formats descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class AttestationFormatsOption implements AttestationFormatsOptionInterface
{
    /**
     * The attestation type.
     *
     * @var     AttestationFormatsEnum[]   $formats
     */
    protected array $formats;

    public function formats(): array
    {
        if (!$this->isConfigured()) {
            $this->configure();
        }

        return $this->formats;
    }

    public function configure(array $config = []): self
    {
        if (isset($config['formats']) && is_array($config['formats'])) {
            foreach($config['formats'] as $format) {
                if (is_a($format, AttestationFormatsEnum::class)) {
                    $this->formats[] = $format;
                }
            }
        }

        if (!isset($this->formats)) {
            $this->formats = AttestationFormatsEnum::cases();
        }

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->formats);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::CREATE) {
            $arguments->attestationFormats = array_map(fn ($v): string => $v->value, $this->formats);
        }
    }
}