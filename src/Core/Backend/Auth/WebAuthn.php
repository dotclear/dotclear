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
            'authenticator_attachment' => AuthenticatorAttachmentEnum::CROSSPLATFORM, // allow usb key
        ]);

        // For now we don't use certificats
        $this->attestationOption()->configure([
            'attestation' => AttestationEnum::INDIRECT,
        ]);
        
        return $this->prepareCredentialsOptions(CredentialMethodEnum::CREATE);
    }

    /**
     * Process passkey registration, step 2 of registration flow.
     */  
    public function processCreate(string $client, string $attestation, string $transports): bool
    {
        // same as above
        $this->authenticatorSelectionOption()->configure([
            'resident_key'      => ResidentKeyEnum::REQUIRED, // required resident key as it is used to auth user
            'user_verification' => UserVerificationEnum::PREFERRED, // preferred user verification
        ]);

        return parent::processCreate($client, $attestation, $transports);
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

        return parent::processGet($id, $client, $authenticator, $signature, $user);
    }
}