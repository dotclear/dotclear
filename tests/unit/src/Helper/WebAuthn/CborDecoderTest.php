<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Exception\CborException;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use Dotclear\Helper\WebAuthn\Util\CborDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CborDecoderTest extends TestCase
{
    private function decoder(): CborDecoder
    {
        $buffer = new ByteBuffer();

        return new CborDecoder($buffer);
    }

    public function testDecodesBasicItemsAndContainers(): void
    {
        $decoder = $this->decoder();
        $this->assertSame(10, $decoder->decode("\x0a"));
        $this->assertSame(-2, $decoder->decode("\x21"));
        $this->assertSame('hi', $decoder->decode("\x62hi"));
        $this->assertSame("\x01\x02", $decoder->decode("\x42\x01\x02")->getBinaryString());
        $this->assertSame([1, 'a'], $decoder->decode("\x82\x01\x61a"));
        $this->assertSame(['a' => 1], $decoder->decode("\xa1\x61a\x01"));
        $this->assertSame('tagged', $decoder->decode("\xc1\x66tagged"));
        $this->assertFalse($decoder->decode("\xf4"));
        $this->assertTrue($decoder->decode("\xf5"));
        $this->assertNull($decoder->decode("\xf6"));
        $this->assertSame(1.0, $decoder->decode("\xf9\x3c\x00"));
        $this->assertSame(1.0, $decoder->decode("\xfa\x3f\xc0\x00\x00"));
        $this->assertSame(2.5, $decoder->decode("\xfb\x40\x04\x00\x00\x00\x00\x00\x00"));
    }

    public function testLengthEncodingsAndInPlaceOffsets(): void
    {
        $decoder = $this->decoder();
        $this->assertSame(24, $decoder->decode("\x18\x18"));
        $this->assertSame(256, $decoder->decode("\x19\x01\x00"));
        $this->assertSame(65536, $decoder->decode("\x1a\x00\x01\x00\x00"));
        $this->assertSame(1, $decoder->decode("\x1b\x00\x00\x00\x00\x00\x00\x00\x01"));

        $end = 0;
        $this->assertSame(2, $decoder->decodeInPlace("\x00\x02\x03", 1, $end));
        $this->assertSame(2, $end);
        $this->assertSame('ok', $decoder->decode(ByteBuffer::fromBinary("\x62ok")));
    }

    public static function malformedItems(): array
    {
        return [
            'trailing data' => ["\x00\x00"],
            'reserved simple' => ["\xf8\x00"],
            'reserved float' => ["\xfc"],
            'indefinite float' => ["\xfd"],
            'reserved length' => ["\x1c"],
            'indefinite length' => ["\x1f"],
            'array truncation' => ["\x81"],
            'bad map key' => ["\xa1\xf4\x00"],
        ];
    }

    #[DataProvider('malformedItems')]
    public function testRejectsMalformedItems(string $data): void
    {
        $this->expectException(\Throwable::class);
        $this->decoder()->decode($data);
    }
}
