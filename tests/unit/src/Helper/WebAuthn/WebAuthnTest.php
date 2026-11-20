<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\Container\Factories;
use Dotclear\Helper\WebAuthn\Credentials\PublicKey;
use Dotclear\Helper\WebAuthn\Credentials\Option\RpOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\UserOption;
use Dotclear\Helper\WebAuthn\Data\Credential;
use Dotclear\Helper\WebAuthn\Data\Store;
use Dotclear\Helper\WebAuthn\Exception\ClientException;
use Dotclear\Helper\WebAuthn\Exception\CredentialsException;
use Dotclear\Helper\WebAuthn\Exception\AuthenticatorException;
use Dotclear\Helper\WebAuthn\Exception\AttestationException;
use Dotclear\Helper\WebAuthn\Credentials\Option\AuthenticatorSelectionOption;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Helper\WebAuthn\Type\ResidentKeyEnum;
use Dotclear\Helper\WebAuthn\Type\UserVerificationEnum;
use Dotclear\Helper\WebAuthn\WebAuthn;
use Dotclear\Helper\WebAuthn\WebAuthnContainer;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\OptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AuthenticatorSelectionOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatTpmInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[RunTestsInSeparateProcesses]
class WebAuthnTest extends TestCase
{
    private function registerStore(): void
    {
        Factories::addService(WebAuthnContainer::CONTAINER_ID, StoreInterface::class, TestWebAuthnStore::class);
    }

    private function configuredWebAuthn(): WebAuthn
    {
        $this->registerStore();
        Factories::addService(WebAuthnContainer::CONTAINER_ID, RpOptionInterface::class, fn () => (new RpOption())->configure(['id' => 'example.test', 'name' => 'Example']));
        Factories::addService(WebAuthnContainer::CONTAINER_ID, UserOptionInterface::class, fn () => (new UserOption(new ByteBuffer()))->configure(['id' => 'user', 'name' => 'user', 'displayname' => 'User']));

        return new WebAuthn();
    }

    private function clientData(string $type): string
    {
        return json_encode([
            'type'      => $type,
            'challenge' => rtrim(strtr(base64_encode('nonce'), '+/', '-_'), '='),
            'origin'    => 'https://example.test',
        ], JSON_THROW_ON_ERROR);
    }

    private function noneAttestation(string $rpId = 'example.test', int $flags = 0x41): string
    {
        $x = str_repeat("\x11", 32);
        $y = str_repeat("\x22", 32);
        $cose = "\xa5\x01\x02\x03\x26\x20\x01\x21\x58\x20" . $x . "\x22\x58\x20" . $y;
        $authData = hash('sha256', $rpId, true) . chr($flags) . pack('N', 0)
            . str_repeat("\0", 16) . pack('n', 1) . 'c' . $cose;

        return "\xa3\x63fmt\x64none\x67attStmt\xa0\x68authData"
            . "\x58" . chr(strlen($authData)) . $authData;
    }

    private function configureSelection(ResidentKeyEnum $residentKey, UserVerificationEnum $verification): void
    {
        Factories::addService(
            WebAuthnContainer::CONTAINER_ID,
            AuthenticatorSelectionOptionInterface::class,
            fn () => (new AuthenticatorSelectionOption())->configure([
                'resident_key' => $residentKey,
                'user_verification' => $verification,
            ])
        );
    }

    public function testContainerBuildsDefaultServices(): void
    {
        $this->registerStore();
        $container = new WebAuthnContainer();

        $this->assertSame(WebAuthnContainer::CONTAINER_ID, $container->getFactory()->id);
        $this->assertNotEmpty($container->getDefaultServices());
        $this->assertSame($container->store(), $container->store());
        $this->assertInstanceOf(\Dotclear\Helper\WebAuthn\Attestation\Format\Tpm::class, $container->get(FormatTpmInterface::class));

        foreach ([
            'certificates', 'client', 'credential', 'provider', 'publicKey', 'attestation',
            'authenticator', 'attestedCredential', 'credentialPublicKey', 'byteBuffer',
            'attestationOption', 'attestationFormatsOption', 'authenticatorSelectionOption',
            'challengeOption', 'extensionsOption', 'excludeCredentialsOption',
            'pubKeyCredParamsOption', 'rpOption', 'timeoutOption', 'userOption',
            'allowCredentialsOption',
        ] as $service) {
            $this->assertIsObject($container->$service());
        }
    }

    public function testPrepareOptionsAndRejectInvalidRequests(): void
    {
        $this->registerStore();
        $webauthn = new WebAuthn();
        $this->assertTrue(WebAuthn::hasRequirements());

        $create = $webauthn->prepareCredentialsOptions(CredentialMethodEnum::CREATE);
        $this->assertObjectHasProperty('publicKey', $create);
        $this->assertObjectHasProperty('rp', $create->publicKey);
        $this->assertObjectHasProperty('challenge', $create->publicKey);
        $this->assertObjectHasProperty('user', $create->publicKey);

        $get = $webauthn->prepareCredentialsOptions(CredentialMethodEnum::GET);
        $this->assertObjectHasProperty('publicKey', $get);
        $this->assertObjectHasProperty('challenge', $get->publicKey);

        try {
            $webauthn->processCreate('not-json', '', '', 'label');
            $this->fail('Invalid client JSON must be rejected');
        } catch (ClientException $exception) {
            $this->assertSame('invalid client data', $exception->getMessage());
        }

        $this->expectException(AuthenticatorException::class);
        $webauthn->processGet('', '{}', '', '', '');
    }

