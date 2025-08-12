<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Attestation\Format;

use Dotclear\Helper\WebAuthn\Exception\AttestationException;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatAppleInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn attestation format option for apple format.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Apple extends FormatBase implements FormatAppleInterface
{
    private string $_x5c;

    public function initFormat(array $attestation): void
    {
        $this->attestation = $attestation;

        // check packed data
        $attStmt = $this->attestation['attStmt'];

        // certificate for validation
        if (array_key_exists('x5c', $attStmt) && is_array($attStmt['x5c']) && count($attStmt['x5c']) > 0) {

            // The attestation certificate attestnCert MUST be the first element in the array
            $attestnCert = array_shift($attStmt['x5c']);

            if (!($attestnCert instanceof ByteBufferInterface)) {
                throw new AttestationException('invalid x5c certificate');
            }

            $this->_x5c = $attestnCert->getBinaryString();

            // certificate chain
            foreach ($attStmt['x5c'] as $chain) {
                if ($chain instanceof ByteBufferInterface) {
                    $this->_x5c_chain[] = $chain->getBinaryString();
                }
            }
        } else {
            throw new AttestationException('invalid Apple attestation statement: missing x5c');
        }
    }

    public function getCertificatePem(): string
    {
        return $this->_createCertificatePem($this->_x5c);
    }

    public function validateAttestation(string $clientDataHash): bool
    {
        return $this->_validateOverX5c($clientDataHash);
    }

    public function validateRootCertificate(array $rootCas): bool
    {
        $chainC = $this->_createX5cChainFile();
        if ($chainC) {
            $rootCas[] = $chainC;
        }

        $v = openssl_x509_checkpurpose($this->getCertificatePem(), -1, $rootCas);
        if ($v === -1) {
            throw new AttestationException(sprintf('error on validating root certificate: %s', openssl_error_string()));
        }

        return (bool) $v;
    }

    /**
     * Validate if x5c is present.
     *
     * @param   string  $clientDataHash
     *
     * @return  bool
     */
    protected function _validateOverX5c(string $clientDataHash): bool
    {
        $publicKey = openssl_pkey_get_public($this->getCertificatePem());

        if ($publicKey === false) {
            throw new AttestationException(sprintf('invalid public key: %s', openssl_error_string()));
        }

        // Concatenate authenticatorData and clientDataHash to form nonceToHash.
        $nonceToHash = $this->authenticator->getBinary();
        $nonceToHash .= $clientDataHash;

        // Perform SHA-256 hash of nonceToHash to produce nonce
        $nonce = hash('SHA256', $nonceToHash, true);

        $credCert = openssl_x509_read($this->getCertificatePem());
        if ($credCert === false) {
            throw new AttestationException(sprintf('invalid x5c certificate: %s', openssl_error_string()));
        }

        $pubKey  = openssl_pkey_get_public($credCert);
        $keyData = $pubKey === false ? null : openssl_pkey_get_details($pubKey);
        $key     = is_array($keyData) && array_key_exists('key', $keyData) ? $keyData['key'] : null;


        // Verify that nonce equals the value of the extension with OID ( 1.2.840.113635.100.8.2 ) in credCert.
        $parsedCredCert = openssl_x509_parse($credCert);
        $nonceExtension = $parsedCredCert['extensions']['1.2.840.113635.100.8.2'] ?? '';

        // nonce padded by ASN.1 string: 30 24 A1 22 04 20
        // 30     — type tag indicating sequence
        // 24     — 36 byte following
        //   A1   — Enumerated [1]
        //   22   — 34 byte following
        //     04 — type tag indicating octet string
        //     20 — 32 byte following

        $asn1Padding = "\x30\x24\xA1\x22\x04\x20";
        if (substr($nonceExtension, 0, strlen($asn1Padding)) === $asn1Padding) {
            $nonceExtension = substr($nonceExtension, strlen($asn1Padding));
        }

        if ($nonceExtension !== $nonce) {
            throw new AttestationException('nonce doesn\'t equal the value of the extension with OID 1.2.840.113635.100.8.2');
        }

        // Verify that the credential public key equals the Subject Public Key of credCert.
        $pubKey = openssl_pkey_get_public($this->authenticator->getPublicKeyPem());
        $authKeyData = $pubKey === false ? null : openssl_pkey_get_details($pubKey);
        $authKey = is_array($authKeyData) && array_key_exists('key', $authKeyData) ? $authKeyData['key'] : null;

        if ($key === null || $key !== $authKey) {
            throw new AttestationException('credential public key doesn\'t equal the Subject Public Key of credCert');
        }

        return true;
    }
}