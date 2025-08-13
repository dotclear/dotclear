<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Attestation;

/**
 * @brief   WebAuthn authenticator data interface.
 *
 * @see     https://www.w3.org/TR/webauthn/#sctn-authenticator-data
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface AuthenticatorInterface
{
    /**
     * Create authenticator data instance from binary.
     *
     * @param   string  $binary     The attested credential data binary
     */
    public function fromBinary(string $binary): void;

    /**
     * Get Authenticator Attestation Globally Unique Identifier.
     */
    public function getAAGUID(): string;

    /**
     * Get authenticatorData as binary.
     */
    public function getBinary(): string;

    /**
     * Get credentialId.
     */
    public function getCredentialId(): string;
    /**
     * Get public key in PEM format.
     */
    public function getPublicKeyPem(): string;

    /**
     * Get public key in U2F format.
     */
    public function getPublicKeyU2F(): string;

    /**
     * Get SHA256 hash of the relying party ID.
     */
    public function getRpIdHash(): string;

    /**
     * Get signature counter.
     *
     * 32-bit unsigned big-endian integer.
     */
    public function getSignCount(): int;

    /**
     * Check if the user is present.
     */
    public function isUserPresent(): bool;

    /**
     * Check if the user is verified.
     */
    public function isUserVerified(): bool;

    /**
     * Check if the backup is eligible.
     */
    public function isBackupEligible(): bool;

    /**
     * Check if the current credential is backed up.
     */
    public function isBackup(): bool;

    /**
     * Check if the attested data is included in authenticator data.
     */
    public function isAttestedCredentialDataIncluded(): bool;

    /**
     * Check if the extensions data is included in authenticator data.
     */
    public function isExtensionDataIncluded(): bool;

    /**
     * Get attested credential data.
     */
    public function getAttestedCredentialData(): AttestedCredentialInterface;

    /**
     * Get extension data.
     *
     * @return  array<mixed>
     */
    public function getExtensionData(): array;
}