    public function testCreateRejectsIncorrectTypeChallengeAndOrigin(): void
    {
        $webauthn = $this->configuredWebAuthn();
        $cases = [
            [$this->clientData('webauthn.get'), 'invalid type'],
            [json_encode(['type' => 'webauthn.create', 'challenge' => 'd3Jvbmc', 'origin' => 'https://example.test'], JSON_THROW_ON_ERROR), 'invalid challenge'],
            [json_encode(['type' => 'webauthn.create', 'challenge' => 'bm9uY2U', 'origin' => 'http://example.test'], JSON_THROW_ON_ERROR), 'invalid origin'],
        ];

        foreach ($cases as [$client, $message]) {
            $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
            try {
                $webauthn->processCreate($client, '', '', '');
                $this->fail('Invalid create request must be rejected');
            } catch (ClientException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
    }

    public function testCreateRejectsInvalidRpIdHashAndMissingUserPresence(): void
    {
        $webauthn = $this->configuredWebAuthn();
        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        try {
            $webauthn->processCreate($this->clientData('webauthn.create'), $this->noneAttestation('other.test'), '', '');
            $this->fail('Incorrect RP ID hash must be rejected');
        } catch (AttestationException $exception) {
            $this->assertSame('invalid rpId hash', $exception->getMessage());
        }

        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        try {
            $webauthn->processCreate($this->clientData('webauthn.create'), $this->noneAttestation('example.test', 0x40), '', '');
            $this->fail('Missing user presence must be rejected');
        } catch (AuthenticatorException $exception) {
            $this->assertSame('user not present during authentication', $exception->getMessage());
        }
    }

    public function testCreateRequiresUserVerificationWhenConfigured(): void
    {
        $this->configureSelection(ResidentKeyEnum::PREFERRED, UserVerificationEnum::REQUIRED);
        $webauthn = $this->configuredWebAuthn();
        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));

        $this->expectException(AuthenticatorException::class);
        $this->expectExceptionMessage('user not verified during authentication');
        $webauthn->processCreate($this->clientData('webauthn.create'), $this->noneAttestation(), '', '');
    }

