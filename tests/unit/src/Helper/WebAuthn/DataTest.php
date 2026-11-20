<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Data\Certificates;
use Dotclear\Helper\WebAuthn\Data\Client;
use Dotclear\Helper\WebAuthn\Data\Credential;
use Dotclear\Helper\WebAuthn\Data\Provider;
use Dotclear\Helper\WebAuthn\Data\Store;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    public function testClientResponseValidation(): void
    {
        $store = $this->createStub(StoreInterface::class);
        $rp    = $this->createStub(RpOptionInterface::class);
        $rp->method('id')->willReturn('example.test');
        $store->method('getRelyingParty')->willReturn($rp);
        $store->method('getChallenge')->willReturn(ByteBuffer::fromBinary('nonce'));
        $client = new Client($store, new ByteBuffer());

        $this->assertFalse($client->checkObject());
        $this->assertFalse($client->checkCreate());
        $this->assertFalse($client->checkGet());
        $this->assertFalse($client->checkChallenge());
        $this->assertFalse($client->checkOrigin());

        $json = json_encode(['type' => 'webauthn.create', 'challenge' => 'bm9uY2U', 'origin' => 'https://example.test']);
        $client->fromResponse($json);
        $this->assertTrue($client->checkObject());
        $this->assertTrue($client->checkCreate());
        $this->assertFalse($client->checkGet());
        $this->assertTrue($client->checkChallenge());
        $this->assertTrue($client->checkOrigin());
        $this->assertSame(hash('sha256', $json, true), $client->getHash());

        $client->fromResponse('[]');
        $this->assertFalse($client->checkObject());
        $client->fromResponse('{"type":"webauthn.get","challenge":"bm9uY2U","origin":"http://example.test","crossOrigin":true}');
        $this->assertTrue($client->checkGet());
        $this->assertFalse($client->checkOrigin());
        $client->fromResponse('{"origin":"https://sub.example.test"}');
        $this->assertFalse($client->checkOrigin());
        $client->fromResponse('{"origin":"http://example.test"}');
        $this->assertFalse($client->checkOrigin());
        $client->fromResponse('{"origin":"android:apk-key-hash:aandroid:apk-key-hash:b"}');
        $this->assertFalse($client->checkOrigin());
        $emptyRp = $this->createStub(RpOptionInterface::class);
        $emptyRp->method('id')->willReturn('');
        $emptyStore = $this->createStub(StoreInterface::class);
        $emptyStore->method('getRelyingParty')->willReturn($emptyRp);
        $emptyClient = new Client($emptyStore, new ByteBuffer());
        $emptyClient->fromResponse('{"origin":"https://example.test"}');
        $this->assertFalse($emptyClient->checkOrigin());
        $localStore = $this->createStub(StoreInterface::class);
        $localRp    = $this->createStub(RpOptionInterface::class);
        $localRp->method('id')->willReturn('localhost');
        $localStore->method('getRelyingParty')->willReturn($localRp);
        $localClient = new Client($localStore, new ByteBuffer());
        $localClient->fromResponse('{"origin":"http://localhost"}');
        $this->assertTrue($localClient->checkOrigin());

        $client->addAndroidKeyHash('known-hash');
        $client->fromResponse('{"origin":"android:apk-key-hash:known-hash"}');
        $this->assertTrue($client->checkOrigin());
        $client->fromResponse('{"origin":"android:apk-key-hash:unknown"}');
        $this->assertFalse($client->checkOrigin());
    }

    public function testCredentialNormalizationAndCloning(): void
    {
        $rp = $this->createStub(RpOptionInterface::class);
        $rp->method('id')->willReturn('rp.example');
        $credential = new Credential($rp);
        $credential->fromArray(['label' => 'Passkey', 'credentialId' => 'id', 'signatureCounter' => '7', 'AAGUID' => str_repeat("\x01", 16), 'userPresent' => true]);
        $copy = $credential->newFromArray(['label' => 12, 'signatureCounter' => 'invalid']);

        $this->assertSame('Passkey', $credential->label());
        $this->assertSame('id', $credential->credentialId());
        $this->assertSame(7, $credential->signatureCounter());
        $this->assertSame('', $copy->label());
        $this->assertSame(0, $copy->signatureCounter());
        $this->assertTrue($credential->userPresent());
        $this->assertFalse($credential->userVerified());
        $this->assertSame('01010101-0101-0101-0101-010101010101', $credential->UUID());
        $this->assertSame('', (new Credential($rp))->label());
    }

    public function testCredentialAccessorsAndAttestationImport(): void
    {
        $rp = $this->createStub(RpOptionInterface::class);
        $rp->method('id')->willReturn('rp.example');
        $credential = new Credential($rp);
        $data = [
            'createDate' => 'today', 'label' => 'Key', 'rpId' => 'rp',
            'attestationFormat' => 'none', 'credentialId' => 'cid',
            'credentialPublicKey' => 'pem', 'certificateChain' => 'chain',
            'certificate' => 'cert', 'certificateIssuer' => 'issuer',
            'certificateSubject' => 'subject', 'signatureCounter' => '3',
            'AAGUID' => str_repeat("\x02", 16), 'userPresent' => 1,
            'userVerified' => 1, 'isBackupEligible' => 1, 'isBackedUp' => 1,
        ];
        $credential->fromArray($data);
        foreach ([
            'createDate' => 'today', 'label' => 'Key', 'rpId' => 'rp',
            'attestationFormat' => 'none', 'credentialId' => 'cid',
            'credentialPublicKey' => 'pem', 'certificateChain' => 'chain',
            'certificate' => 'cert', 'certificateIssuer' => 'issuer',
            'certificateSubject' => 'subject', 'AAGUID' => str_repeat("\x02", 16),
        ] as $method => $expected) {
            $this->assertSame($expected, $credential->$method());
        }
        $this->assertSame(3, $credential->signatureCounter());
        $this->assertTrue($credential->userPresent());
        $this->assertTrue($credential->userVerified());
        $this->assertTrue($credential->isBackupEligible());
        $this->assertTrue($credential->isBackedUp());

        $authenticator = $this->createStub(\Dotclear\Interface\Helper\WebAuthn\Attestation\AuthenticatorInterface::class);
        $authenticator->method('getCredentialId')->willReturn('attested-id');
        $authenticator->method('getPublicKeyPem')->willReturn('attested-pem');
        $authenticator->method('getSignCount')->willReturn(4);
        $authenticator->method('getAAGUID')->willReturn(str_repeat("\x03", 16));
        $authenticator->method('isUserPresent')->willReturn(true);
        $authenticator->method('isUserVerified')->willReturn(false);
        $authenticator->method('isBackupEligible')->willReturn(true);
        $authenticator->method('isBackup')->willReturn(false);
        $attestation = $this->createStub(\Dotclear\Interface\Helper\WebAuthn\Attestation\AttestationInterface::class);
        $attestation->method('getAttestationFormatType')->willReturn(\Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum::NONE);
        $attestation->method('getAuthenticator')->willReturn($authenticator);
        $attestation->method('getCertificateChain')->willReturn('');
        $attestation->method('getCertificatePem')->willReturn('');
        $attestation->method('getCertificateIssuer')->willReturn('');
        $attestation->method('getCertificateSubject')->willReturn('');
        $imported = new Credential($rp);
        $imported->fromAttestation($attestation, '  imported  ');
        $this->assertSame('imported', $imported->label());
        $this->assertSame('rp.example', $imported->rpId());
        $this->assertSame('attested-id', $imported->credentialId());
        $this->assertSame(4, $imported->signatureCounter());
        $this->assertTrue($imported->isBackupEligible());
        $this->assertFalse($imported->isBackedUp());
    }

    public function testStoreEncodingAndSessionChallenges(): void
    {
        $rp   = $this->createStub(RpOptionInterface::class);
        $user = $this->createStub(\Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface::class);
        $store = new class(new ByteBuffer(), $rp, $user) extends Store {
        };
        $this->assertSame('webauthn', $store->getType());
        $this->assertSame('', Store::encodeValue(null));
        $this->assertSame(base64_encode('binary'), Store::encodeValue('binary'));
        $this->assertSame('binary', Store::decodeValue(base64_encode('binary')));
        $this->assertSame(['a' => base64_encode('x'), 'b' => ''], Store::encodeData(['a' => 'x', 'b' => []]));
        $this->assertSame(['a' => 'x', 'b' => ''], Store::decodeData(['a' => base64_encode('x'), 'b' => []]));
        $this->assertFalse(Store::isChallengeExpired(time()));
        $this->assertTrue(Store::isChallengeExpired(0));
        $this->assertTrue(Store::isChallengeExpired(time() - Store::CHALLENGE_TTL - 2));

        $_SESSION['webauthn_challenge'] = 'malformed';
        $_SESSION['webauthn_challenge_time'] = time();
        $store->setChallenge(ByteBuffer::fromBinary('challenge'));
        $this->assertSame('challenge', $store->getChallenge()->getBinaryString());
        $store->delChallenge();
        $this->assertArrayNotHasKey('webauthn_challenge', $_SESSION);
        $this->assertSame(32, $store->getChallenge()->getLength());
        $credential = $this->createStub(\Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface::class);
        $store->setCredential($credential);
        $this->assertSame([], $store->getCredentials('credential', 'user'));
        $store->delCredential('credential');
        $store->setProviders(['id' => 'name']);
        $this->assertSame([], $store->getProviders());
        $this->assertSame($rp, $store->getRelyingParty());
        $this->assertSame($user, $store->getUser());
    }

    public function testCertificatesAndProviderCacheWithoutNetwork(): void
    {
        $certificates = new Certificates(new ByteBuffer());
        $certificates->addCertificates('/path-that-does-not-exist');
        $this->assertTrue($certificates->checkRequested());
        $this->assertSame([], $certificates->getCertificates());
        $certificates->addCertificates(__DIR__);
        $certificates->addCertificates(__FILE__);
        $certificates->addCertificates(__FILE__);
        $this->assertSame([realpath(__FILE__)], $certificates->getCertificates());

        $store = $this->createMock(StoreInterface::class);
        $store->method('getProviders')->willReturn([]);
        $store->expects($this->once())->method('setProviders')->with(['id' => 'Local key']);
        $provider = new class($store) extends Provider {
            public function updateProviders(): void
            {
                $this->stack = ['id' => 'Local key'];
                $this->store->setProviders($this->stack);
            }
        };
        $this->assertSame('Local key', $provider->getProvider('id'));
        $this->assertSame('Local key', $provider->getProvider('id'));
        $this->assertSame('Unknown passkey.', $provider->getProvider('missing'));

        $failingStore = $this->createStub(StoreInterface::class);
        $failingStore->method('getProviders')->willReturn([]);
        $failing = new class($failingStore) extends Provider {
            public int $attempts = 0;

            public function updateProviders(): void
            {
                ++$this->attempts;
                throw new \RuntimeException('offline refresh');
            }
        };
        $this->assertSame('Unknown passkey.', $failing->getProvider('unknown'));
        $this->assertSame('Unknown passkey.', $failing->getProvider('unknown'));
        $this->assertSame(1, $failing->attempts);
    }

    public function testCertificatesDirectoryFiltersExtensionsAndDeduplicatesPaths(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'webauthn-certificates-' . bin2hex(random_bytes(6));
        $this->assertTrue(mkdir($directory));
        $pem = $directory . DIRECTORY_SEPARATOR . 'root.PEM';
        $der = $directory . DIRECTORY_SEPARATOR . 'root.der';
        $text = $directory . DIRECTORY_SEPARATOR . 'readme.txt';
        $nested = $directory . DIRECTORY_SEPARATOR . 'nested';

        try {
            $this->assertNotFalse(file_put_contents($pem, 'pem'));
            $this->assertNotFalse(file_put_contents($der, 'der'));
            $this->assertNotFalse(file_put_contents($text, 'text'));
            $this->assertTrue(mkdir($nested));
            $this->assertNotFalse(file_put_contents($nested . DIRECTORY_SEPARATOR . 'nested.pem', 'nested'));

            $certificates = new Certificates(new ByteBuffer());
            $certificates->addCertificates($directory . DIRECTORY_SEPARATOR);
            $certificates->addCertificates($pem);

            $this->assertSame([realpath($pem), realpath($der)], $certificates->getCertificates());
            $this->assertTrue($certificates->checkRequested());
        } finally {
            foreach ([$nested . DIRECTORY_SEPARATOR . 'nested.pem', $pem, $der, $text] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            if (is_dir($nested)) {
                rmdir($nested);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
