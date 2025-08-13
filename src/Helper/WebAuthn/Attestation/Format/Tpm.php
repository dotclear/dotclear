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
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatTpmInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn attestation format option for tpm format.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Tpm extends FormatBase implements FormatTpmInterface
{
    private string $_TPM_GENERATED_VALUE = "\xFF\x54\x43\x47";
    private string $_TPM_ST_ATTEST_CERTIFY = "\x80\x17";
    private int $_alg;
    private string $_signature;
    private string $_x5c;
    private ByteBufferInterface $_certInfo;

    public function __construct(
        protected ByteBufferInterface $buffer
    ) {

    }

    public function initFormat(array $attestation): void
    {
        $this->attestation = $attestation;

        // check packed data
        $attStmt = $this->attestation['attStmt'];

        if (!array_key_exists('ver', $attStmt) || $attStmt['ver'] !== '2.0') {
            throw new AttestationException(sprintf('invalid tpm version: %s', $attStmt['ver']));
        }

        if (!array_key_exists('alg', $attStmt) || $this->_getCoseAlgorithm($attStmt['alg']) === null) {
            throw new AttestationException(sprintf('unsupported alg: %s', $attStmt['alg']));
        }

        if (!array_key_exists('sig', $attStmt) || !is_object($attStmt['sig']) || !($attStmt['sig'] instanceof ByteBufferInterface)) {
            throw new AttestationException('signature not found');
        }

        if (!array_key_exists('certInfo', $attStmt) || !is_object($attStmt['certInfo']) || !($attStmt['certInfo'] instanceof ByteBufferInterface)) {
            throw new AttestationException('certInfo not found');
        }

        if (!array_key_exists('pubArea', $attStmt) || !is_object($attStmt['pubArea']) || !($attStmt['pubArea'] instanceof ByteBufferInterface)) {
            throw new AttestationException('pubArea not found');
        }

        $this->_alg       = $attStmt['alg'];
        $this->_signature = $attStmt['sig']->getBinaryString();
        $this->_certInfo  = $attStmt['certInfo'];

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
            throw new AttestationException('no x5c certificate found');
        }
    }

    public function getCertificatePem(): string
    {
        if (!$this->_x5c) {
            return '';
        }

        return $this->_createCertificatePem($this->_x5c);
    }

    public function validateAttestation(string $clientDataHash): bool
    {
        return $this->_validateOverX5c($clientDataHash);
    }

    public function validateRootCertificate(array $rootCas): bool
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

        // Concatenate authenticatorData and clientDataHash to form attToBeSigned.
        $attToBeSigned = $this->authenticator->getBinary();
        $attToBeSigned .= $clientDataHash;

        // Validate that certInfo is valid:

        // Verify that magic is set to TPM_GENERATED_VALUE.
        if ($this->_certInfo->getBytes(0, 4) !== $this->_TPM_GENERATED_VALUE) {
            throw new AttestationException('tpm magic not TPM_GENERATED_VALUE');
        }

        // Verify that type is set to TPM_ST_ATTEST_CERTIFY.
        if ($this->_certInfo->getBytes(4, 2) !== $this->_TPM_ST_ATTEST_CERTIFY) {
            throw new AttestationException('tpm type not TPM_ST_ATTEST_CERTIFY');
        }

        $offset = 6;
        $qualifiedSigner = $this->_tpmReadLengthPrefixed($this->_certInfo, $offset);
        $extraData = $this->_tpmReadLengthPrefixed($this->_certInfo, $offset);
        $coseAlg = $this->_getCoseAlgorithm($this->_alg);

        // Verify that extraData is set to the hash of attToBeSigned using the hash algorithm employed in "alg".
        if ($extraData->getBinaryString() !== hash($coseAlg->hash ?? '', $attToBeSigned, true)) {
            throw new AttestationException('certInfo:extraData not hash of attToBeSigned');
        }

        // Verify the sig is a valid signature over certInfo using the attestation
        // public key in aikCert with the algorithm specified in alg.
        return openssl_verify($this->_certInfo->getBinaryString(), $this->_signature, $publicKey, $coseAlg->openssl ?? 0) === 1;
    }


    /**
     * Returns next part of ByteBuffer.
     *
     * @param   ByteBufferInterface  $buffer
     * @param   int         $offset
     *
     * @return  ByteBufferInterface
     */
    protected function _tpmReadLengthPrefixed(ByteBufferInterface $buffer, int &$offset): ByteBufferInterface
    {
        $len    = (int) $buffer->getUint16Val($offset);
        $data   = $buffer->getBytes($offset + 2, $len);
        $offset += (2 + $len);

        return $this->buffer->fromBinary($data);
    }

}