    public function testGetRejectsClientAndAuthenticatorValidationFailures(): void
    {
        $webauthn = $this->configuredWebAuthn();
        $authenticatorData = hash('sha256', 'example.test', true) . "\x01" . pack('N', 1);
        $cases = [
            ['not-json', 'invalid client data'],
            [$this->clientData('webauthn.create'), 'invalid type'],
            [json_encode(['type' => 'webauthn.get', 'challenge' => 'd3Jvbmc', 'origin' => 'https://example.test'], JSON_THROW_ON_ERROR), 'invalid challenge'],
            [json_encode(['type' => 'webauthn.get', 'challenge' => 'bm9uY2U', 'origin' => 'http://example.test'], JSON_THROW_ON_ERROR), 'invalid origin'],
        ];

        foreach ($cases as [$client, $message]) {
            $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
            try {
                $webauthn->processGet('', $client, $authenticatorData, '', '');
                $this->fail('Invalid get request must be rejected');
            } catch (ClientException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        try {
            $webauthn->processGet('', $this->clientData('webauthn.get'), str_repeat("\0", 32) . "\x01" . pack('N', 1), '', '');
            $this->fail('Incorrect RP ID hash must be rejected');
        } catch (AuthenticatorException $exception) {
            $this->assertSame('invalid rpId hash', $exception->getMessage());
        }
    }

    public function testGetRequiresUserVerificationWhenConfigured(): void
    {
        $this->configureSelection(ResidentKeyEnum::DISCOURAGED, UserVerificationEnum::REQUIRED);
        $webauthn = $this->configuredWebAuthn();
        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        $authenticatorData = hash('sha256', 'example.test', true) . "\x01" . pack('N', 1);

        $this->expectException(AuthenticatorException::class);
        $this->expectExceptionMessage('user not verified during authentication');
        $webauthn->processGet('', $this->clientData('webauthn.get'), $authenticatorData, '', '');
    }

    public function testGetReturnsEmptyWhenNoCredentialMatches(): void
    {
        $webauthn = $this->configuredWebAuthn();
        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        TestWebAuthnStore::$credentials = [];
        $authenticatorData = hash('sha256', 'example.test', true) . "\x01" . pack('N', 0);

        $this->assertSame('', $webauthn->processGet('', $this->clientData('webauthn.get'), $authenticatorData, '', ''));
        $this->assertNull($webauthn->getAuthenticatedCredential());
    }

    public function testGetRejectsInvalidStoredPublicKey(): void
    {
        $webauthn = $this->configuredWebAuthn();
        TestWebAuthnStore::$credentials = [(new Credential($webauthn->rpOption()))->newFromArray([
            'credentialId' => 'credential',
            'credentialPublicKey' => 'not-a-public-key',
            'signatureCounter' => 0,
        ])];
        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        $authenticatorData = hash('sha256', 'example.test', true) . "\x01" . pack('N', 1);

        $this->expectException(AuthenticatorException::class);
        $this->expectExceptionMessage('invalid public key');
        $webauthn->processGet('credential', $this->clientData('webauthn.get'), $authenticatorData, 'signature', 'user');
    }

    public function testPublicKeyOptionConfigurationFailureIsWrapped(): void
    {
        $option = $this->createMock(OptionInterface::class);
        $option->method('isConfigured')->willReturn(false);
        $option->expects($this->once())->method('configure')->willThrowException(new RuntimeException('bad option'));

        $this->expectException(CredentialsException::class);
        (new PublicKey())->addOption($option);
    }

    public function testCreateRegistrationWithLocallyEncodedNoneAttestation(): void
    {
        $webauthn = $this->configuredWebAuthn();
        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));

        $x = str_repeat("\x11", 32);
        $y = str_repeat("\x22", 32);
        $cose = "\xa5\x01\x02\x03\x26\x20\x01\x21\x58\x20" . $x . "\x22\x58\x20" . $y;
        $authData = hash('sha256', 'example.test', true) . "\x41" . pack('N', 0)
            . str_repeat("\0", 16) . pack('n', 1) . 'c' . $cose;
        $attestation = "\xa3\x63fmt\x64none\x67attStmt\xa0\x68authData"
            . "\x58" . chr(strlen($authData)) . $authData;

        $this->assertTrue($webauthn->processCreate($this->clientData('webauthn.create'), $attestation, '', '  Laptop  '));
        $this->assertSame('Laptop', TestWebAuthnStore::$credentials[0]->label());
        $this->assertSame('c', TestWebAuthnStore::$credentials[0]->credentialId());
        $this->assertSame(AttestationFormatsEnum::NONE->value, TestWebAuthnStore::$credentials[0]->attestationFormat());
    }

    public function testGetAssertionVerifiesSignatureAndUpdatesCounter(): void
    {
        $webauthn = $this->configuredWebAuthn();
        $privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->assertNotFalse($privateKey);
        openssl_pkey_export($privateKey, $privatePem);
        $publicPem = openssl_pkey_get_details($privateKey)['key'];
        TestWebAuthnStore::$credentials = [(new Credential($webauthn->rpOption()))->newFromArray([
            'credentialId' => 'credential',
            'credentialPublicKey' => $publicPem,
            'signatureCounter' => 0,
        ])];

        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        $client = $this->clientData('webauthn.get');
        $authData = hash('sha256', 'example.test', true) . "\x01" . pack('N', 1);
        openssl_sign($authData . hash('sha256', $client, true), $signature, $privatePem, OPENSSL_ALGO_SHA256);

        $this->assertSame('user', $webauthn->processGet('credential', $client, $authData, $signature, 'user'));
        $this->assertSame(1, $webauthn->getAuthenticatedCredential()->signatureCounter());
    }

    public function testGetAssertionRejectsBadSignatureAndNonIncreasingCounter(): void
    {
        $webauthn = $this->configuredWebAuthn();
        $privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($privateKey, $privatePem);
        $publicPem = openssl_pkey_get_details($privateKey)['key'];
        $client = $this->clientData('webauthn.get');
        $authData = hash('sha256', 'example.test', true) . "\x01" . pack('N', 1);
        openssl_sign($authData . hash('sha256', $client, true), $signature, $privatePem, OPENSSL_ALGO_SHA256);
        TestWebAuthnStore::$credentials = [(new Credential($webauthn->rpOption()))->newFromArray([
            'credentialId' => 'credential',
            'credentialPublicKey' => $publicPem,
            'signatureCounter' => 1,
        ])];

        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        try {
            $webauthn->processGet('credential', $client, $authData, 'bad-signature', 'user');
            $this->fail('Invalid signatures must fail authentication');
        } catch (AuthenticatorException $exception) {
            $this->assertSame('invalid signature', $exception->getMessage());
        }

        $webauthn->store()->setChallenge(ByteBuffer::fromBinary('nonce'));
        $this->expectException(AuthenticatorException::class);
        $webauthn->processGet('credential', $client, $authData, $signature, 'user');
    }
}

class TestWebAuthnStore extends Store
{
    /** @var Credential[] */
    public static array $credentials = [];

    public function setCredential(\Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface $credential): void
    {
        self::$credentials = [$credential];
    }

    public function getCredentials(string $credential_id = '', string $user_id = ''): array
    {
        return self::$credentials;
    }
}
