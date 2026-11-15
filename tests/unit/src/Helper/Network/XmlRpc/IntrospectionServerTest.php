<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use Dotclear\Helper\Network\XmlRpc\Base64;
use Dotclear\Helper\Network\XmlRpc\Date;
use Dotclear\Helper\Network\XmlRpc\XmlRpcException;
use PHPUnit\Framework\TestCase;

class IntrospectionServerTest extends TestCase
{
    public function testCallbacksAndIntrospectionMethods(): void
    {
        $server = new IntrospectionServerTester();
        $server->register(
            'sample.types',
            static fn (...$args): array => $args,
            ['array', 'string', 'int', 'i4', 'double', 'dateTime.iso8601', 'boolean', 'base64', 'struct'],
            'Sample documentation'
        );

        $result = $server->invoke('sample.types', ['text', 1, 2, 3.14, new Date(1_234_567_890), true, 'base64', ['key' => 'value']]);
        $this->assertSame('text', $result[0]);
        $this->assertSame(['text', 1, 2, 3.14, $result[4], true, 'base64', ['key' => 'value']], $result);
        $this->assertInstanceOf(Date::class, $result[4]);

        $signature = $server->signature('sample.types');
        $this->assertSame([['array'], 'string', 42, 42, 3.1415], array_slice($signature, 0, 5));
        $this->assertInstanceOf(Date::class, $signature[5]);
        $this->assertTrue($signature[6]);
        $this->assertInstanceOf(Base64::class, $signature[7]);
        $this->assertSame(['struct' => 'struct'], $signature[8]);
        $this->assertSame('Sample documentation', $server->help('sample.types'));

        $this->assertArrayHasKey('introspection', $server->invoke('system.getCapabilities', []));
        $this->assertContains('sample.types', $server->invoke('system.listMethods', []));
        $this->assertIsArray($server->invoke('system.methodSignature', ['sample.types']));
        $this->assertSame('Sample documentation', $server->invoke('system.methodHelp', ['sample.types']));
    }

    public function testArgumentValidationAndErrors(): void
    {
        $server = new IntrospectionServerTester();
        $server->register('sample.string', static fn (string $value): string => $value, ['string', 'string']);
        $this->assertSame('value', $server->invoke('sample.string', 'value'));

        $this->expectException(XmlRpcException::class);
        $this->expectExceptionMessage('Server error. Wrong number of method parameters');
        $server->invoke('sample.string', ['one', 'two']);
    }

    public function testInvalidArgumentAndUnknownMethodErrors(): void
    {
        $server = new IntrospectionServerTester();
        $server->register('sample.integer', static fn (int $value): int => $value, ['int', 'int']);

        try {
            $server->invoke('sample.integer', ['not an integer']);
            $this->fail('Expected invalid argument error.');
        } catch (XmlRpcException $exception) {
            $this->assertSame('Server error. Invalid method parameters', $exception->getMessage());
        }

        $this->expectException(XmlRpcException::class);
        $this->expectExceptionMessage('Server error. Requested method "missing" not specified.');
        $server->invoke('missing', []);
    }

    public function testRejectsInvalidArgumentTypes(): void
    {
        $server = new IntrospectionServerTester();
        $server->register('sample.validated', static fn (...$args): array => $args, ['array', 'string', 'int', 'boolean', 'float', 'date']);

        foreach ([
            [['value' => 'not a string'], 1, true, 1.0, new Date(1)],
            ['text', ['not an integer'], true, 1.0, new Date(1)],
            ['text', 1, 1, 1.0, new Date(1)],
            ['text', 1, true, 1, new Date(1)],
            ['text', 1, true, 1.0, 'not a date'],
        ] as $arguments) {
            try {
                $server->invoke('sample.validated', $arguments);
                $this->fail('Expected invalid method parameters.');
            } catch (XmlRpcException $exception) {
                $this->assertSame('Server error. Invalid method parameters', $exception->getMessage());
            }
        }
    }

    public function testUnknownSignatureMethod(): void
    {
        $server = new IntrospectionServerTester();

        $this->expectException(XmlRpcException::class);
        $this->expectExceptionMessage('Server error. Requested method "missing" not specified.');
        $server->signature('missing');
    }
}

class IntrospectionServerTester extends \Dotclear\Helper\Network\XmlRpc\IntrospectionServer
{
    public function register(string $method, callable $callback, array $signature, string $help = ''): void
    {
        $this->addCallback($method, $callback, $signature, $help);
    }

    public function invoke(string $method, mixed $args): mixed
    {
        return $this->call($method, $args);
    }

    public function signature(string $method): array
    {
        return $this->methodSignature($method);
    }

    public function help(string $method): string
    {
        return $this->methodHelp($method);
    }
}
