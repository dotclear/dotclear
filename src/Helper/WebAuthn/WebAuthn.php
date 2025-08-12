<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Attestation\Format\AndroidSafetyNet;
use Dotclear\Helper\WebAuthn\Exception\AttestationException;
use Dotclear\Helper\WebAuthn\Exception\AuthenticatorException;
use Dotclear\Helper\WebAuthn\Exception\ClientException;
use Dotclear\Helper\WebAuthn\Exception\RequirementException;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\OptionInterface;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use stdClass;

/**
 * @brief   WebAuthn main class.
 *
 * This group of webauthn class is inspired from work by 
 * Lukas Buchs https://github.com/lbuchs/WebAuthn under MIT license
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class WebAuthn extends WebAuthnContainer
{
    /**
     * Required check for Compatibility Testing Suite (CTS) for Android-SafetyNet.
     *
     * (google approuved key)
     *
     * @var     bool    $require_cts_profile_match
     */
    public bool $require_cts_profile_match = true;

    /**
     * Initialize a new WebAuthn server.
     */
    public function __construct() {
        // Load container
        parent::__construct();

        // Check WebAuthn requirements
        static::hasRequirements(true);
    }

    /**
     * Check class requirements.
     *
     * @return  bool    False if there are missing requirements
     */
    public static function hasRequirements(bool $throw = false): bool
    {
        $check = true;

        // Hash SHA256 not supported
        if (!function_exists('\hash') || !in_array('sha256', hash_algos())) {
            $check = false;
        }

        // OpenSSL-Module not installed
        if (!function_exists('\openssl_open')) {
            $check = false;
        }

        // OpenSSL SHA256 not supported
        if (!in_array('SHA256', array_map('\strtoupper', openssl_get_md_methods()))) {
            $check = false;
        }

        if (!$check && $throw) {
            throw new RequirementException();
        }

        return $check;
    }

    /**
     * Prepare credentials arguments instance.
     *
     * Generates the object for a key registration/authentification.
     * This response must be transmitted to browser navigator.credentials.create or .get
     *
     * @param   CredentialMethodEnum    $method     The credentials method
     *
     * @return  stdClass    The credentials request arguments instance
     */
    public function prepareCredentialsOptions(CredentialMethodEnum $method): stdClass
    {
        $arguments = new stdClass();

        // Load all services that inherit OptionInterface
        foreach($this->factory->dump() as $interface => $service) {
            if (is_subclass_of($service, OptionInterface::class)) { // @phpstan-ignore-line
                $this->publicKey()->addOption($this->get($interface));
            }
        }

        // Add services credentials arguments to current credentials instance
        $this->publicKey()->parseCredentialTypeOptions($method, $arguments);

        // Return ready to use navigator.credentials.xxx() arguments
        return $arguments;
    }

    /**
     * Process a create request.
     *
     * @see     https://www.w3.org/TR/webauthn/#sctn-registering-a-new-credential
     *
     * @param   string  $client         Binary client data from browser
     * @param   string  $attestation    Binary attestation data from browser
     * @param   string  $transports     Binary transports data from browser
     *
     * @return  bool    True on success
     */
    public function processCreate(string $client, string $attestation, string $transports): bool
    {
        $this->client()->fromResponse($client);

        if (!$this->client()->checkObject()) {
            throw new ClientException('invalid client data');
        }

        if (!$this->client()->checkCreate()) {
            throw new ClientException('invalid type');
        }

        if (!$this->client()->checkChallenge()) {
            throw new ClientException('invalid challenge');
        }

        if (!$this->client()->checkOrigin()) {
            throw new ClientException('invalid origin');
        }

        $this->attestation()->fromResponse($attestation, array_map(fn ($case): string => $case->value, $this->attestationFormatsOption()->formats()));

        if (!$this->attestation()->validateRpIdHash($this->rpOption()->hash())) {
            throw new AttestationException('invalid rpId hash');
        }

        if (!$this->attestation()->validateAttestation($this->client()->getHash())) {
            throw new AttestationException('invalid certificate signature');
        }

        // Android-SafetyNet: if required, check for Compatibility Testing Suite (CTS).
        if ($this->require_cts_profile_match && $this->attestation()->getAttestationFormat() instanceof AndroidSafetyNet) {
            if (!$this->attestation()->getAttestationFormat()->ctsProfileMatch()) {
                 throw new AttestationException('invalid ctsProfileMatch: device is not approved as a Google-certified Android device.');
            }
        }

        // If validation is successful, obtain a list of acceptable trust anchors
        if ($this->certificates()->checkRequested()) {
            if ($this->attestation()->validateRootCertificate($this->certificates()->getCertificates()) === false) {
                throw new AttestationException('invalid root certificate');
            }
        }

        // 14. Verify that the User Present bit of the flags in authData is set.
        if ($this->authenticatorSelectionOption()->requireUserPresent() && !$this->attestation()->getAuthenticator()->isUserPresent()) {
            throw new AuthenticatorException('user not present during authentication');
        }

        // 15. If user verification is required for this registration, verify that the User Verified bit of the flags in authData is set.
        if ($this->authenticatorSelectionOption()->requireUserVerification() && !$this->attestation()->getAuthenticator()->isUserVerified()) {
            throw new AuthenticatorException('user not verified during authentication');
        }

        // Store data for futur login
        $this->credential()->fromAttestation($this->attestation());
        $this->store()->setCredential($this->credential());

        return true;
    }

    /**
     * Process a get request.
     *
     * @see     https://www.w3.org/TR/webauthn/#sctn-verifying-assertion
     *
     * @param   string  $id             The credential raw ID binary from browser
     * @param   string  $client         The clientDataJSON binary from browser
     * @param   string  $authenticator  The authenticatorData binary from browser
     * @param   string  $signature      The signature binary from browser
     * @param   string  $user           The userHandle from browser
     *
     * @return  string  The user ID or an empty string on fail
     */
    public function processGet(string $id, string $client, string $authenticator, string $signature, string $user): string
    {
        $this->client()->fromResponse($client);
        $this->authenticator()->fromBinary($authenticator);

        if (!$this->client()->checkObject()) {
            throw new ClientException('invalid client data');
        }

        if (!$this->client()->checkGet()) {
            throw new ClientException('invalid type');
        }

        if (!$this->client()->checkChallenge()) {
            throw new ClientException('invalid challenge');
        }

        if (!$this->client()->checkOrigin()) {
            throw new ClientException('invalid origin');
        }

        // 11. Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
        if ($this->authenticator()->getRpIdHash() !== $this->rpOption()->hash()) {
            throw new AuthenticatorException('invalid rpId hash');
        }

        // 12. Verify that the User Present bit of the flags in authData is set
        if ($this->authenticatorSelectionOption()->requireUserPresent() && !$this->authenticator()->isUserPresent()) {
            throw new AuthenticatorException('user not present during authentication');
        }

        // 13. If user verification is required for this assertion, verify that the User Verified bit of the flags in authData is set.
        if ($this->authenticatorSelectionOption()->requireUserVerification() && !$this->authenticator()->isUserVerified()) {
            throw new AuthenticatorException('user not verified during authentication');
        }

        // 14. Verify the values of the client extension outputs
        //     (extensions not implemented)

        // 1. If the allowCredentials option was given when this authentication ceremony was initiated, verify that credential.id identifies one of the public key credentials that were listed in allowCredentials.
        // 2. If credential.response.userHandle is present, verify that the user identified by this value is the owner of the public key credential identified by credential.id.
        $credentials = $this->store()->getCredentials($id, $user ?: null);
        $user_id     = '';

        foreach ($credentials as $credential) {
            // 3. Using credential’s id attribute (or the corresponding rawId, if base64url encoding is inappropriate for your use case), look up the corresponding credential public key.

            // 16. Using the credential public key looked up in step 3, verify that sig is a valid signature over the binary concatenation of authData and hash.
            if (!$this->verifySignature($this->authenticator()->getBinary(), $this->client()->getHash(), $signature, $credential->credentialPublicKey())) {
                throw new AuthenticatorException('invalid signature');
            }

            // 17. If either of the signature counter value authData.signCount or previous signature count is nonzero, and if authData.signCount less than or equal to previous signature count, it's a signal that the authenticator may be cloned
            if ($credential->signatureCounter() !== 0 || $this->authenticator()->getSignCount() !== 0) {
                if ($credential->signatureCounter() >= $this->authenticator()->getSignCount()) {
                    throw new AuthenticatorException('invalid signature counter');
                }
            }

            $user_id = $user;
        }

        return $user_id;
    }

    /**
     * Check if the signature is valid.
     *
     * @param   string  $authenticator  The authenticatorData binary from browser
     * @param   string  $hash           The hash of clientDataJSON binary from browser
     * @param   string  $signature      The signature binary from browser
     * @param   string  $public_key     The (PEM format) credential public key 
     *
     * @return  bool    True on success
     */
    private function verifySignature(string $authenticator, string $hash, string $signature, string $public_key): bool
    {
        $data = $authenticator . $hash;

        // Use Sodium to verify EdDSA 25519 as its not yet supported by openssl
        if (function_exists('sodium_crypto_sign_verify_detached') && !in_array('ed25519', openssl_get_curve_names() ?: [], true)) {
            $parts = [];
            if (preg_match('/BEGIN PUBLIC KEY\-+(?:\s|\n|\r)+([^\-]+)(?:\s|\n|\r)*\-+END PUBLIC KEY/i', $public_key, $parts)) {
                $raw = base64_decode($parts[1]);

                // 30        = der sequence
                // 2a        = length 42 byte
                // 30        = der sequence
                // 05        = lenght 5 byte
                // 06        = der OID
                // 03        = OID length 3 byte
                // 2b 65 70  = OID 1.3.101.112 curveEd25519 (EdDSA 25519 signature algorithm)
                // 03        = der bit string
                // 21        = length 33 byte
                // 00        = null padding
                // [...]     = 32 byte x-curve
                $prefix = "\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00";

                if ($raw && strlen($raw) === 44 && substr($raw, 0, strlen($prefix)) === $prefix) {
                    $xcurve = substr($raw, strlen($prefix));

                    return sodium_crypto_sign_verify_detached($signature ?: '_', $data, $xcurve ?: '_');
                }
            }
        }

        // verify with openSSL
        $key = openssl_pkey_get_public($public_key);
        if ($key === false) {
            throw new AuthenticatorException('invalid public key');
        }

        return openssl_verify($data, $signature, $key, OPENSSL_ALGO_SHA256) === 1;
    }
}
