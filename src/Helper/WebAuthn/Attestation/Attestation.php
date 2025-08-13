<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Attestation;

use Dotclear\Helper\WebAuthn\WebAuthnContainer;
use Dotclear\Helper\WebAuthn\Exception\AttestationException;
use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestationInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatBaseInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;

/**
 * @brief   WebAuthn attestation helper.
 *
 * attestation from AuthenticatorAttestationResponse.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Attestation implements AttestationInterface
{
    /**
     * The attestation format instance.
     */
    protected FormatBaseInterface $attestation_format;

    /**
     * The attestation type enumerator.
     */
    protected AttestationFormatsEnum $attestation_type;

    /**
     * Load services from container.
     *
     * @param   AuthenticatorInterface  $authenticator  The AuthenticatorData instance
     * @param   CborDecoderInterface    $cbor           The Cbor interface
     * @param   WebAuthnContainer       $webauthn       The WebAuthn instance
     */
    public function __construct(
        protected AuthenticatorInterface $authenticator,
        protected CborDecoderInterface $cbor,
        protected WebAuthnContainer $webauthn,
    ) {
    }

    public function fromResponse(ByteBufferInterface|string $binary, array $allowed_formats): void
    {
        $enc = $this->cbor->decode($binary);

        if (!is_array($enc) || !array_key_exists('fmt', $enc) || !is_string($enc['fmt'])) {
            throw new AttestationException('invalid attestation format');
        }

        if (!array_key_exists('attStmt', $enc) || !is_array($enc['attStmt'])) {
            throw new AttestationException('invalid attestation format (attStmt not available)');
        }

        if (!array_key_exists('authData', $enc) || !is_object($enc['authData']) || !($enc['authData'] instanceof ByteBufferInterface)) {
            throw new AttestationException('invalid attestation format (authData not available)');
        }

        $this->authenticator->fromBinary($enc['authData']->getBinaryString());
        $this->attestation_type = AttestationFormatsEnum::from($enc['fmt']);

        if (!in_array($this->attestation_type->value, $allowed_formats)) {
            throw new AttestationException(sprintf('invalid atttestation format: %s', $this->attestation_type->value));
        }

        // Load all services that inherit FormatBaseInterface and find whitch one match format type
        foreach ($this->webauthn->getFactory()->dump() as $interface => $service) {
            if (is_subclass_of($service, FormatBaseInterface::class) && $service::TYPE == $this->attestation_type) { // @phpstan-ignore-line
                $this->attestation_format = $this->webauthn->get($interface);
                $this->attestation_format->initFormat($enc);

                break;
            }
        }

        if (!isset($this->attestation_format)) {
            throw new AttestationException(sprintf('unknown atttestation format of type: %s', $this->attestation_type->value));
        }
    }

    public function getAttestationFormatType(): AttestationFormatsEnum
    {
        return $this->attestation_type;
    }

    public function getAttestationFormat(): FormatBaseInterface
    {
        return $this->attestation_format;
    }

    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }

    public function getCertificateChain(): string
    {
        return $this->attestation_format->getCertificateChain();
    }

    public function getCertificateIssuer(): string
    {
        $pem    = $this->getCertificatePem();
        $issuer = '';
        if ($pem !== '') {
            $info = openssl_x509_parse($pem);
            if (is_array($info) && array_key_exists('issuer', $info) && is_array($info['issuer'])) {
                $cn = $info['issuer']['CN'] ?? '';
                $o  = $info['issuer']['O']  ?? '';
                $ou = $info['issuer']['OU'] ?? '';

                if ($cn) {
                    $issuer .= $cn;
                }
                if ($issuer && ($o || $ou)) {
                    $issuer .= ' (' . trim($o . ' ' . $ou) . ')';
                } else {
                    $issuer .= trim($o . ' ' . $ou);
                }
            }
        }

        return $issuer;
    }

    public function getCertificateSubject(): string
    {
        $pem     = $this->getCertificatePem();
        $subject = '';
        if ($pem !== '') {
            $info = openssl_x509_parse($pem);
            if (is_array($info) && array_key_exists('subject', $info) && is_array($info['subject'])) {
                $cn = $info['subject']['CN'] ?? '';
                $o  = $info['subject']['O']  ?? '';
                $ou = $info['subject']['OU'] ?? '';

                if ($cn) {
                    $subject .= $cn;
                }
                if ($subject && ($o || $ou)) {
                    $subject .= ' (' . trim($o . ' ' . $ou) . ')';
                } else {
                    $subject .= trim($o . ' ' . $ou);
                }
            }
        }

        return $subject;
    }

    public function getCertificatePem(): string
    {
        return $this->attestation_format->getCertificatePem();
    }

    public function validateAttestation(string $hash): bool
    {
        return $this->attestation_format->validateAttestation($hash);
    }

    public function validateRootCertificate(array $paths): bool
    {
        return $this->attestation_format->validateRootCertificate($paths);
    }

    public function validateRpIdHash(string $hash): bool
    {
        return $hash === $this->authenticator->getRpIdHash();
    }
}
