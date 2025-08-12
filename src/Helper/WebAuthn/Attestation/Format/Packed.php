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
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatPackedInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn attestation format option for packed format.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Packed extends FormatBase implements FormatPackedInterface
{
    private int $_alg;
    private string $_signature;
    private string $_x5c;

    public function initFormat(array $attestation): void
    {
        $this->attestation = $attestation;

        // check packed data
        $attStmt = $this->attestation['attStmt'];

        if (!array_key_exists('alg', $attStmt) || $this->_getCoseAlgorithm($attStmt['alg']) === null) {
            throw new AttestationException(sprintf('unsupported alg: %s', $attStmt['alg']));
        }

        if (!array_key_exists('sig', $attStmt) || !is_object($attStmt['sig']) || !($attStmt['sig'] instanceof ByteBufferInterface)) {
            throw new AttestationException('no signature found');
        }

        $this->_alg = $attStmt['alg'];
        $this->_signature = $attStmt['sig']->getBinaryString();

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
        }
    }

    public function getCertificatePem(): string
    {
        if (!$this->_x5c) {
            return '';
        }

        return $this->_createCertificatePem($this->_x5c);
    }

    public function validateAttestation(string $clientDataHash):bool
    {
        if ($this->_x5c) {
            return $this->_validateOverX5c($clientDataHash);
        } else {
            return $this->_validateSelfAttestation($clientDataHash);
        }
    }

    public function validateRootCertificate(array $rootCas):bool
    {
        if (!$this->_x5c) {
            return false;
        }

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

        // Verify that sig is a valid signature over the concatenation of authenticatorData and clientDataHash
        // using the attestation public key in attestnCert with the algorithm specified in alg.
        $dataToVerify = $this->authenticator->getBinary();
        $dataToVerify .= $clientDataHash;

        $coseAlgorithm = $this->_getCoseAlgorithm($this->_alg);

        // check certificate
        return openssl_verify($dataToVerify, $this->_signature, $publicKey, $coseAlgorithm->openssl ?? 0) === 1;
    }

    /**
     * Validate if self attestation is in use.
     *
     * @param   string  $clientDataHash
     *
     * @return bool
     */
    protected function _validateSelfAttestation(string $clientDataHash):bool
    {
        // Verify that sig is a valid signature over the concatenation of authenticatorData and clientDataHash
        // using the credential public key with alg.
        $dataToVerify = $this->authenticator->getBinary();
        $dataToVerify .= $clientDataHash;

        $publicKey = $this->authenticator->getPublicKeyPem();

        // check certificate
        return openssl_verify($dataToVerify, $this->_signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}