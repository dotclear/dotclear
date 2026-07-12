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
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatAndroidKeyInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn attestation format option for android key format.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class AndroidKey extends FormatBase implements FormatAndroidKeyInterface
{
    private int $_alg;

    private string $_signature;

    private string $_x5c;

    public function initFormat(array $attestation): void
    {
        $this->attestation = $attestation;

        // check u2f data
        $attStmt = $this->attestation['attStmt'];

        if (!is_array($attStmt)
            || !array_key_exists('alg', $attStmt)
            || !is_numeric($attStmt['alg'])
            || is_null($this->_getCoseAlgorithm((int) $attStmt['alg']))
        ) {
            $value = is_array($attStmt) && is_scalar($attStmt['alg']) ? (string) $attStmt['alg'] : '';

            throw new AttestationException(sprintf('unsupported alg: %s', $value));
        }

        if (!array_key_exists('sig', $attStmt)
            || !is_object($attStmt['sig'])
            || !($attStmt['sig'] instanceof ByteBufferInterface)
        ) {
            throw new AttestationException('no signature found');
        }

        if (!array_key_exists('x5c', $attStmt)
            || !is_array($attStmt['x5c'])
            || count($attStmt['x5c']) < 1
        ) {
            throw new AttestationException('invalid x5c certificate');
        }

        if (!is_object($attStmt['x5c'][0])
            || !($attStmt['x5c'][0] instanceof ByteBufferInterface)
        ) {
            throw new AttestationException('invalid x5c certificate');
        }

        $this->_alg       = (int) $attStmt['alg'];
        $this->_signature = $attStmt['sig']->getBinaryString();
        $this->_x5c       = $attStmt['x5c'][0]->getBinaryString();

        $counter = count($attStmt['x5c']);
        if ($counter > 1) {
            for ($i = 1; $i < $counter; $i++) {
                if ($attStmt['x5c'][$i] instanceof ByteBufferInterface) {
                    $this->_x5c_chain[] = $attStmt['x5c'][$i]->getBinaryString();
                }
            }

            unset($i);
        }
    }

    public function getCertificatePem(): string
    {
        return $this->_createCertificatePem($this->_x5c);
    }

    public function validateAttestation(string $clientDataHash): bool
    {
        $publicKey = openssl_pkey_get_public($this->getCertificatePem());

        if ($publicKey === false) {
            $error = is_string($error = openssl_error_string()) ? $error : '<unknown error>';

            throw new AttestationException(sprintf('invalid public key: %s', $error));
        }

        // Verify that sig is a valid signature over the concatenation of authenticatorData and clientDataHash
        // using the attestation public key in attestnCert with the algorithm specified in alg.
        $dataToVerify = $this->authenticator->getBinary();
        $dataToVerify .= $clientDataHash;

        $coseAlgorithm = $this->_getCoseAlgorithm($this->_alg);

        // check certificate
        $openssl = isset($coseAlgorithm->openssl) && is_numeric($openssl = $coseAlgorithm->openssl) ? (int) $openssl : 0;
        if ($openssl === 0) {
            $openssl = isset($coseAlgorithm->openssl) && is_string($openssl = $coseAlgorithm->openssl) ? $openssl : 0;
        }

        return openssl_verify($dataToVerify, $this->_signature, $publicKey, $openssl) === 1;
    }

    public function validateRootCertificate(array $rootCas): bool
    {
        $chainC = $this->_createX5cChainFile();
        if ($chainC) {
            $rootCas[] = $chainC;
        }

        $v = openssl_x509_checkpurpose($this->getCertificatePem(), -1, $rootCas);
        if ($v === -1) {
            $error = is_string($error = openssl_error_string()) ? $error : '<unknown error>';

            throw new AttestationException(sprintf('error on validating root certificate: %s', $error));
        }

        return (bool) $v;
    }
}
