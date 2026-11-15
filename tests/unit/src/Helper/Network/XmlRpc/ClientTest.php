<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use Dotclear\Helper\Network\XmlRpc\XmlRpcException;
use Exception;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testQueryResponseAndRequestEncoding(): void
    {
        $client = new ClientTester();
        $client->setResponse(200, 'OK', '<methodResponse><params><param><value><string>response</string></value></param></params></methodResponse>');

        $this->assertSame('response', $client->query('sample.echo', 'request'));
        $this->assertSame('POST /xmlrpc HTTP/1.0', $client->requestHeaders[0]);
        $this->assertContains('Content-Type: text/xml', $client->requestHeaders);
        $this->assertStringContainsString('sample.echo', $client->requestHeaders[array_key_last($client->requestHeaders)]);
    }

    public function testHttpErrorAndFaultResponses(): void
    {
        $client = new ClientTester();
        $client->setResponse(404, 'Not Found', '');

        try {
            $client->query('missing.method');
            $this->fail('Expected HTTP status error.');
        } catch (Exception $exception) {
            $this->assertSame('HTTP Error. 404 Not Found', $exception->getMessage());
        }

        $client->setResponse(200, 'OK', '<methodResponse><fault><value><struct><member><name>faultCode</name><value><int>42</int></value></member><member><name>faultString</name><value><string>fault response</string></value></member></struct></value></fault></methodResponse>');

        $this->expectException(XmlRpcException::class);
        $this->expectExceptionMessage('fault response');
        $client->query('failing.method');
    }

    public function testInvalidUrlAndMalformedResponse(): void
    {
        $this->assertInstanceOf(\Dotclear\Helper\Network\XmlRpc\Client::class, new \Dotclear\Helper\Network\XmlRpc\Client('invalid-url'));

        $client = new ClientTester();
        $client->setResponse(200, 'OK', '<broken');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('XML Parser Error.');
        $client->query('malformed.response');
    }
}

class ClientTester extends \Dotclear\Helper\Network\XmlRpc\Client
{
    public int $responseStatus = 200;
    public string $responseMessage = 'OK';
    public string $responseContent = '';

    /**
     * @var list<string>
     */
    public array $requestHeaders = [];

    public function __construct()
    {
        parent::__construct('http://example.test/xmlrpc');
    }

    public function setResponse(int $status, string $message, string $content): void
    {
        $this->responseStatus   = $status;
        $this->responseMessage = $message;
        $this->responseContent = $content;
    }

    protected function doRequest(): bool
    {
        $this->requestHeaders = $this->buildRequest();
        $this->status         = $this->responseStatus;
        $this->status_string  = $this->responseMessage;
        $this->content        = $this->responseContent;

        return true;
    }
}
