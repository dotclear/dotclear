<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Attestation\Format;

use Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatBaseInterface;
use stdClass;

/**
 * @brief   WebAuthn attestation format option helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class FormatBase implements FormatBaseInterface
{
    /**
     * @var     string[]    $_x5c_chain
     */
    protected array $_x5c_chain = [];

    protected ?string $_x5c_tempFile = null;

    /**
     * @var     array<string,mixed>     $attestation
     */
    protected array $attestation;

    /**
     * @param   AuthenticatorInterface  $authenticator  The authenticatorData instance
     */
    public function __construct(
        protected AuthenticatorInterface $authenticator
    ) {

    }

    public function initFormat(array $attestation): void
    {
        $this->attestation = $attestation;
    }

    public function __destruct()
    {
        if ($this->_x5c_tempFile && is_file($this->_x5c_tempFile)) {
            // Delete X.509 chain certificate file after use.
            unlink($this->_x5c_tempFile);
        }
    }

    public function getCertificateChain(): string
    {
        if ($this->_x5c_tempFile && is_file($this->_x5c_tempFile)) {
            return (string) file_get_contents($this->_x5c_tempFile);
        }

        return '';
    }

    public function getCertificatePem(): string
    {
        // MUST be implemented by child class
        return '';
    }

    public function validateAttestation(string $clientDataHash):bool
    {
        // need to be overwritten
        return false;
    }

    public function validateRootCertificate(array $rootCas): bool
    {
        // need to be overwritten
        return false;
    }

    /**
     * Create a PEM encoded certificate with X.509 binary data.
     *
     * @param   string  $x5c
     *
     * @return  string
     */
    protected function _createCertificatePem(string $x5c): string
    {
        $pem = '-----BEGIN CERTIFICATE-----' . "\n";
        $pem .= chunk_split(base64_encode($x5c), 64, "\n");
        $pem .= '-----END CERTIFICATE-----' . "\n";
        return $pem;
    }

    /**
     * Creates a PEM encoded chain file.
     *
     * @return  string|null
     */
    protected function _createX5cChainFile(): null|string
    {
        $content = '';
        if (count($this->_x5c_chain) > 0) {
            foreach ($this->_x5c_chain as $x5c) {
                $certInfo = openssl_x509_parse($this->_createCertificatePem($x5c));

                // check if certificate is self signed
                if (is_array($certInfo) && is_array($certInfo['issuer']) && is_array($certInfo['subject'])) {
                    $selfSigned = false;

                    $subjectKeyIdentifier = $certInfo['extensions']['subjectKeyIdentifier'] ?? null;
                    $authorityKeyIdentifier = $certInfo['extensions']['authorityKeyIdentifier'] ?? null;

                    if ($authorityKeyIdentifier && substr($authorityKeyIdentifier, 0, 6) === 'keyid:') {
                        $authorityKeyIdentifier = substr($authorityKeyIdentifier, 6);
                    }
                    if ($subjectKeyIdentifier && substr($subjectKeyIdentifier, 0, 6) === 'keyid:') {
                        $subjectKeyIdentifier = substr($subjectKeyIdentifier, 6);
                    }

                    if (($subjectKeyIdentifier && !$authorityKeyIdentifier) || ($authorityKeyIdentifier && $authorityKeyIdentifier === $subjectKeyIdentifier)) {
                        $selfSigned = true;
                    }

                    if (!$selfSigned) {
                        $content .= "\n" . $this->_createCertificatePem($x5c) . "\n";
                    }
                }
            }
        }

        if ($content) {
            $this->_x5c_tempFile = (string) tempnam(sys_get_temp_dir(), 'x5c_');
            if (file_put_contents($this->_x5c_tempFile, $content) !== false) {
                return $this->_x5c_tempFile;
            }
        }

        return null;
    }


    /**
     * Returns the name and openssl key for provided cose number.
     *
     * @param   int     $coseNumber
     *
     * @return  stdClass|null
     */
    protected function _getCoseAlgorithm(int $coseNumber): null|stdClass
    {
        // https://www.iana.org/assignments/cose/cose.xhtml#algorithms
        $coseAlgorithms = [
            [
                'hash' => 'SHA1',
                'openssl' => OPENSSL_ALGO_SHA1,
                'cose' => [
                    -65535  // RS1
                ]],

            [
                'hash' => 'SHA256',
                'openssl' => OPENSSL_ALGO_SHA256,
                'cose' => [
                    -257, // RS256
                    -37,  // PS256
                    -7,   // ES256
                    5     // HMAC256
                ]],

            [
                'hash' => 'SHA384',
                'openssl' => OPENSSL_ALGO_SHA384,
                'cose' => [
                    -258, // RS384
                    -38,  // PS384
                    -35,  // ES384
                    6     // HMAC384
                ]],

            [
                'hash' => 'SHA512',
                'openssl' => OPENSSL_ALGO_SHA512,
                'cose' => [
                    -259, // RS512
                    -39,  // PS512
                    -36,  // ES512
                    7     // HMAC512
                ]]
        ];

        foreach ($coseAlgorithms as $coseAlgorithm) {
            if (in_array($coseNumber, $coseAlgorithm['cose'], true)) {
                $return = new stdClass();
                $return->hash = $coseAlgorithm['hash'];
                $return->openssl = $coseAlgorithm['openssl'];
                return $return;
            }
        }

        return null;
    }
}