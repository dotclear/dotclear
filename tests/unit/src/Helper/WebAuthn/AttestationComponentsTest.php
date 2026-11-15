<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Attestation\AttestedCredential;
use Dotclear\Helper\WebAuthn\Attestation\Authenticator;
use Dotclear\Helper\WebAuthn\Attestation\CredentialPublicKey;
use Dotclear\Helper\WebAuthn\Attestation\Format\None;
use Dotclear\Helper\WebAuthn\Exception\AuthenticatorException;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Helper\WebAuthn\Util\CborDecoder;
use Dotclear\Helper\WebAuthn\Util\DerEncoder;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestedCredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\CredentialPublicKeyInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\DerEncoderInterface;
use PHPUnit\Framework\TestCase;

class AttestationComponentsTest extends TestCase
{
    public function testAuthenticatorFlagsAndExtensionData(): void
    {
        $credential = $this->createStub(AttestedCredentialInterface::class);
        $decoder    = new CborDecoder(new ByteBuffer());
        $auth       = new Authenticator($credential, $decoder);
        $binary     = str_repeat("\x01", 32) . "\x9d" . pack('N', 7) . "\xa1\x61x\x01";
        $auth->fromBinary($binary);

        $this->assertSame($binary, $auth->getBinary());
        $this->assertSame(str_repeat("\x01", 32), $auth->getRpIdHash());
        $this->assertSame(7, $auth->getSignCount());
        $this->assertTrue($auth->isUserPresent());
        $this->assertTrue($auth->isUserVerified());
        $this->assertTrue($auth->isBackupEligible());
        $this->assertTrue($auth->isBackup());
        $this->assertTrue($auth->isExtensionDataIncluded());
        $this->assertSame(['x' => 1], $auth->getExtensionData());
        $this->assertFalse($auth->isAttestedCredentialDataIncluded());
        $this->expectException(AuthenticatorException::class);
        $auth->getPublicKeyPem();
    }

    public function testAuthenticatorRejectsShortAndInvalidBackupData(): void
    {
        $auth = new Authenticator($this->createStub(AttestedCredentialInterface::class), new CborDecoder(new ByteBuffer()));
        try {
            $auth->fromBinary('short');
            $this->fail('Short data must be rejected');
        } catch (AuthenticatorException $exception) {
            $this->assertSame('Invalid authenticator input', $exception->getMessage());
        }

        $this->expectException(AuthenticatorException::class);
        $auth->fromBinary(str_repeat("\0", 32) . "\x10" . pack('N', 0));
    }

    public function testAuthenticatorDelegatesIncludedCredentialData(): void
    {
        $publicKey = $this->createStub(CredentialPublicKeyInterface::class);
        $publicKey->method('getPem')->willReturn('public-pem');
        $publicKey->method('getU2F')->willReturn('u2f-key');
        $credential = $this->createMock(AttestedCredentialInterface::class);
        $binary = str_repeat("\0", 32) . "\x40" . pack('N', 0);
        $credential->expects($this->once())->method('fromBinary')->with($binary);
        $credential->method('getOffset')->willReturn(55);
        $credential->method('getAAGUID')->willReturn(str_repeat("\x01", 16));
        $credential->method('getCredentialID')->willReturn('credential');
        $credential->method('getCredentialPublicKey')->willReturn($publicKey);

        $auth = new Authenticator($credential, new CborDecoder(new ByteBuffer()));
        $auth->fromBinary($binary);
        $this->assertSame($credential, $auth->getAttestedCredentialData());
        $this->assertSame(str_repeat("\x01", 16), $auth->getAAGUID());
        $this->assertSame('credential', $auth->getCredentialId());
        $this->assertSame('public-pem', $auth->getPublicKeyPem());
        $this->assertSame('u2f-key', $auth->getPublicKeyU2F());
        $this->expectException(AuthenticatorException::class);
        $auth->getExtensionData();
    }

    public function testAttestedCredentialParsesIdAndKey(): void
    {
        $key = $this->createMock(CredentialPublicKeyInterface::class);
        $key->expects($this->once())->method('fromCbor')->with([1 => 2]);
        $cbor = $this->createMock(CborDecoderInterface::class);
        $cbor->expects($this->once())->method('decodeInPlace')->willReturnCallback(
            function ($binary, $start, &$end): array {
                $end = $start + 1;

                return [1 => 2];
            }
        );
        $credential = new AttestedCredential($key, $cbor);
        $binary = str_repeat("\0", 37) . str_repeat("\x11", 16) . pack('n', 1) . 'i' . "\x00";
        $credential->fromBinary($binary);

        $this->assertSame(str_repeat("\x11", 16), $credential->getAAGUID());
        $this->assertSame('i', $credential->getCredentialID());
        $this->assertSame(57, $credential->getOffset());
        $this->assertSame($key, $credential->getCredentialPublicKey());

        $bad = new AttestedCredential($key, $cbor);
        $this->expectException(AuthenticatorException::class);
        $bad->fromBinary(str_repeat("\0", 55));
    }

