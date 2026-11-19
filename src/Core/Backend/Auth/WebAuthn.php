<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Auth;

use Dotclear\App;
use Dotclear\Helper\Container\Factories;
use Dotclear\Helper\WebAuthn\WebAuthn as Wan;
use Dotclear\Helper\WebAuthn\Type\AttestationEnum;
use Dotclear\Helper\WebAuthn\Type\ResidentKeyEnum;
use Dotclear\Helper\WebAuthn\Type\UserVerificationEnum;
use Dotclear\Helper\WebAuthn\Type\AuthenticatorAttachmentEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use stdClass;

/**
 * @brief   Dotclear backend WebAuthn class.
 *
 * This class set up backend webauthn options.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class WebAuthn extends Wan
{
    /**
     * Credentials request timeout in seconds.
     */
    public const TIMEOUT = 180;

    /**
     * Create backend WebAuthn instance.
     */
    public function __construct()
    {
        // Replace WebAuthn Store interface
        Factories::addService('webauthn', StoreInterface::class, WebAuthnStore::class);

        parent::__construct();

        // We don't use certificates for now.
        //$this->certificates()->addCertificates(dirname(App::config()->configPath()) . DIRECTORY_SEPARATOR . 'webauthncertificates');

        // Prepare backend values
        $this->store()->getRelyingParty();
        $this->store()->getUser();
    }

    /**
     * Prepare passkey registration, step 1 of registration flow.
     *
     * @return  stdClass    The navigator.credentials.create options instance
     */
    public function prepareCreate(): stdClass
    {
        // We use webauthn to login user, so we need the following parameters
        $this->authenticatorSelectionOption()->configure([
            'resident_key'             => ResidentKeyEnum::REQUIRED, // required resident key as it is used to auth user
            'user_verification'        => UserVerificationEnum::PREFERRED, // preferred user verification
            'authenticator_attachment' => AuthenticatorAttachmentEnum::ANY, // allow usb key and platform passkeys (Windows Hello, Touch ID, Android, password managers)
        ]);

        // For now we don't use certificats
        $this->attestationOption()->configure([
            'attestation' => AttestationEnum::INDIRECT,
        ]);

        // Let enough time to user to enter its PIN or to use another device (QR code)
        $this->timeoutOption()->configure([
            'timeout' => self::TIMEOUT,
        ]);

        return $this->prepareCredentialsOptions(CredentialMethodEnum::CREATE);
    }

    /**
     * Process passkey registration, step 2 of registration flow.
     */
    public function processCreate(string $client, string $attestation, string $transports, string $label = ''): bool
    {
        // same as above
        $this->authenticatorSelectionOption()->configure([
            'resident_key'      => ResidentKeyEnum::REQUIRED, // required resident key as it is used to auth user
            'user_verification' => UserVerificationEnum::PREFERRED, // preferred user verification
        ]);

        return parent::processCreate($client, $attestation, $transports, $label);
    }

    /**
     * Prepare passkey authentication, step 1 of authentication flow.
     *
     * @return  stdClass   The authentication arguments sent to browser
     */
    public function prepareGet(): stdClass
    {
        // We use webauthn to login user, so we need the following parameters
        $this->authenticatorSelectionOption()->configure([
            'resident_key'      => ResidentKeyEnum::REQUIRED, // required resident key as it is used to auth user
            'user_verification' => UserVerificationEnum::PREFERRED,
        ]);

        // Let enough time to user to enter its PIN or to use another device (QR code)
        $this->timeoutOption()->configure([
            'timeout' => self::TIMEOUT,
        ]);

        return parent::prepareCredentialsOptions(CredentialMethodEnum::GET);
    }

    /**
     * Process passkey authentication, step 2 of authentication flow.
     */
    public function processGet(string $id, string $client, string $authenticator, string $signature, string $user): string
    {
        // same as above
        $this->authenticatorSelectionOption()->configure([
            'resident_key'      => ResidentKeyEnum::REQUIRED, // required resident key as it is used to auth user
            'user_verification' => UserVerificationEnum::PREFERRED,
        ]);

        $user_id = parent::processGet($id, $client, $authenticator, $signature, $user);

        // Store new signature counter of the authenticated credential
        $credential = $this->getAuthenticatedCredential();
        if ($user_id !== '' && $credential instanceof CredentialInterface && $this->store() instanceof WebAuthnStore) {
            $this->store()->updateCredential($user_id, $credential);
        }

        return $user_id;
    }

    /**
     * Prepare passkey verification of the current user, step 1 of verification flow.
     *
     * This is used instead of the user password to confirm sensitive actions.
     * Only the given user's passkeys are allowed and user verification (PIN, biometrics) is required.
     *
     * @since   2.41
     *
     * @param   string  $user_id    The user ID
     *
     * @return  stdClass   The authentication arguments sent to browser
     */
    public function prepareVerify(string $user_id): stdClass
    {
        $this->authenticatorSelectionOption()->configure([
            'resident_key'      => ResidentKeyEnum::REQUIRED,
            'user_verification' => UserVerificationEnum::REQUIRED, // a passkey replaces a password here, so user must be verified
        ]);

        // Only allow passkeys of this user
        $this->allowCredentialsOption()->configure([
            'user_id' => $user_id,
        ]);

        // Let enough time to user to enter its PIN or to use another device (QR code)
        $this->timeoutOption()->configure([
            'timeout' => self::TIMEOUT,
        ]);

        return parent::prepareCredentialsOptions(CredentialMethodEnum::GET);
    }

    /**
     * Process passkey verification of the current user, step 2 of verification flow.
     *
     * @since   2.41
     *
     * @param   string  $user_id    The user ID that must own the passkey
     *
     * @return  bool    True if passkey belongs to the user and is valid
     */
    public function processVerify(string $user_id, string $id, string $client, string $authenticator, string $signature, string $user): bool
    {
        // same as above
        $this->authenticatorSelectionOption()->configure([
            'resident_key'      => ResidentKeyEnum::REQUIRED,
            'user_verification' => UserVerificationEnum::REQUIRED,
        ]);

        // Always process passkey response, as it consumes the challenge
        $found = parent::processGet($id, $client, $authenticator, $signature, $user);

        // The passkey must belong to the given user
        if ($user_id === '' || $found !== $user_id) {
            return false;
        }

        // Store new signature counter of the authenticated credential
        $credential = $this->getAuthenticatedCredential();
        if ($credential instanceof CredentialInterface && $this->store() instanceof WebAuthnStore) {
            $this->store()->updateCredential($user_id, $credential);
        }

        return true;
    }
}
