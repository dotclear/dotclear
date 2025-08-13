<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Attestation\Format;

/**
 * @brief   WebAuthn attestation format interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface FormatBaseInterface
{
    /**
     * Inilialise format.
     *
     * @param   array<string,mixed>     $attestation    The attestationObject form response
     */
    public function initFormat(array $attestation): void;

    /**
     * Delete X.509 chain certificate file after use.
     */
    //public function __destruct();

    /**
     * Returns the certificate chain in PEM format.
     */
    public function getCertificateChain(): string;

    /**
     * Returns the key X.509 certificate in PEM format.
     */
    public function getCertificatePem(): string;

    /**
     * Checks validity of the signature.
     */
    public function validateAttestation(string $clientDataHash): bool;

    /**
     * Validates the certificate against root certificates.
     *
     * @param   string[]    $rootCas
     */
    public function validateRootCertificate(array $rootCas): bool;
}
