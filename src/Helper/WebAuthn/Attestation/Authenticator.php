<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Attestation;

use Dotclear\Helper\WebAuthn\Exception\AuthenticatorException;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestedCredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;

/**
 * @brief   WebAuthn authenticator data helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Authenticator implements AuthenticatorInterface
{
    /**
     * The authenticatorData binary.
     *
     * @var     string  $binary
     */
    protected string $binary;

    /**
     * The authenticator data flags.
     *
     * @var     int     $flags
     */
    protected int $flags;

    /**
     * The authenticator data current reading offset.
     *
     * @var     int     $offet
     */
    protected int $offset = 37;

    /**
     * The unpack attested credential data.
     *
     * @var     AttestedCredentialDataInterface     attested_credential_data
     */
    //protected AttestedCredentialDataInterface $attested_credential_data;

    /**
     * @var     array<string,mixed>     $extension_data
     */
    protected array $extension_data;

    /**
     * Load services from container.
     *
     * @param   AttestedCredentialInterface     $attested_credential_data   The attested credential data instance
     * @param   CborDecoderInterface            $cbor                       The Cbor interface
     */
    public function __construct(
        protected AttestedCredentialInterface $attested_credential_data,
        protected CborDecoderInterface $cbor
    ) {

    }

    public function fromBinary(string $binary): void
    {
        $this->binary = $binary;
        $this->offset = 37;

        // The authenticator data structure is a byte array of 37 bytes or more
        if (strlen($this->binary) < 37) {
            throw new AuthenticatorException('Invalid authenticator input');
        }

        // flags, start after RpIdHash of 32 bytes length and last 1 byte
        $flags = unpack('Cflags', substr($this->binary, 32, 1));
        $this->flags = isset($flags['flags'] ) && is_numeric($flags['flags'] ) ? (int) $flags['flags']  : 0;

        // AttestedCredentialData, after previous data of length of 37 bytes
        if ($this->isAttestedCredentialDataIncluded()) {
            $this->attested_credential_data->fromBinary($this->binary);
            $this->offset = $this->attested_credential_data->getOffset();
        }

        // ExtensionData, after all
        if ($this->isExtensionDataIncluded()) {
            $ext = $this->cbor->decode(substr($this->binary, $this->offset));
            if (!is_array($ext)) {
                throw new AuthenticatorException('invalid extension data');
            }

            $this->extension_data = $ext;
        }
    }

    public function getAAGUID(): string
    {
        return $this->getAttestedCredentialData()->getAAGUID();
    }

    public function getBinary(): string
    {
        return $this->binary;
    }

    public function getCredentialId():string
    {
        return $this->getAttestedCredentialData()->getCredentialId();
    }

    public function getPublicKeyPem(): string
    {
        if (!$this->isAttestedCredentialDataIncluded()) {
            throw  new AuthenticatorException('credential data not included in authenticator data');
        }

        return $this->getAttestedCredentialData()->getCredentialPublicKey()->getPem();
    }

    public function getPublicKeyU2F(): string
    {
        if (!$this->isAttestedCredentialDataIncluded()) {
            throw  new AuthenticatorException('credential data not included in authenticator data');
        }

        return $this->getAttestedCredentialData()->getCredentialPublicKey()->getU2F();
    }

    public function getRpIdHash(): string
    {
        return substr($this->binary, 0, 32);
    }

    public function getSignCount(): int
    {
        $signcount = unpack('Nsigncount', substr($this->binary, 33, 4));

        return $signcount['signcount'] ?? 0;
    }

    public function isUserPresent(): bool
    {
        return !!($this->flags & 1); // bit 0 userPresent
    }

    public function isUserVerified(): bool
    {
        return !!($this->flags & 4); // bit 1 userVerified
    }

    public function isBackupEligible(): bool
    {
        return !!($this->flags & 8); // bit 2 isBackupEligible
    }

    public function isBackup(): bool
    {
        return !!($this->flags & 16); // bit 4 isBackup
    }

    public function isAttestedCredentialDataIncluded(): bool
    {
        return !!($this->flags & 64); // bit 6 attestedDataIncluded
    }

    public function isExtensionDataIncluded(): bool
    {
        return !!($this->flags & 128); // bit 7 extensionDataIncluded
    }

    public function getAttestedCredentialData(): AttestedCredentialInterface
    {
        if (!$this->isAttestedCredentialDataIncluded()) {
            throw  new AuthenticatorException('credential data not included in authenticator data');
        }

        return $this->attested_credential_data;
    }

    public function getExtensionData(): array
    {
        if (!$this->isExtensionDataIncluded()) {
            throw  new AuthenticatorException('extensions data not included in authenticator data');
        }

        return $this->extension_data;
    }
}
