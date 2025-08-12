<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Data;

use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestationInterface;

/**
 * @brief   WebAuthn credential data interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface CredentialInterface
{
    /**
     * Parse data from Attestation Object from response.
     *
     * @param   AttestationInterface    $attestation    The attestation intance
     */
    public function fromAttestation(AttestationInterface $attestation): void;

    /**
     * Parse data from an array.
     *
     * @param   array<string, mixed>    $res    The data to parse
     */
    public function fromArray(array $res): void;

    /**
     * Safe encode data.
     *
     * Used to encode data before adding it to database.
     *
     * @return  string  Encoded data
     */
    public function encodeData(): string;

    /**
     * Safe decode data and return a new instance of itself.
     *
     * Decode string encoded with self::encodeData().
     *
     * @param   string  $data   The encoded data
     *
     * @return  CredentialInterface     Self instance
     */
    public function decodeData(string $data): CredentialInterface;

    /**
     * Get creation date.
     *
     * @return  string  The creation date
     */
    public function createDate(): string;

    /**
     * Get attestation format.
     *
     * @return  string  The attestation format
     */
    public function attestationFormat(): string;

    /**
     * Get credential ID.
     *
     * @return  string  The credential ID.
     */
    public function credentialId(): string;

    /**
     * Get credential public key.
     *
     * @return  string  The credential public key
     */
    public function credentialPublicKey(): string;

    /**
     * Get certificate chain.
     *
     * @return  string  The certificate chain
     */
    public function certificateChain(): string;

    /**
     * Get certificate.
     *
     * @return  string  The certificate
     */
    public function certificate(): string;

    /**
     * Get certificate issuer.
     *
     * @return  string  The certificate issuer
     */
    public function certificateIssuer(): string;

    /**
     * Get certificate subject.
     *
     * @return  string  The certificate subject
     */
    public function certificateSubject(): string;

    /**
     * Get signature counter.
     *
     * @return  int     The signature counter
     */
    public function signatureCounter(): int;

    /**
     * Get the AAGUID.
     *
     * @return  string  The AAGUID
     */
    public function AAGUID(): string;

    /**
     * Get the UUID (formatted AAGUID).
     *
     * @return  string  The AAGUID
     */
    public function UUID(): string;

    /**
     * Check if user is present.
     *
     * @return  bool    The user presence
     */
    public function userPresent(): bool;

    /**
     * Check if user verified
     *
     * @return  bool    The user verification
     */
    public function userVerified(): bool;

    /**
     * Check if backup is eligible.
     *
     * @return  bool    The backup eligibilty
     */
    public function isBackupEligible(): bool;

    /**
     * Check if it is backuped.
     *
     * @return  bool    If backuped
     */
    public function isBackedUp(): bool;
}