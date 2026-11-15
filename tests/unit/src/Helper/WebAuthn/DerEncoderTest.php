<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Util\DerEncoder;
use PHPUnit\Framework\TestCase;

class DerEncoderTest extends TestCase
{
    public function testEncodesSubjectPublicKeyInfoFormats(): void
    {
        $this->assertStringStartsWith("\x30", DerEncoder::encodeEC2(str_repeat("\x01", 65)));
        $this->assertStringStartsWith("\x30", DerEncoder::encodeOKP(str_repeat("\x02", 32)));
        $rsa = DerEncoder::encodeRSA("\x00\x80\x01", "\x01\x00\x01");
        $this->assertStringStartsWith("\x30", $rsa);
        $this->assertStringContainsString("\x02\x03\x00\x80\x01", $rsa);
        $this->assertStringContainsString("\x02\x03\x01\x00\x01", $rsa);
    }

    public function testEncodesLongLengths(): void
    {
        $encoded = DerEncoder::encodeOKP(str_repeat('x', 130));
        $this->assertSame("\x30\x81", substr($encoded, 0, 2));
        $this->assertSame(141, ord($encoded[2]));
    }
}
