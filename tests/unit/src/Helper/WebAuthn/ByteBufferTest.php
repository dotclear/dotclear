<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\WebAuthn;

use Dotclear\Helper\WebAuthn\Exception\ByteBufferException;
use Dotclear\Helper\WebAuthn\Util\ByteBuffer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ByteBufferTest extends TestCase
{
    protected function tearDown(): void
    {
        ByteBuffer::useBase64UrlEncoding(false);
    }

    public function testFactoriesAndRepresentations(): void
    {
        $buffer = ByteBuffer::fromBinary("\x00\xff");

        $this->assertSame(2, $buffer->getLength());
        $this->assertSame("\x00\xff", $buffer->getBinaryString());
        $this->assertSame('00ff', $buffer->getHex());
        $this->assertSame('00000000-0000-0000-0000-000000000000', (new ByteBuffer(str_repeat("\0", 16)))->getUUID());
        $this->assertSame('00ff', (string) $buffer);
        $this->assertTrue($buffer->equals("\x00\xff"));
        $this->assertTrue($buffer->equals(ByteBuffer::fromHex('00ff')));
        $this->assertFalse($buffer->equals(''));
        $this->assertSame("\x00\xff", ByteBuffer::fromBase64Url('AP8')->getBinaryString());
        $this->assertSame('=?BINARY?B?AP8=?=', $buffer->jsonSerialize());

        ByteBuffer::useBase64UrlEncoding(true);
        $this->assertSame('AP8', $buffer->jsonSerialize());
        $this->assertSame('"AP8"', json_encode($buffer));
    }

    public function testJsonAndSerialization(): void
    {
        $buffer = ByteBuffer::fromBinary('{"ok":true}');
        $this->assertEquals((object) ['ok' => true], $buffer->getJson());

        $restored = unserialize(serialize($buffer));
        $this->assertInstanceOf(ByteBuffer::class, $restored);
        $this->assertSame($buffer->getBinaryString(), $restored->getBinaryString());

        $legacy = new ByteBuffer();
        $legacy->unserialize(serialize('legacy'));
        $this->assertSame('legacy', $legacy->getBinaryString());

        $this->assertSame(32, ByteBuffer::randomBuffer(0)->getLength());
        $this->assertSame(4, ByteBuffer::randomBuffer(4)->getLength());
    }

    public function testMagicUnserializationAndEmptyBuffer(): void
    {
        $empty = new ByteBuffer();
        $this->assertTrue($empty->isEmpty());
        $this->assertSame('', $empty->getBinaryString());

        $restored = new ByteBuffer();
        $restored->__unserialize(['data' => serialize('payload')]);
        $this->assertSame('payload', $restored->getBinaryString());

        $unchanged = new ByteBuffer('original');
        $unchanged->__unserialize([]);
        $this->assertSame('original', $unchanged->getBinaryString());

        $restored->unserialize(serialize(123));
        $this->assertSame('', $restored->getBinaryString());
    }

    public function testNumericReads(): void
    {
        $buffer = ByteBuffer::fromBinary(pack('n', 65535) . pack('N', 4294967295) . pack('J', 9) . pack('G', 1.5) . pack('E', 2.5));

        $this->assertSame(65535, $buffer->getUint16Val(0));
        $this->assertSame(4294967295, $buffer->getUint32Val(2));
        $this->assertSame(9, $buffer->getUint64Val(6));
        $this->assertSame(1.0, $buffer->getFloatVal(14));
        $this->assertSame(2.5, $buffer->getDoubleVal(18));

        $this->assertSame(1.0, ByteBuffer::fromBinary("\x3c\x00")->getHalfFloatVal(0));
        $this->assertSame(-1.0, ByteBuffer::fromBinary("\xbc\x00")->getHalfFloatVal(0));
        $this->assertSame(INF, ByteBuffer::fromBinary("\x7c\x00")->getHalfFloatVal(0));
        $this->assertNan(ByteBuffer::fromBinary("\x7c\x01")->getHalfFloatVal(0));
        $this->assertSame(2 ** -24, ByteBuffer::fromBinary("\x00\x01")->getHalfFloatVal(0));
    }

    public static function invalidReads(): array
    {
        return [
            'bytes negative offset' => [fn () => (new ByteBuffer('abc'))->getBytes(-1, 1)],
            'bytes negative length' => [fn () => (new ByteBuffer('abc'))->getBytes(0, -1)],
            'bytes past end' => [fn () => (new ByteBuffer('abc'))->getBytes(2, 2)],
            'byte past end' => [fn () => (new ByteBuffer(''))->getByteVal(0)],
            'uint16 past end' => [fn () => (new ByteBuffer('x'))->getUint16Val(0)],
            'uint32 past end' => [fn () => (new ByteBuffer('x'))->getUint32Val(0)],
            'uint64 past end' => [fn () => (new ByteBuffer('x'))->getUint64Val(0)],
            'uint64 overflow' => [fn () => (new ByteBuffer("\x80" . str_repeat("\0", 7)))->getUint64Val(0)],
            'float past end' => [fn () => (new ByteBuffer('x'))->getFloatVal(0)],
            'double past end' => [fn () => (new ByteBuffer('x'))->getDoubleVal(0)],
            'bad base64' => [fn () => ByteBuffer::fromBase64Url('!')],
            'bad json' => [fn () => (new ByteBuffer('{'))->getJson()],
        ];
    }

    #[DataProvider('invalidReads')]
    public function testInvalidReads(callable $read): void
    {
        $this->expectException(ByteBufferException::class);
        $read();
    }
}
