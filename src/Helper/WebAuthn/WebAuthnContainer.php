<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear\Helper\WebAuthn
 * @brief       Dotclear WebAuthn services
 */

namespace Dotclear\Helper\WebAuthn;

// Container
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factories;
use Dotclear\Helper\Container\Factory;

// Interface
use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestationInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestedCredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\CredentialPublicKeyInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatAndroidKeyInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatAndroidSafetyNetInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatAppleInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatNoneInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatPackedInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatTpmInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatU2fInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\PublicKeyInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AttestationOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AttestationFormatsOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AuthenticatorSelectionOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\ChallengeOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\ExcludeCredentialsOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\ExtensionsOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\PubKeyCredParamsOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\TimeoutOptionInterface;
//use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\TransportsOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AllowCredentialsOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\CertificatesInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\ClientInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\ProviderInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\DerEncoderInterface;

// Default class
use Dotclear\Helper\WebAuthn\Attestation\Attestation;
use Dotclear\Helper\WebAuthn\Attestation\AttestedCredential;
use Dotclear\Helper\WebAuthn\Attestation\Authenticator;
use Dotclear\Helper\WebAuthn\Attestation\CredentialPublicKey;
use Dotclear\Helper\WebAuthn\Attestation\Format\AndroidKey;
use Dotclear\Helper\WebAuthn\Attestation\Format\AndroidSafetyNet;
use Dotclear\Helper\WebAuthn\Attestation\Format\Apple;
use Dotclear\Helper\WebAuthn\Attestation\Format\None;
use Dotclear\Helper\WebAuthn\Attestation\Format\Packed;
use Dotclear\Helper\WebAuthn\Attestation\Format\Tpm;
use Dotclear\Helper\WebAuthn\Attestation\Format\U2f;
use Dotclear\Helper\WebAuthn\Credentials\PublicKey;
use Dotclear\Helper\WebAuthn\Credentials\Option\AttestationOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\AttestationFormatsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\AuthenticatorSelectionOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\ChallengeOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\ExcludeCredentialsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\ExtensionsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\PubKeyCredParamsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\RpOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\TimeoutOption;
//use Dotclear\Helper\WebAuthn\Credentials\Option\TransportsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\UserOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\AllowCredentialsOption;
use Dotclear\Helper\WebAuthn\Data\Certificates;
use Dotclear\Helper\WebAuthn\Data\Client;
use Dotclear\Helper\WebAuthn\Data\Credential;
use Dotclear\Helper\WebAuthn\Data\Provider;
use Dotclear\Helper\WebAuthn\Data\Store;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Helper\WebAuthn\Util\CborDecoder;
use Dotclear\Helper\WebAuthn\Util\DerEncoder;

