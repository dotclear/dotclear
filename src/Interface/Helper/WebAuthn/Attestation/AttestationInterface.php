<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Attestation;

use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatBaseInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn attestation interface.
 *
 * attestation from AuthenticatorAttestationResponse.
 *
 * Methods are used to follow some rules from
 * https://www.w3.org/TR/webauthn/#sctn-registering-a-new-credential
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface AttestationInterface
{
    /**
     * Parse attestation oject from flow response.
     *
     * 12. Perform CBOR decoding on the attestationObject field of the AuthenticatorAttestationResponse structure
     *
     * @param   string[]                    $allowed_formats
     */
    public function fromResponse(ByteBufferInterface|string $binary, array $allowed_formats): void;

    /**
     * Returns the attestation format name.
     */
    public function getAttestationFormatType(): AttestationFormatsEnum;

    /**
     * Returns the attestation format class.
     */
    public function getAttestationFormat(): FormatBaseInterface;

    /**
     * Returns the attestation public key in PEM format.
     */
    public function getAuthenticator(): AuthenticatorInterface;

    /**
     * Returns the certificate chain as PEM.
     */
    public function getCertificateChain(): string;

    /**
     * Return the certificate issuer as string.
     */
    public function getCertificateIssuer(): string;

    /**
     * Return the certificate subject as string.
     */
    public function getCertificateSubject(): string;

    /**
     * Returns the key certificate in PEM format.
     */
    public function getCertificatePem(): string;

    /**
     * Checks validity of the signature (ClientDataHash).
     *
     * 19. Verify that attStmt is a correct attestation statement, conveying a valid attestation signature
     */
    public function validateAttestation(string $hash): bool;

    /**
     * Validates the certificate against root certificates.
     *
     * @param   string[]    $paths
     */
    public function validateRootCertificate(array $paths): bool;

    /**
     * Checks if the RpId Hash is valid.
     *
     * 13. Verify that the RP ID hash in authData is indeed the SHA-256 hash of the RP ID expected by the RP.
     */
    public function validateRpIdHash(string $hash): bool;
}
