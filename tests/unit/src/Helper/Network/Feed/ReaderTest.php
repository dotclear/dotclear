<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\Feed;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Network\Feed\Parser;
use Dotclear\Helper\Network\Feed\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    private string $cacheDirectory;
    private string $feed;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'feed-reader-' . bin2hex(random_bytes(6));
        mkdir($this->cacheDirectory);
        $fixture = (string) realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Feed', 'atom-1.0.xml']));
        $this->feed = (string) file_get_contents($fixture);
    }

    protected function tearDown(): void
    {
        Files::deltree($this->cacheDirectory);
    }

    public function testParseAndStatusFailures(): void
    {
        $reader = new ReaderTester();
        $reader->setResponse(true, 200, $this->feed);

        $parser = $reader->parse('https://example.test/feed');

        $this->assertInstanceOf(Parser::class, $parser);
        $this->assertSame('Example Feed', $parser->title);
        $this->assertSame(1, $reader->requestCount);

        $reader->setResponse(true, 404, $this->feed);
        $this->assertFalse($reader->parse('https://example.test/missing'));

        $reader->setResponse(false, 0, '');
        $this->assertFalse($reader->parse('https://example.test/offline'));
    }

    public function testCacheFreshExpiredAndFallbackPaths(): void
    {
        $url    = 'https://example.test/cached';
        $reader = new ReaderTester();
        $reader->setCacheDir($this->cacheDirectory);
        $cacheFile = $this->cacheFile($url);

        file_put_contents($cacheFile, $this->feed);
        $reader->setResponse(false, 0, '');
        $parser = $reader->parse($url);
        $this->assertInstanceOf(Parser::class, $parser);
        $this->assertSame(0, $reader->requestCount);

        touch($cacheFile, time() - 3600);
        clearstatcache(true, $cacheFile);
        $reader->setResponse(true, 304, '');
        $parser = $reader->parse($url);
        $this->assertInstanceOf(Parser::class, $parser);
        $this->assertCount(1, array_filter($reader->lastRequestHeaders, static fn (string $header): bool => str_starts_with($header, 'If-Modified-Since:')));

        touch($cacheFile, time() - 3600);
        clearstatcache(true, $cacheFile);
        $reader->setResponse(false, 0, '');
        $this->assertInstanceOf(Parser::class, $reader->parse($url));
    }

    public function testCacheRefreshAndInvalidDirectory(): void
    {
        $url    = 'https://example.test/refresh';
        $reader = new ReaderTester();
        $this->assertTrue($reader->setCacheDir($this->cacheDirectory));
        $this->assertFalse($reader->setCacheDir($this->cacheDirectory . '/missing'));
        $this->assertFalse($reader->setCacheDir(''));
        $reader->setResponse(false, 0, '');
        $this->assertFalse($reader->parse($url));

        $this->assertTrue($reader->setCacheDir($this->cacheDirectory));
        $reader->setCacheTTL('4 hours');
        $reader->setCacheTTL('');
        $reader->setResponse(true, 200, $this->feed);

        $parser = $reader->parse($url);
        $this->assertInstanceOf(Parser::class, $parser);
        $this->assertFileExists($this->cacheFile($url));
        $this->assertInstanceOf(Parser::class, Reader::quickParse($url, $this->cacheDirectory));

        file_put_contents($this->cacheFile($url), '');
        $reader->setResponse(true, 500, '');
        $this->assertFalse($reader->parse($url));
        $this->assertFileDoesNotExist($this->cacheFile($url));
    }

    public function testQuickParseRejectsInvalidUrl(): void
    {
        $this->assertFalse(Reader::quickParse('not-a-url'));
    }

    public function testFeedFetchUsesParsedUrl(): void
    {
        $reader = new ReaderTransportTester($this->feed);
        $parser = $reader->parse('https://user:pass@example.test:8443/path?query=1');

        $this->assertInstanceOf(Parser::class, $parser);
        $this->assertSame('example.test', $reader->requestedHost);
        $this->assertSame(8443, $reader->requestedPort);
        $this->assertSame('/path?query=1', $reader->requestedPath);
        $this->assertTrue($reader->requestedSSL);
        $this->assertSame('user', $reader->requestedUsername);
        $this->assertSame('pass', $reader->requestedPassword);
    }

    private function cacheFile(string $url): string
    {
        $hash      = md5($url);
        $directory = implode(DIRECTORY_SEPARATOR, [$this->cacheDirectory, Reader::CACHE_FOLDER, substr($hash, 0, 2), substr($hash, 2, 2)]);
        Files::makeDir($directory, true);

        return $directory . DIRECTORY_SEPARATOR . $hash . '.xml';
    }
}

class ReaderTester extends Reader
{
    public bool $connected = true;
    public int $responseStatus = 200;
    public string $responseContent = '';
    public int $requestCount = 0;

    /**
     * @var list<string>
     */
    public array $lastRequestHeaders = [];

    public function setResponse(bool $connected, int $status, string $content): void
    {
        $this->connected       = $connected;
        $this->responseStatus  = $status;
        $this->responseContent = $content;
    }

    protected function getFeed(string $url): bool
    {
        $this->requestCount++;
        $this->setHost('example.test');
        $this->setAuthorization(null, null);
        $this->method = 'GET';
        $this->path   = '/feed';
        $this->lastRequestHeaders = $this->buildRequest();
        $this->status  = $this->responseStatus;
        $this->content = $this->responseContent;

        return $this->connected;
    }
}

class ReaderTransportTester extends Reader
{
    public string $requestedHost = '';
    public int $requestedPort = 0;
    public string $requestedPath = '';
    public bool $requestedSSL = false;
    public string $requestedUsername = '';
    public string $requestedPassword = '';

    public function __construct(private string $responseContent)
    {
        parent::__construct();
    }

    public function get(string $path, $data = false): bool
    {
        $this->requestedHost     = $this->host;
        $this->requestedPort     = $this->port;
        $this->requestedPath     = $path;
        $this->requestedSSL      = $this->use_ssl;
        $this->requestedUsername = $this->username;
        $this->requestedPassword = $this->password;
        $this->status            = 200;
        $this->content           = $this->responseContent;

        return true;
    }
}
