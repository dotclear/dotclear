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
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatAndroidSafetyNetInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use stdClass;

/**
 * @brief   WebAuthn attestation format option for android safetynet format.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class AndroidSafetyNet extends FormatBase implements FormatAndroidSafetyNetInterface
{
    private string $_signature;
    private string $_signedValue;
    private string $_x5c;
    private stdClass $_payload;

    public function initFormat(array $attestation): void
    {
        $this->attestation = $attestation;

        // check data
        $attStmt = $this->attestation['attStmt'];

        if (!array_key_exists('ver', $attStmt) || !$attStmt['ver']) {
            throw new AttestationException('invalid Android Safety Net Format');
        }

        if (!array_key_exists('response', $attStmt) || !($attStmt['response'] instanceof ByteBufferInterface)) {
            throw new AttestationException('invalid Android Safety Net Format');
        }

        $response = $attStmt['response']->getBinaryString();

        // Response is a JWS [RFC7515] object in Compact Serialization.
        // JWSs have three segments separated by two period ('.') characters
        $parts = explode('.', $response);
        unset ($response);
        if (count($parts) !== 3) {
            throw new AttestationException('invalid JWS data');
        }

        $header             = $this->_base64url_decode($parts[0]);
        $payload            = $this->_base64url_decode($parts[1]);
        $this->_signature   = $this->_base64url_decode($parts[2]);
        $this->_signedValue = $parts[0] . '.' . $parts[1];
        unset ($parts);

        $header = json_decode($header);
        $payload = json_decode($payload);

        if (!($header instanceof stdClass)) {
            throw new AttestationException('invalid JWS header');
        }
        if (!($payload instanceof stdClass)) {
            throw new AttestationException('invalid JWS payload');
        }

        if (!isset($header->x5c) || !is_array($header->x5c) || count($header->x5c) === 0) {
            throw new AttestationException('No X.509 signature in JWS Header');
        }

        // algorithm
        if (!in_array($header->alg, array('RS256', 'ES256'))) {
            throw new AttestationException(sprintf('invalid JWS algorithm %s', $header->alg));
        }

        $this->_x5c     = base64_decode($header->x5c[0]);
        $this->_payload = $payload;

        if (count($header->x5c) > 1) {
            for ($i=1; $i<count($header->x5c); $i++) {
                $this->_x5c_chain[] = base64_decode($header->x5c[$i]);
            }
            unset ($i);
        }
    }

    /**
     * ctsProfileMatch: A stricter verdict of device integrity.
     *
     * If the value of ctsProfileMatch is true, then the profile of the device running your app matches
     * the profile of a device that has passed Android compatibility testing and
     * has been approved as a Google-certified Android device.
     *
     * @return bool
     */
    public function ctsProfileMatch(): bool
    {
        return isset($this->_payload->ctsProfileMatch) ? !!$this->_payload->ctsProfileMatch : false;
    }

    public function getCertificatePem(): string
    {
        return $this->_createCertificatePem($this->_x5c);
    }

    public function validateAttestation(string $clientDataHash): bool
    {
        $publicKey = openssl_pkey_get_public($this->getCertificatePem());

        // Verify that the nonce in the response is identical to the Base64 encoding
        // of the SHA-256 hash of the concatenation of authenticatorData and clientDataHash.
        if (empty($this->_payload->nonce) || $this->_payload->nonce !== base64_encode(\hash('SHA256', $this->authenticator->getBinary() . $clientDataHash, true))) {
            throw new AttestationException('invalid nonce in JWS payload');
        }

        // Verify that attestationCert is issued to the hostname "attest.android.com"
        $certInfo = openssl_x509_parse($this->getCertificatePem());
        if (!is_array($certInfo) || ($certInfo['subject']['CN'] ?? '') !== 'attest.android.com') {
            throw new AttestationException(sprintf('invalid certificate CN in JWS (%s)', $certInfo['subject']['CN'] ?? '-'));
        }

        // Verify that the basicIntegrity attribute in the payload of response is true.
        if (empty($this->_payload->basicIntegrity)) {
            throw new AttestationException('invalid basicIntegrity in payload');
        }

        // check certificate
        return openssl_verify($this->_signedValue, $this->_signature, $publicKey ?: [], OPENSSL_ALGO_SHA256) === 1;
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
     * Decode base64 url.
     *
     * @param   string $data
     *
     * @return  string
     */
    private function _base64url_decode(string $data):string 
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}