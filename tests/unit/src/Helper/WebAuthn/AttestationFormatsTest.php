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

        $tpm = new Tpm(new ByteBuffer());
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
            [fn () => (new Tpm(new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0']]), 'unsupported alg: '],
            [fn () => (new Tpm(new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0', 'alg' => -7]]), 'signature not found'],
            [fn () => (new Tpm(new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0', 'alg' => -7, 'sig' => ByteBuffer::fromBinary('sig')]]), 'certInfo not found'],
            [fn () => (new Tpm(new ByteBuffer()))->initFormat(['attStmt' => ['ver' => '2.0', 'alg' => -7, 'sig' => ByteBuffer::fromBinary('sig'), 'certInfo' => ByteBuffer::fromBinary('i')]]), 'pubArea not found'],
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
