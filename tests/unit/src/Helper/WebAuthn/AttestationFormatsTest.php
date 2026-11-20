<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Attestation\Format\AndroidKey;
use Dotclear\Helper\WebAuthn\Attestation\Format\AndroidSafetyNet;
use Dotclear\Helper\WebAuthn\Attestation\Format\Apple;
use Dotclear\Helper\WebAuthn\Attestation\Format\FormatBase;
use Dotclear\Helper\WebAuthn\Attestation\Format\Packed;
use Dotclear\Helper\WebAuthn\Attestation\Format\Tpm;
use Dotclear\Helper\WebAuthn\Attestation\Format\U2f;
use Dotclear\Helper\WebAuthn\Exception\AttestationException;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class AttestationFormatsTest extends TestCase
{
    /**
     * @return array{OpenSSLAsymmetricKey, string, string}
     */
    private function localCertificate(string $commonName): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        $request = openssl_csr_new(['commonName' => $commonName, 'organizationName' => 'Unit tests'], $key, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($request);
        $certificate = openssl_csr_sign($request, null, $key, 1, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($certificate);
        $pem = '';
        $this->assertTrue(openssl_x509_export($certificate, $pem));
        $der = base64_decode(implode('', array_slice(explode("\n", trim($pem)), 1, -1)), true);
        $this->assertIsString($der);

        return [$key, $pem, $der];
    }

    /**
     * @return array{OpenSSLAsymmetricKey, string}
     */
    private function localEcCertificate(string $commonName): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->assertNotFalse($key);
        $request = openssl_csr_new(['commonName' => $commonName, 'organizationName' => 'Unit tests'], $key, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($request);
        $certificate = openssl_csr_sign($request, null, $key, 1, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($certificate);
        $pem = '';
        $this->assertTrue(openssl_x509_export($certificate, $pem));
        $der = base64_decode(implode('', array_slice(explode("\n", trim($pem)), 1, -1)), true);
        $this->assertIsString($der);

        return [$key, $der];
    }

    /**
     * @return array{OpenSSLAsymmetricKey, string}
     */
    private function localAppleCertificate(string $commonName, string $nonce): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->assertNotFalse($key);
        $config = tempnam(sys_get_temp_dir(), 'webauthn-apple-');
        $this->assertNotFalse($config);
        $extension = strtoupper(implode(':', str_split(bin2hex("\x30\x24\xA1\x22\x04\x20" . $nonce), 2)));
        $this->assertNotFalse(file_put_contents($config, "[req]\ndistinguished_name=dn\n[dn]\n[v3_req]\n1.2.840.113635.100.8.2=DER:$extension\n"));

        try {
            $request = openssl_csr_new(['commonName' => $commonName, 'organizationName' => 'Unit tests'], $key, ['config' => $config, 'digest_alg' => 'sha256']);
            $this->assertNotFalse($request);
            $certificate = openssl_csr_sign($request, null, $key, 1, ['config' => $config, 'x509_extensions' => 'v3_req', 'digest_alg' => 'sha256']);
            $this->assertNotFalse($certificate);
            $pem = '';
            $this->assertTrue(openssl_x509_export($certificate, $pem));
            $der = base64_decode(implode('', array_slice(explode("\n", trim($pem)), 1, -1)), true);
            $this->assertIsString($der);

            return [$key, $der];
        } finally {
            unlink($config);
        }
    }

    private function authenticator(): AuthenticatorInterface
    {
        return $this->createStub(AuthenticatorInterface::class);
    }

    public function testFormatBaseHelpersAndAlgorithmLookup(): void
    {
        $format = new FormatBaseTester($this->authenticator());
        $format->initFormat(['attStmt' => []]);
        $this->assertSame('', $format->getCertificateChain());
        $this->assertSame('', $format->getCertificatePem());
        $this->assertFalse($format->validateAttestation(''));
        $this->assertFalse($format->validateRootCertificate([]));
        $this->assertStringContainsString('BEGIN CERTIFICATE', $format->certificate('local'));
        foreach ([-65535 => 'SHA1', -257 => 'SHA256', -258 => 'SHA384', -259 => 'SHA512'] as $alg => $hash) {
            $this->assertSame($hash, $format->algorithm($alg)->hash);
        }
        foreach ([-37, -7, 5] as $alg) {
            $this->assertSame('SHA256', $format->algorithm($alg)->hash);
        }
        foreach ([-38, -35, 6] as $alg) {
            $this->assertSame('SHA384', $format->algorithm($alg)->hash);
        }
        foreach ([-39, -36, 7] as $alg) {
            $this->assertSame('SHA512', $format->algorithm($alg)->hash);
        }
        $this->assertNull($format->algorithm(12345));
    }

    public function testAndroidKeyValidatesInputAndFormatsCertificateChain(): void
    {
        $format = new AndroidKey($this->authenticator());
        $this->expectException(AttestationException::class);
        $format->initFormat(['attStmt' => ['alg' => -9]]);
    }

    public function testAndroidKeyAndAppleExposeCertificateData(): void
    {
        $certificate = ByteBuffer::fromBinary('not-a-certificate');
        $chain       = ByteBuffer::fromBinary('chain');
        $android = new AndroidKey($this->authenticator());
        $android->initFormat(['attStmt' => [
            'alg' => -7,
            'sig' => ByteBuffer::fromBinary('signature'),
            'x5c' => [$certificate, $chain, 'ignored'],
        ]]);
        $this->assertStringContainsString(base64_encode('not-a-certificate'), $android->getCertificatePem());
        $this->assertSame('', $android->getCertificateChain());
        try {
            $android->validateAttestation('hash');
            $this->fail('Invalid local certificate must fail validation');
        } catch (AttestationException $exception) {
            $this->assertStringContainsString('invalid public key', $exception->getMessage());
        }
        $apple = new Apple($this->authenticator());
        $apple->initFormat(['attStmt' => ['x5c' => [$certificate, $chain]]]);
        $this->assertStringContainsString('BEGIN CERTIFICATE', $apple->getCertificatePem());
        try {
            $apple->validateAttestation('hash');
            $this->fail('Invalid local certificate must fail validation');
        } catch (AttestationException $exception) {
            $this->assertStringContainsString('invalid public key', $exception->getMessage());
        }
    }

    public function testAppleValidatesNonceAndCredentialPublicKey(): void
    {
        $authenticatorData = 'authenticator-data';
        $clientHash = hash('sha256', 'client-data', true);
        $nonce = hash('SHA256', $authenticatorData . $clientHash, true);
        [$key, $certificate] = $this->localAppleCertificate('apple.test', $nonce);
        $publicKey = openssl_pkey_get_details($key)['key'];
        $authenticator = $this->createStub(AuthenticatorInterface::class);
        $authenticator->method('getBinary')->willReturn($authenticatorData);
        $authenticator->method('getPublicKeyPem')->willReturn($publicKey);

        $format = new Apple($authenticator);
        $format->initFormat(['attStmt' => ['x5c' => [ByteBuffer::fromBinary($certificate)]]]);
        $this->assertTrue($format->validateAttestation($clientHash));
        $this->assertFalse($format->validateRootCertificate([]));

        [, $otherNonceCertificate] = $this->localAppleCertificate('apple.test', str_repeat("\x00", 32));
        $badNonce = new Apple($authenticator);
        $badNonce->initFormat(['attStmt' => ['x5c' => [ByteBuffer::fromBinary($otherNonceCertificate)]]]);
        try {
            $badNonce->validateAttestation($clientHash);
            $this->fail('Apple nonce mismatch must be rejected');
        } catch (AttestationException $exception) {
            $this->assertSame('nonce doesn\'t equal the value of the extension with OID 1.2.840.113635.100.8.2', $exception->getMessage());
        }

        $otherKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->assertNotFalse($otherKey);
        $mismatchedAuthenticator = $this->createStub(AuthenticatorInterface::class);
        $mismatchedAuthenticator->method('getBinary')->willReturn($authenticatorData);
        $mismatchedAuthenticator->method('getPublicKeyPem')->willReturn(openssl_pkey_get_details($otherKey)['key']);
        $mismatchedKey = new Apple($mismatchedAuthenticator);
        $mismatchedKey->initFormat(['attStmt' => ['x5c' => [ByteBuffer::fromBinary($certificate)]]]);
        $this->expectException(AttestationException::class);
        $this->expectExceptionMessage('credential public key doesn\'t equal the Subject Public Key of credCert');
        $mismatchedKey->validateAttestation($clientHash);
    }

    public function testAndroidKeyAndSafetyNetAcceptLocallySignedResponses(): void
    {
        [$key, , $certificate] = $this->localCertificate('attestation.test');
        $authenticator = $this->createStub(AuthenticatorInterface::class);
        $authenticator->method('getBinary')->willReturn('authenticator-data');
        $clientHash = hash('sha256', 'client-data', true);
        openssl_sign('authenticator-data' . $clientHash, $signature, $key, OPENSSL_ALGO_SHA256);
        $androidKey = new AndroidKey($authenticator);
        $androidKey->initFormat(['attStmt' => [
            'alg' => -257,
            'sig' => ByteBuffer::fromBinary($signature),
            'x5c' => [ByteBuffer::fromBinary($certificate)],
        ]]);
        $this->assertTrue($androidKey->validateAttestation($clientHash));
        $this->assertFalse($androidKey->validateRootCertificate([]));
        $androidKey->initFormat(['attStmt' => [
            'alg' => -257,
            'sig' => ByteBuffer::fromBinary('invalid-signature'),
            'x5c' => [ByteBuffer::fromBinary($certificate)],
        ]]);
        $this->assertFalse($androidKey->validateAttestation($clientHash));

        [$key, , $safetyNetCertificate] = $this->localCertificate('attest.android.com');
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = $encode(json_encode(['alg' => 'RS256', 'x5c' => [base64_encode($safetyNetCertificate)]], JSON_THROW_ON_ERROR));
        $nonce = base64_encode(hash('SHA256', 'authenticator-data' . $clientHash, true));
        $payload = $encode(json_encode(['nonce' => $nonce, 'basicIntegrity' => true, 'ctsProfileMatch' => true], JSON_THROW_ON_ERROR));
        $signed = $header . '.' . $payload;
        openssl_sign($signed, $jwsSignature, $key, OPENSSL_ALGO_SHA256);
        $format = new AndroidSafetyNet($authenticator);
        $format->initFormat(['attStmt' => [
            'ver' => '1.0',
            'response' => ByteBuffer::fromBinary($signed . '.' . $encode($jwsSignature)),
        ]]);
        $this->assertTrue($format->ctsProfileMatch());
        $this->assertTrue($format->validateAttestation($clientHash));
        $this->assertFalse($format->validateRootCertificate([]));
        $format->initFormat(['attStmt' => [
            'ver' => '1.0',
            'response' => ByteBuffer::fromBinary($signed . '.' . $encode('invalid-signature')),
        ]]);
        $this->assertFalse($format->validateAttestation($clientHash));
    }

    public function testSafetyNetRejectsWrongCertificateNameAndIntegrity(): void
    {
        $clientHash = hash('sha256', 'client-data', true);
        $nonce      = base64_encode(hash('SHA256', 'authenticator-data' . $clientHash, true));
        $encode     = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        foreach ([
            ['wrong-cn', ['nonce' => $nonce, 'basicIntegrity' => true], 'invalid certificate CN in JWS'],
            ['attest.android.com', ['nonce' => $nonce, 'basicIntegrity' => false], 'invalid basicIntegrity in payload'],
        ] as [$commonName, $payload, $message]) {
            [, , $certificate] = $this->localCertificate($commonName);
            $header = $encode(json_encode(['alg' => 'RS256', 'x5c' => [base64_encode($certificate)]], JSON_THROW_ON_ERROR));
            $authenticator = $this->createStub(AuthenticatorInterface::class);
            $authenticator->method('getBinary')->willReturn('authenticator-data');
            $format = new AndroidSafetyNet($authenticator);
            $format->initFormat(['attStmt' => [
                'ver' => '1.0',
                'response' => ByteBuffer::fromBinary($header . '.' . $encode(json_encode($payload, JSON_THROW_ON_ERROR)) . '.c2ln'),
            ]]);

            try {
                $format->validateAttestation($clientHash);
                $this->fail('Invalid SafetyNet claims must be rejected');
            } catch (AttestationException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testSafetyNetValidatesCompactJwsStructureLocally(): void
    {
        $format = new AndroidSafetyNet($this->authenticator());
        try {
            $format->initFormat(['attStmt' => ['ver' => '1.0', 'response' => ByteBuffer::fromBinary('bad')]]);
            $this->fail('Malformed JWS must fail');
        } catch (AttestationException $exception) {
            $this->assertSame('invalid JWS data', $exception->getMessage());
        }

        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = $encode(json_encode(['alg' => 'RS256', 'x5c' => [base64_encode('cert'), base64_encode('chain')]], JSON_THROW_ON_ERROR));
        $payload = $encode(json_encode(['ctsProfileMatch' => true], JSON_THROW_ON_ERROR));
        $format->initFormat(['attStmt' => [
            'ver' => '1.0',
            'response' => ByteBuffer::fromBinary($header . '.' . $payload . '.c2ln'),
        ]]);
        $this->assertTrue($format->ctsProfileMatch());
        $this->assertStringContainsString(base64_encode('cert'), $format->getCertificatePem());
        $this->assertSame('', $format->getCertificateChain());
        try {
            $format->validateAttestation('hash');
            $this->fail('SafetyNet payload without a nonce must fail');
        } catch (AttestationException $exception) {
            $this->assertSame('invalid nonce in JWS payload', $exception->getMessage());
        }
    }

    public function testPackedAndTpmValidateRequiredStatementFields(): void
    {
        $packed = new Packed($this->authenticator());
        try {
            $packed->initFormat(['attStmt' => ['alg' => 999, 'sig' => ByteBuffer::fromBinary('sig')]]);
            $this->fail('Unsupported packed algorithms must fail');
        } catch (AttestationException $exception) {
            $this->assertSame('unsupported alg: 999', $exception->getMessage());
        }
        $packed->initFormat(['attStmt' => [
            'alg' => -7, 'sig' => ByteBuffer::fromBinary('sig'),
            'x5c' => [ByteBuffer::fromBinary('cert')],
        ]]);
        $this->assertStringContainsString('BEGIN CERTIFICATE', $packed->getCertificatePem());
        try {
            $packed->validateAttestation('hash');
            $this->fail('Invalid local certificate must fail validation');
        } catch (AttestationException $exception) {
            $this->assertStringContainsString('invalid public key', $exception->getMessage());
        }

        $tpm = new Tpm($this->authenticator(), new ByteBuffer());
        try {
            $tpm->initFormat(['attStmt' => ['ver' => '1.2']]);
            $this->fail('Unexpected TPM version must fail');
        } catch (AttestationException $exception) {
            $this->assertSame('invalid tpm version: 1.2', $exception->getMessage());
        }
        try {
            $tpm->initFormat(['attStmt' => [
                'ver' => '2.0', 'alg' => -7, 'sig' => ByteBuffer::fromBinary('sig'),
                'certInfo' => ByteBuffer::fromBinary('info'), 'pubArea' => ByteBuffer::fromBinary('area'),
            ]]);
            $this->fail('TPM attestation without x5c must fail');
        } catch (AttestationException $exception) {
            $this->assertSame('no x5c certificate found', $exception->getMessage());
        }
    }

    public function testPackedSupportsSelfAttestationAndChecksSignature(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        $publicKey = openssl_pkey_get_details($key)['key'];
        $authenticator = $this->createStub(AuthenticatorInterface::class);
        $authenticator->method('getBinary')->willReturn('authenticator-data');
        $authenticator->method('getPublicKeyPem')->willReturn($publicKey);
        $clientHash = hash('sha256', 'client-data', true);
        openssl_sign('authenticator-data' . $clientHash, $signature, $key, OPENSSL_ALGO_SHA256);

        $format = new Packed($authenticator);
        $format->initFormat(['attStmt' => ['alg' => -257, 'sig' => ByteBuffer::fromBinary($signature)]]);

        $this->assertSame('', $format->getCertificatePem());
        $this->assertTrue($format->validateAttestation($clientHash));
        $this->assertFalse($format->validateAttestation('wrong-hash'));
        $this->assertFalse($format->validateRootCertificate([]));
    }

    public function testPackedValidatesX5cSignatureAndBuildsCertificateChain(): void
    {
        [$key, $rootPem, $certificate] = $this->localCertificate('packed.test');
        $chainKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($chainKey);
        $request = openssl_csr_new(['commonName' => 'packed-chain.test'], $chainKey, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($request);
        $root = openssl_x509_read($rootPem);
        $this->assertNotFalse($root);
        $chainCertificate = openssl_csr_sign($request, $root, $key, 1, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($chainCertificate);
        $chainPem = '';
        $this->assertTrue(openssl_x509_export($chainCertificate, $chainPem));
        $chainDer = base64_decode(implode('', array_slice(explode("\n", trim($chainPem)), 1, -1)), true);
        $this->assertIsString($chainDer);
        $authenticator = $this->createStub(AuthenticatorInterface::class);
        $authenticator->method('getBinary')->willReturn('authenticator-data');
        $clientHash = hash('sha256', 'client-data', true);
        openssl_sign('authenticator-data' . $clientHash, $signature, $key, OPENSSL_ALGO_SHA256);
        $format = new Packed($authenticator);
        $format->initFormat(['attStmt' => [
            'alg' => -257,
            'sig' => ByteBuffer::fromBinary($signature),
            'x5c' => [ByteBuffer::fromBinary($certificate), ByteBuffer::fromBinary($chainDer)],
        ]]);

        $this->assertTrue($format->validateAttestation($clientHash));
        $this->assertFalse($format->validateAttestation('wrong-hash'));
        $this->assertFalse($format->validateRootCertificate([]));
        $this->assertStringContainsString('BEGIN CERTIFICATE', $format->getCertificateChain());
    }

    public function testTpmValidatesCertInfoHashAndSignature(): void
    {
        [$key, , $certificate] = $this->localCertificate('tpm.test');
        $clientHash = hash('sha256', 'client-data', true);
        $attestationData = 'authenticator-data' . $clientHash;
        $extraData = hash('SHA256', $attestationData, true);
        $certInfo = "\xFF\x54\x43\x47\x80\x17\x00\x00" . pack('n', strlen($extraData)) . $extraData;
        openssl_sign($certInfo, $signature, $key, OPENSSL_ALGO_SHA256);
        $statement = [
            'ver' => '2.0',
            'alg' => -257,
            'sig' => ByteBuffer::fromBinary($signature),
            'certInfo' => ByteBuffer::fromBinary($certInfo),
            'pubArea' => ByteBuffer::fromBinary('public-area'),
            'x5c' => [ByteBuffer::fromBinary($certificate)],
        ];
        $authenticator = $this->createStub(AuthenticatorInterface::class);
        $authenticator->method('getBinary')->willReturn('authenticator-data');
        $format = new Tpm($authenticator, new ByteBuffer());
        $format->initFormat(['attStmt' => $statement]);

        $this->assertTrue($format->validateAttestation($clientHash));
        try {
            $format->validateAttestation('wrong-hash');
            $this->fail('TPM attestation hash mismatch must be rejected');
        } catch (AttestationException $exception) {
            $this->assertSame('certInfo:extraData not hash of attToBeSigned', $exception->getMessage());
        }
        $this->assertFalse($format->validateRootCertificate([]));

        foreach ([
            ["BAD!\x80\x17\x00\x00" . pack('n', strlen($extraData)) . $extraData, 'tpm magic not TPM_GENERATED_VALUE'],
            ["\xFF\x54\x43\x47\x00\x00\x00\x00" . pack('n', strlen($extraData)) . $extraData, 'tpm type not TPM_ST_ATTEST_CERTIFY'],
            ["\xFF\x54\x43\x47\x80\x17\x00\x00\x00\x01x", 'certInfo:extraData not hash of attToBeSigned'],
        ] as [$invalidInfo, $message]) {
            $invalidStatement = $statement;
            $invalidStatement['certInfo'] = ByteBuffer::fromBinary($invalidInfo);
            $invalid = new Tpm($authenticator, new ByteBuffer());
            $invalid->initFormat(['attStmt' => $invalidStatement]);
            try {
                $invalid->validateAttestation($clientHash);
                $this->fail('Invalid TPM certInfo must be rejected');
            } catch (AttestationException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
    }

    public function testU2fRequiresEs256AndSingleCertificate(): void
    {
        $format = new U2f($this->authenticator());
        try {
            $format->initFormat(['attStmt' => ['alg' => -257]]);
            $this->fail('U2F must reject non-ES256 algorithms');
        } catch (AttestationException $exception) {
            $this->assertStringContainsString('only accepts algorithm -7', $exception->getMessage());
        }
        $format->initFormat(['attStmt' => ['sig' => ByteBuffer::fromBinary('sig'), 'x5c' => [ByteBuffer::fromBinary('cert')]]]);
        $this->assertStringContainsString('BEGIN CERTIFICATE', $format->getCertificatePem());
        try {
            $format->validateAttestation('hash');
            $this->fail('Invalid local certificate must fail validation');
        } catch (AttestationException $exception) {
            $this->assertStringContainsString('invalid public key', $exception->getMessage());
        }
    }

    public function testU2fVerifiesSignedAuthenticatorData(): void
    {
        [$key, $certificate] = $this->localEcCertificate('u2f.test');
        $rpIdHash = hash('sha256', 'rp.example', true);
        $clientHash = hash('sha256', 'client-data', true);
        $credentialId = 'credential-id';
        $publicKey = "\x04" . str_repeat("\x11", 32) . str_repeat("\x22", 32);
        $authenticator = $this->createStub(AuthenticatorInterface::class);
        $authenticator->method('getRpIdHash')->willReturn($rpIdHash);
        $authenticator->method('getCredentialId')->willReturn($credentialId);
        $authenticator->method('getPublicKeyU2F')->willReturn($publicKey);
        $data = "\x00" . $rpIdHash . $clientHash . $credentialId . $publicKey;
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

        $format = new U2f($authenticator);
        $format->initFormat(['attStmt' => [
            'sig' => ByteBuffer::fromBinary($signature),
            'x5c' => [ByteBuffer::fromBinary($certificate)],
        ]]);

        $this->assertTrue($format->validateAttestation($clientHash));
        $this->assertFalse($format->validateAttestation('wrong-hash'));
        $this->assertFalse($format->validateRootCertificate([]));
    }

    public function testFormatParsersRejectMissingAndMalformedFields(): void
    {
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $invalidSafetyNetHeader = $encode(json_encode(['alg' => 'RS256'], JSON_THROW_ON_ERROR));
        $invalidSafetyNetPayload = $encode(json_encode(['alg' => 'RS256', 'x5c' => ['Y2VydA==']], JSON_THROW_ON_ERROR));
        $cases = [
            [fn () => (new AndroidKey($this->authenticator()))->initFormat(['attStmt' => ['alg' => -7]]), 'no signature found'],
            [fn () => (new AndroidKey($this->authenticator()))->initFormat(['attStmt' => ['alg' => -7, 'sig' => ByteBuffer::fromBinary('sig'), 'x5c' => []]]), 'invalid x5c certificate'],
            [fn () => (new Apple($this->authenticator()))->initFormat(['attStmt' => ['x5c' => [new stdClass()]]]), 'invalid x5c certificate'],
            [fn () => (new Apple($this->authenticator()))->initFormat(['attStmt' => []]), 'invalid Apple attestation statement: missing x5c'],
            [fn () => (new Packed($this->authenticator()))->initFormat(['attStmt' => ['alg' => -7]]), 'no signature found'],
            [fn () => (new Packed($this->authenticator()))->initFormat(['attStmt' => ['alg' => -7, 'sig' => ByteBuffer::fromBinary('sig'), 'x5c' => ['bad']]]), 'invalid x5c certificate'],
            [fn () => (new Tpm($this->authenticator(), new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0']]), 'unsupported alg: '],
            [fn () => (new Tpm($this->authenticator(), new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0', 'alg' => -7]]), 'signature not found'],
            [fn () => (new Tpm($this->authenticator(), new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0', 'alg' => -7, 'sig' => ByteBuffer::fromBinary('sig')]]), 'certInfo not found'],
            [fn () => (new Tpm($this->authenticator(), new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0', 'alg' => -7, 'sig' => ByteBuffer::fromBinary('sig'), 'certInfo' => ByteBuffer::fromBinary('i')]]), 'pubArea not found'],
            [fn () => (new U2f($this->authenticator()))->initFormat(['attStmt' => ['sig' => ByteBuffer::fromBinary('sig'), 'x5c' => []]]), 'invalid x5c certificate'],
            [fn () => (new AndroidSafetyNet($this->authenticator()))->initFormat(['attStmt' => ['ver' => '1.0']]), 'invalid Android Safety Net Format'],
            [fn () => (new AndroidSafetyNet($this->authenticator()))->initFormat(['attStmt' => ['ver' => '1.0', 'response' => ByteBuffer::fromBinary($encode('not-json') . '.' . $encode('{}') . '.c2ln')]]), 'invalid JWS header'],
            [fn () => (new AndroidSafetyNet($this->authenticator()))->initFormat(['attStmt' => ['ver' => '1.0', 'response' => ByteBuffer::fromBinary($invalidSafetyNetHeader . '.' . $encode('{}') . '.c2ln')]]), 'No X.509 signature in JWS Header'],
            [fn () => (new AndroidSafetyNet($this->authenticator()))->initFormat(['attStmt' => ['ver' => '1.0', 'response' => ByteBuffer::fromBinary($invalidSafetyNetPayload . '.' . $encode('invalid-json') . '.c2ln')]]), 'invalid JWS payload'],
        ];
        foreach ($cases as [$invoke, $message]) {
            try {
                $invoke();
                $this->fail('Invalid format statement must be rejected');
            } catch (AttestationException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }
}

class FormatBaseTester extends FormatBase
{
    public function algorithm(int $alg): ?stdClass
    {
        return $this->_getCoseAlgorithm($alg);
    }

    public function certificate(string $data): string
    {
        return $this->_createCertificatePem($data);
    }
}
