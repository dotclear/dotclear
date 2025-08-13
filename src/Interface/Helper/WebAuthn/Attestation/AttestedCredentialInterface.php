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
 * @brief   WebAuthn attested credential data interface.
 *
 * attested credential data from authenticator data.
 *
 * @see     https://www.w3.org/TR/webauthn/#sctn-authenticator-data
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface AttestedCredentialInterface
{
    /**
     * Create attested credential data instance from binary.
     *
     * @param   string  $binary     The attested credential data binary
     */
    public function fromBinary(string $binary): void;

    /**
     * Get current binary offset.
     *
     * @return  int     The offset
     */
    public function getOffset(): int;

    /**
     * Get AAGUID.
     *
     * @return  string  THe AAGUID
     */
    public function getAAGUID(): string;

    /**
     * Get credential ID.
     *
     * @return  string  The credential ID
     */
    public function getCredentialID(): string;

    /**
     * Get credential public key instance.
     *
     * @return  CredentialPublicKeyInterface    The credential public key instance
     */
    public function getCredentialPublicKey(): CredentialPublicKeyInterface;
}