/**
 * @brief   WebAuthn container.
 *
 * This provides all class as methods used by the webauthn server.
 * All these class can be overloaded through container and can be called from it.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class WebAuthnContainer extends Container
{
    /**
     * WebAuthn container ID.
     *
     * @var     string  CONTAINER_ID
     */
    public const CONTAINER_ID = 'webauthn';

    /**
     * Constructor gets container services.
     */
    public function __construct()
    {
        parent::__construct(Factories::getFactory(static::CONTAINER_ID));
    }

    /**
     * Make factory public
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }

    /**
     * Get default services definitions.
     *
     * This adds default class to the App.
     *
     * @return  array<string,callable>  The default services
     */
    protected function getDefaultServices(): array
    {
        return [    // @phpstan-ignore-line
            StoreInterface::class                        => Store::class,
            CertificatesInterface::class                 => Certificates::class,
            ClientInterface::class                       => Client::class,
            CredentialInterface::class                   => Credential::class,
            PublicKeyInterface::class                    => PublicKey::class,
            AttestationInterface::class                  => Attestation::class,
            AuthenticatorInterface::class                => Authenticator::class,
            AttestedCredentialInterface::class           => AttestedCredential::class,
            CredentialPublicKeyInterface::class          => CredentialPublicKey::class,
            AttestationOptionInterface::class            => AttestationOption::class,
            AttestationFormatsOptionInterface::class     => AttestationFormatsOption::class,
            AuthenticatorSelectionOptionInterface::class => AuthenticatorSelectionOption::class,
            ChallengeOptionInterface::class              => ChallengeOption::class,
            ExcludeCredentialsOptionInterface::class     => ExcludeCredentialsOption::class,
            ExtensionsOptionInterface::class             => ExtensionsOption::class,
            PubKeyCredParamsOptionInterface::class       => PubKeyCredParamsOption::class,
            RpOptionInterface::class                     => RpOption::class,
            TimeoutOptionInterface::class                => TimeoutOption::class,
            //TransportsOptionInterface::class             => TransportsOption::class,
            UserOptionInterface::class             => UserOption::class,
            AllowCredentialsOptionInterface::class => AllowCredentialsOption::class,
            ProviderInterface::class               => Provider::class,

            // Utils, only available from self::get()
            ByteBufferInterface::class  => ByteBuffer::class,
            CborDecoderInterface::class => CborDecoder::class,
            DerEncoderInterface::class  => DerEncoder::class,

            // Attestion formats, only available from self::get()
            FormatAndroidKeyInterface::class       => AndroidKey::class,
            FormatAndroidSafetyNetInterface::class => AndroidSafetyNet::class,
            FormatAppleInterface::class            => Apple::class,
            FormatNoneInterface::class             => None::class,
            FormatPackedInterface::class           => Packed::class,
            FormatTpmInterface::class              => Tpm::class,
            FormatU2fInterface::class              => U2f::class,
        ];
    }

    /**
     * Data store instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Data\Store
     */
    public function store(): StoreInterface
    {
        return $this->get(StoreInterface::class);
    }

    /**
     * Data cetificates instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\data\CertificatesInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Data\Certificates
     */
    public function certificates(): CertificatesInterface
    {
        return $this->get(CertificatesInterface::class);
    }

    /**
     * Data client instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\data\ClientInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Data\Client
     */
    public function client(): ClientInterface
    {
        return $this->get(ClientInterface::class);
    }

    /**
     * Data credential instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\data\CredentialInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Data\Credential
     */
    public function credential(): CredentialInterface
    {
        return $this->get(CredentialInterface::class);
    }

    /**
     * Data passkey providers instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Data\ProviderInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Data\Provider
     */
    public function provider(): ProviderInterface
    {
        return $this->get(ProviderInterface::class);
    }

    /**
     * Credentials public key instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Credentials\PublicKeyInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Credentials\PublicKey
     */
    public function publicKey(): PublicKeyInterface
    {
        return $this->get(PublicKeyInterface::class);
    }

    /**
     * Response attestation instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\AttestationInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Attestation
     */
    public function attestation(): AttestationInterface
    {
        return $this->get(AttestationInterface::class);
    }

    /**
     * Response authenticator instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Authenticator
     */
    public function authenticator(): AuthenticatorInterface
    {
        return $this->get(AuthenticatorInterface::class);
    }

    /**
     * Response attested credential instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\AttestedCredentialInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\AttestedCredential
     */
    public function attestedCredential(): AttestedCredentialInterface
    {
        return $this->get(AttestedCredentialInterface::class);
    }

    /**
     * Response credential public key instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\CredentialPublicKeyInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\CredentialPublicKey
     */
    public function credentialPublicKey(): CredentialPublicKeyInterface
    {
        return $this->get(CredentialPublicKeyInterface::class);
    }

    /**
     * Util byte buffer instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\ByteBufferInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\ByteBuffer
     */
    public function byteBuffer(): ByteBufferInterface
    {
        return $this->get(ByteBufferInterface::class);
    }

    /**
     * Attestation credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\AttestationOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\AttestationOption
     */
    public function attestationOption(): AttestationOptionInterface
    {
        return $this->get(AttestationOptionInterface::class);
    }

    /**
     * Attestation formats credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\AttestationFormatsOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\AttestationFormatsOption
     */
    public function attestationFormatsOption(): AttestationFormatsOptionInterface
    {
        return $this->get(AttestationFormatsOptionInterface::class);
    }

    /**
     * Authenticator selection credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\AuthenticatorSelectionOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\AuthenticatorSelectionOption
     */
    public function authenticatorSelectionOption(): AuthenticatorSelectionOptionInterface
    {
        return $this->get(AuthenticatorSelectionOptionInterface::class);
    }

    /**
     * Challenge credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\ChallengeOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\ChallengeOption
     */
    public function challengeOption(): ChallengeOptionInterface
    {
        return $this->get(ChallengeOptionInterface::class);
    }

    /**
     * Extension credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\ExtensionsOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\ExtensionsOption
     */
    public function extensionsOption(): ExtensionsOptionInterface
    {
        return $this->get(ExtensionsOptionInterface::class);
    }

    /**
     * Excluded credentials credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\ExcludeCredentialsOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\ExcludeCredentials
     */
    public function excludeCredentialsOption(): ExcludeCredentialsOptionInterface
    {
        return $this->get(ExcludeCredentialsOptionInterface::class);
    }

    /**
     * Public key credential params credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\PubKeyCredParamsOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\PubKeyCredParamsOption
     */
    public function pubKeyCredParamsOption(): PubKeyCredParamsOptionInterface
    {
        return $this->get(PubKeyCredParamsOptionInterface::class);
    }

    /**
     * Relying party credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\RpOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\RpOption
     */
    public function rpOption(): RpOptionInterface
    {
        return $this->get(RpOptionInterface::class);
    }

    /**
     * Timeout credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\TimeoutOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\TimeoutOption
     */
    public function timeoutOption(): TimeoutOptionInterface
    {
        return $this->get(TimeoutOptionInterface::class);
    }

    /**
     * Transports credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\TransportsOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\TransportsOption
     */
    /*
        public function transportsOption(): TransportsOptionInterface
        {
            return $this->get(TransportsOptionInterface::class);
        }
    //*/

    /**
     * User credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\UserOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\UserOption
     */
    public function userOption(): UserOptionInterface
    {
        return $this->get(UserOptionInterface::class);
    }

    /**
     * Allowed credentials credential option instance.
     *
     * @see     Calls webauthn container service Dotclear\Interface\Helper\WebAuthn\Attestation\Option\AllowCredentialsOptionInterface
     * @see     Uses default webauthn service Dotclear\Helper\WebAuthn\Attestation\Option\AllowCredentialsOption
     */
    public function allowCredentialsOption(): AllowCredentialsOptionInterface
    {
        return $this->get(AllowCredentialsOptionInterface::class);
    }
}
