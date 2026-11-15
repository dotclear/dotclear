<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Attestation\Attestation;
use Dotclear\Helper\WebAuthn\Exception\AttestationException;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Helper\WebAuthn\WebAuthnContainer;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class AttestationTest extends TestCase
{
    private function attestation(array $decoded, ?AuthenticatorInterface $authenticator = null, ?WebAuthnContainer $container = null): Attestation
    {
        $cbor = $this->createStub(CborDecoderInterface::class);
        $cbor->method('decode')->willReturn($decoded);

        return new Attestation($authenticator ?? $this->createStub(AuthenticatorInterface::class), $cbor, $container ?? $this->createStub(WebAuthnContainer::class));
    }

    public function testMalformedAndUnsupportedAttestations(): void
    {
        foreach ([
            [[], 'invalid attestation format'],
            [['fmt' => 'none'], 'invalid attestation format (attStmt not available)'],
            [['fmt' => 'none', 'attStmt' => []], 'invalid attestation format (authData not available)'],
        ] as [$decoded, $message]) {
            try {
                $this->attestation($decoded)->fromResponse('', []);
                $this->fail('Malformed attestation must be rejected');
            } catch (AttestationException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        $validShape = ['fmt' => 'unsupported', 'attStmt' => [], 'authData' => new ByteBuffer(str_repeat("\0", 37))];
        $auth = $this->createMock(AuthenticatorInterface::class);
        $auth->expects($this->once())->method('fromBinary')->with(str_repeat("\0", 37));
        try {
            $this->attestation($validShape, $auth)->fromResponse('', ['unsupported']);
            $this->fail('Unknown formats must be rejected');
        } catch (AttestationException $exception) {
            $this->assertStringContainsString('unsupported atttestation format', $exception->getMessage());
        }
    }

    #[RunInSeparateProcess]
    public function testFormatAllowListAndNoneAttestationDelegation(): void
    {
        $authData = new ByteBuffer(str_repeat("\x04", 32) . "\x00" . pack('N', 0));
        $auth = $this->createMock(AuthenticatorInterface::class);
        $auth->expects($this->once())->method('fromBinary')->with($authData->getBinaryString());

        $decoded = ['fmt' => 'none', 'attStmt' => [], 'authData' => $authData];
        try {
            $this->attestation($decoded, $auth)->fromResponse('', []);
            $this->fail('Formats absent from the allow-list must be rejected');
        } catch (AttestationException $exception) {
            $this->assertSame('invalid atttestation format: none', $exception->getMessage());
        }

        $decoder = new \Dotclear\Helper\WebAuthn\Util\CborDecoder(new ByteBuffer());
        $authenticator = new \Dotclear\Helper\WebAuthn\Attestation\Authenticator(
            new \Dotclear\Helper\WebAuthn\Attestation\AttestedCredential(
                new \Dotclear\Helper\WebAuthn\Attestation\CredentialPublicKey($decoder, new \Dotclear\Helper\WebAuthn\Util\DerEncoder()),
                $decoder
            ),
            $decoder
        );
        $cbor = $this->createStub(CborDecoderInterface::class);
        $cbor->method('decode')->willReturn($decoded);
        $actual = new Attestation($authenticator, $cbor, new WebAuthnContainer());
        $actual->fromResponse('', ['none']);

        $this->assertSame('none', $actual->getAttestationFormatType()->value);
        $this->assertSame('', $actual->getCertificateChain());
        $this->assertSame('', $actual->getCertificatePem());
        $this->assertSame('', $actual->getCertificateIssuer());
        $this->assertSame('', $actual->getCertificateSubject());
        $this->assertTrue($actual->validateAttestation('hash'));
        $this->assertFalse($actual->validateRootCertificate([]));
        $this->assertTrue($actual->validateRpIdHash(str_repeat("\x04", 32)));
        $this->assertFalse($actual->validateRpIdHash('wrong'));
    }

    #[RunInSeparateProcess]
    public function testLocalCertificateMetadataAndSignedAttestation(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        $request = openssl_csr_new(['commonName' => 'attestation.test', 'organizationName' => 'Unit tests'], $key);
        $this->assertNotFalse($request);
        $certificate = openssl_csr_sign($request, null, $key, 1);
        $this->assertNotFalse($certificate);
        $pem = '';
        $this->assertTrue(openssl_x509_export($certificate, $pem));
        $der = base64_decode(implode('', array_slice(explode("\n", trim($pem)), 1, -1)), true);
        $this->assertIsString($der);

        $authData = hash('sha256', 'rp.example', true) . "\x00" . pack('N', 0);
        $clientHash = hash('sha256', 'client data', true);
        openssl_sign($authData . $clientHash, $signature, $key, OPENSSL_ALGO_SHA256);
        $byteString = static function (string $bytes): string {
            $length = strlen($bytes);

            return $length < 24 ? chr(0x40 + $length) . $bytes
                : ($length <= 255 ? "\x58" . chr($length) . $bytes : "\x59" . pack('n', $length) . $bytes);
        };
        $encoded = "\xa3\x63fmt\x6bandroid-key\x67attStmt\xa3\x63alg\x39\x01\x00\x63sig"
            . $byteString($signature) . "\x63x5c\x81" . $byteString($der)
            . "\x68authData" . $byteString($authData);

        $container = new WebAuthnContainer();
        $attestation = $container->attestation();
        $attestation->fromResponse($encoded, ['android-key']);

        $this->assertSame('attestation.test (Unit tests)', $attestation->getCertificateIssuer());
        $this->assertSame('attestation.test (Unit tests)', $attestation->getCertificateSubject());
        $this->assertTrue($attestation->validateAttestation($clientHash));
        $this->assertTrue($attestation->validateRpIdHash(hash('sha256', 'rp.example', true)));
        $this->assertSame('', $attestation->getCertificateChain());
    }
}
