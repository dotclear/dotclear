<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Type\AlgEnum;
use Dotclear\Helper\WebAuthn\Type\AttestationEnum;
use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;
use Dotclear\Helper\WebAuthn\Type\AuthenticatorAttachmentEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialTypeEnum;
use Dotclear\Helper\WebAuthn\Type\MediationEnum;
use Dotclear\Helper\WebAuthn\Type\ResidentKeyEnum;
use Dotclear\Helper\WebAuthn\Type\TransportsEnum;
use Dotclear\Helper\WebAuthn\Type\TypeEnum;
use Dotclear\Helper\WebAuthn\Type\UserVerificationEnum;
use PHPUnit\Framework\TestCase;

class TypeEnumTest extends TestCase
{
    public function testEnumValuesExposeTheirCases(): void
    {
        foreach ([
            AlgEnum::class, AttestationEnum::class, AttestationFormatsEnum::class,
            AuthenticatorAttachmentEnum::class, CredentialMethodEnum::class,
            CredentialTypeEnum::class, MediationEnum::class, ResidentKeyEnum::class,
            TransportsEnum::class, TypeEnum::class, UserVerificationEnum::class,
        ] as $enum) {
            $this->assertSame(array_column($enum::cases(), 'value'), $enum::values());
        }

        $this->assertTrue(ResidentKeyEnum::REQUIRED->required());
        $this->assertFalse(ResidentKeyEnum::PREFERRED->required());
    }
}
