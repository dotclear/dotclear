<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Credentials\Option\AllowCredentialsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\AttestationFormatsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\AttestationOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\AuthenticatorSelectionOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\ChallengeOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\ExcludeCredentialsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\ExtensionsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\PubKeyCredParamsOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\RpOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\TimeoutOption;
use Dotclear\Helper\WebAuthn\Credentials\Option\UserOption;
use Dotclear\Helper\WebAuthn\Type\AlgEnum;
use Dotclear\Helper\WebAuthn\Type\AttestationEnum;
use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;
use Dotclear\Helper\WebAuthn\Type\AuthenticatorAttachmentEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Helper\WebAuthn\Type\ResidentKeyEnum;
use Dotclear\Helper\WebAuthn\Type\TransportsEnum;
use Dotclear\Helper\WebAuthn\Type\UserVerificationEnum;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class OptionTest extends TestCase
{
    public function testRelyingPartyAndUserOptions(): void
    {
        $rp = (new RpOption())->configure(['id' => ' example.test ', 'name' => ' Example ']);
        $this->assertSame('example.test', $rp->id());
        $this->assertSame('Example', $rp->name());
        $this->assertSame(hash('sha256', 'example.test', true), $rp->hash());

        $create = new stdClass();
        $rp->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $create);
        $this->assertSame('example.test', $create->rp->id);
        $this->assertSame('Example', $create->rp->name);
        $get = new stdClass();
        $rp->parseCredentialTypeOptions(CredentialMethodEnum::GET, $get);
        $this->assertSame('example.test', $get->rpId);

        $buffer = new ByteBuffer();
        $user   = (new UserOption($buffer))->configure(['id' => 'user-id', 'name' => 'login', 'displayname' => 'User']);
        $args   = new stdClass();
        $user->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertSame('user-id', $args->user->id->getBinaryString());
        $this->assertSame('login', $args->user->name);
        $this->assertSame('User', $args->user->displayName);
        $get = new stdClass();
        $user->parseCredentialTypeOptions(CredentialMethodEnum::GET, $get);
        $this->assertObjectNotHasProperty('user', $get);

        $this->assertSame('', (new RpOption())->id());
        $this->assertSame('', (new UserOption($buffer))->name());
    }

    public function testCreationAndRequestOptionShapes(): void
    {
        $attestation = (new AttestationOption())->configure(['attestation' => AttestationEnum::DIRECT]);
        $args        = new stdClass();
        $attestation->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertSame(AttestationEnum::DIRECT->value, $args->attestation);
        $defaultAttestation = new stdClass();
        (new AttestationOption())->configure()->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $defaultAttestation);
        $this->assertSame(AttestationEnum::NONE->value, $defaultAttestation->attestation);

        $formats = (new AttestationFormatsOption())->configure(['formats' => [AttestationFormatsEnum::NONE, 'invalid']]);
        $args    = new stdClass();
        $formats->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertSame(['none'], $args->attestationFormats);
        $this->assertCount(count(AttestationFormatsEnum::cases()), (new AttestationFormatsOption())->formats());

        $selection = (new AuthenticatorSelectionOption())->configure([
            'resident_key' => ResidentKeyEnum::REQUIRED,
            'user_verification' => UserVerificationEnum::REQUIRED,
            'authenticator_attachment' => AuthenticatorAttachmentEnum::CROSSPLATFORM,
        ]);
        $this->assertTrue($selection->requireUserPresent());
        $this->assertTrue($selection->requireUserVerification());
        $args = new stdClass();
        $selection->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertSame('cross-platform', $args->authenticatorSelection->authenticatorAttachment);
        $this->assertTrue($args->authenticatorSelection->requireResidentKey);
        $get = new stdClass();
        $selection->parseCredentialTypeOptions(CredentialMethodEnum::GET, $get);
        $this->assertSame(UserVerificationEnum::REQUIRED->value, $get->userVerification);
        $this->assertTrue((new AuthenticatorSelectionOption())->requireUserPresent());
        $this->assertSame(AuthenticatorAttachmentEnum::ANY, AuthenticatorAttachmentEnum::DEFAULT);

        $timeout = (new TimeoutOption())->configure(['timeout' => 12]);
        $args = new stdClass();
        $timeout->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertSame(12000, $args->timeout);
        $defaultTimeout = (new TimeoutOption())->configure(['timeout' => 'bad']);
        $get = new stdClass();
        $defaultTimeout->parseCredentialTypeOptions(CredentialMethodEnum::GET, $get);
        $this->assertSame(30000, $get->timeout);

        $extensions = (new ExtensionsOption())->configure();
        $args = new stdClass();
        $extensions->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertTrue($args->extensions->exts);
        $get = new stdClass();
        $extensions->parseCredentialTypeOptions(CredentialMethodEnum::GET, $get);
        $this->assertObjectNotHasProperty('extensions', $get);

        $params = (new PubKeyCredParamsOption())->configure();
        $args = new stdClass();
        $params->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertContains(AlgEnum::RS256->value, array_column($args->pubKeyCredParams, 'alg'));
    }

    public function testChallengeAndCredentialLists(): void
    {
        $store  = $this->createMock(StoreInterface::class);
        $buffer = new ByteBuffer();
        $store->expects($this->once())->method('setChallenge')->with($this->callback(fn ($challenge): bool => $challenge->getLength() === 9));
        $challenge = (new ChallengeOption($store, $buffer))->configure(['length' => 9]);
        $args      = new stdClass();
        $challenge->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertSame(9, $args->challenge->getLength());
        $this->assertTrue((new ChallengeOption($store, $buffer))->configure(['length' => '5'])->isConfigured());

        $credential = $this->createStub(CredentialInterface::class);
        $credential->method('credentialId')->willReturn('cred');
        $store->method('getCredentials')->willReturn([$credential]);
        $allow = (new AllowCredentialsOption($store, $buffer))->configure(['credentials' => ['a', '', 3]]);
        $args  = new stdClass();
        $allow->parseCredentialTypeOptions(CredentialMethodEnum::GET, $args);
        $this->assertCount(1, $args->allowCredentials);
        $this->assertSame(['a'], array_map(fn ($item) => $item->id->getBinaryString(), $args->allowCredentials));
        $this->assertSame(TransportsEnum::values(), $args->allowCredentials[0]->transports);
        $this->assertObjectNotHasProperty('allowCredentials', (object) []);

        $user = $this->createStub(\Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface::class);
        $user->method('id')->willReturn('user');
        $store->method('getUser')->willReturn($user);
        $exclude = (new ExcludeCredentialsOption($store, $buffer))->configure();
        $args    = new stdClass();
        $exclude->parseCredentialTypeOptions(CredentialMethodEnum::CREATE, $args);
        $this->assertSame('cred', $args->excludeCredentials[0]->id->getBinaryString());
        $this->assertSame('public-key', $args->excludeCredentials[0]->type);
    }
}