    public function testCredentialPublicKeyFormatsAndValidation(): void
    {
        $cbor = $this->createStub(CborDecoderInterface::class);
        $der  = new DerEncoder();
        $key  = new CredentialPublicKey($cbor, $der);
        $key->fromCbor([1 => 2, 3 => -7, -1 => 1, -2 => ByteBuffer::fromBinary(str_repeat('x', 32)), -3 => ByteBuffer::fromBinary(str_repeat('y', 32))]);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $key->getPem());
        $this->assertSame("\x04" . str_repeat('x', 32) . str_repeat('y', 32), $key->getU2F());

        $key->fromCbor([1 => 1, 3 => -8, -1 => 6, -2 => ByteBuffer::fromBinary(str_repeat('z', 32))]);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $key->getPem());
        try {
            $key->getU2F();
            $this->fail('Non-EC keys have no U2F representation');
        } catch (AuthenticatorException $exception) {
            $this->assertSame('signature algorithm not ES256', $exception->getMessage());
        }

        $key->fromCbor([1 => 3, 3 => -257, -1 => ByteBuffer::fromBinary(str_repeat("\x01", 256)), -2 => ByteBuffer::fromBinary("\x01\x00\x01")]);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $key->getPem());

        $this->expectException(AuthenticatorException::class);
        $key->fromCbor([1 => 2, 3 => 42]);
    }

    public function testCredentialPublicKeyRejectsInvalidCoordinatesAndNoneFormat(): void
    {
        $key = new CredentialPublicKey($this->createStub(CborDecoderInterface::class), new DerEncoder());
        $this->expectException(AuthenticatorException::class);
        $key->fromCbor([1 => 2, 3 => -7, -1 => 1]);
    }

    public static function invalidCoseKeys(): array
    {
        $bytes32 = ByteBuffer::fromBinary(str_repeat('x', 32));
        return [
            'EC key type' => [[1 => 1, 3 => -7, -1 => 1, -2 => $bytes32, -3 => $bytes32], 'public key not in EC2 format'],
            'EC curve' => [[1 => 2, 3 => -7, -1 => 2, -2 => $bytes32, -3 => $bytes32], 'curve not P-256'],
            'EC x length' => [[1 => 2, 3 => -7, -1 => 1, -2 => ByteBuffer::fromBinary('x'), -3 => $bytes32], 'Invalid X-coordinate'],
            'EC y length' => [[1 => 2, 3 => -7, -1 => 1, -2 => $bytes32, -3 => ByteBuffer::fromBinary('y')], 'Invalid Y-coordinate'],
            'EdDSA key type' => [[1 => 2, 3 => -8, -1 => 6, -2 => $bytes32], 'public key not in OKP format'],
            'EdDSA curve' => [[1 => 1, 3 => -8, -1 => 1, -2 => $bytes32], 'curve not Ed25519'],
            'EdDSA x length' => [[1 => 1, 3 => -8, -1 => 6, -2 => ByteBuffer::fromBinary('x')], 'Invalid X-coordinate'],
            'RSA key type' => [[1 => 2, 3 => -257, -1 => ByteBuffer::fromBinary(str_repeat('n', 256)), -2 => ByteBuffer::fromBinary('e')], 'public key not in RSA format'],
            'RSA modulus' => [[1 => 3, 3 => -257, -1 => ByteBuffer::fromBinary('n'), -2 => ByteBuffer::fromBinary('e')], 'Invalid RSA modulus'],
            'RSA exponent' => [[1 => 3, 3 => -257, -1 => ByteBuffer::fromBinary(str_repeat('n', 256)), -2 => ByteBuffer::fromBinary('e')], 'Invalid RSA public exponent'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCoseKeys')]
    public function testCredentialPublicKeyRejectsInvalidCoseKey(array $coseKey, string $message): void
    {
        try {
            (new CredentialPublicKey($this->createStub(CborDecoderInterface::class), new DerEncoder()))->fromCbor($coseKey);
            $this->fail('Invalid COSE key must be rejected');
        } catch (AuthenticatorException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }

    public function testNoneAttestationChecksEmptyStatement(): void
    {
        $format = new None($this->createStub(\Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface::class));
        $format->initFormat(['attStmt' => []]);
        $this->assertSame('', $format->getCertificatePem());
        $this->assertTrue($format->validateAttestation('hash'));
        $this->assertFalse($format->validateRootCertificate([]));
        $format->initFormat([]);
        $this->assertFalse($format->validateAttestation('hash'));
    }
}
