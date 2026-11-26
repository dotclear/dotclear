<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\StoreParser;
use Dotclear\Module\StoreReader;
use PHPUnit\Framework\TestCase;

class FixtureStoreReader extends StoreReader
{
    public bool $fetchSucceeds = true;
    public int $responseStatus = 200;
    public string $responseContent = '<modules/>';
    public int $fetchCount = 0;
    public array $logs = [];

    protected function getModulesXML(string $url): bool
    {
        ++$this->fetchCount;

        return $this->fetchSucceeds;
    }

    public function getStatus(): int
    {
        return $this->responseStatus;
    }

    public function getContent(): string
    {
        return $this->responseContent;
    }

    /**
     * @return list<string>
     */
    public function requestHeaders(): array
    {
        return $this->buildRequest();
    }

    public function seedHost(string $host, int $timeout, int $failedAt): void
    {
        static::$hosts[$host] = [$timeout, $failedAt];
    }

    public function hasHost(string $host): bool
    {
        return array_key_exists($host, static::$hosts);
    }

    protected function log(string $message): void
    {
        $this->logs[] = $message;
    }
}

class FixtureNetworkStoreReader extends StoreReader
{
    public array $logs = [];

    protected function log(string $message): void
    {
        $this->logs[] = $message;
    }
}

class StoreReaderTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/store-reader-' . bin2hex(random_bytes(6));
        mkdir($this->cacheDirectory);
        StoreReader::resetHostsList();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDirectory)) {
            $this->removeDirectory($this->cacheDirectory);
        }
        StoreReader::resetHostsList();
    }

    public function testCacheConfigurationAndNetworkResponseParsing(): void
    {
        $reader = new FixtureStoreReader(false, false);
        $this->assertTrue($reader->setCacheDir($this->cacheDirectory));
        $this->assertFalse($reader->setCacheDir($this->cacheDirectory . '/missing'));
        $reader->setCacheDir($this->cacheDirectory);
        $reader->setCacheTTL(' 5 minutes ');
        $reader->setForce(false);

        $reader->responseContent = '<modules><module id="sample"><name>Sample</name><version>1.0</version></module></modules>';
        $parsed = $reader->parse('https://repository.example.test/dcstore.xml');
        $this->assertInstanceOf(StoreParser::class, $parsed);
        $this->assertSame(['sample'], array_map(static fn ($define): string => $define->getId(), $parsed->getDefines()));
        $this->assertSame(1, $reader->fetchCount);
        $this->assertSame(StoreReader::READ_FROM_SOURCE, StoreReader::readCode());
    }

    public function testCacheOnlyMissAndCacheHit(): void
    {
        $url = 'https://repository.example.test/dcstore.xml';
        $reader = new FixtureStoreReader(false, true);
        $this->assertTrue($reader->setCacheDir($this->cacheDirectory));
        $reader->setForce(null);

        $this->assertFalse($reader->parse($url));
        $this->assertSame(0, $reader->fetchCount);

        $parser = new StoreParser('<modules><module id="cached"><name>Cached</name><version>1</version></module></modules>');
        $hash = md5($url);
        $cachedFile = sprintf(
            '%s/dcrepo/%s/%s/%s.ser',
            $this->cacheDirectory,
            substr($hash, 0, 2),
            substr($hash, 2, 2),
            $hash
        );
        mkdir(dirname($cachedFile), 0777, true);
        file_put_contents($cachedFile, serialize($parser));

        $cached = $reader->parse($url);
        $this->assertInstanceOf(StoreParser::class, $cached);
        $this->assertSame('cached', $cached->getDefines()[0]->getId());
        $this->assertSame(StoreReader::READ_FROM_CACHE, StoreReader::readCode());
        $this->assertSame(0, $reader->fetchCount);

        $quickCached = StoreReader::quickParse($url, $this->cacheDirectory, null, false, true);
        $this->assertInstanceOf(StoreParser::class, $quickCached);
        $this->assertSame('cached', $quickCached->getDefines()[0]->getId());
    }

    public function testMissingCacheAndUnsuccessfulNetworkResponse(): void
    {
        $reader = new FixtureStoreReader(false, false);
        $reader->fetchSucceeds = false;
        $this->assertFalse($reader->parse('https://repository.example.test/missing.xml'));

        $reader = new FixtureStoreReader(false, false);
        $reader->fetchSucceeds = false;
        $this->assertFalse($reader->parse('https://repository.example.test/unavailable.xml'));
        $this->assertSame(1, $reader->fetchCount);

        $reader = new FixtureStoreReader(false, false);
        $reader->responseStatus = 404;
        $this->assertFalse($reader->parse('https://repository.example.test/not-found.xml'));
    }

    public function testExpiredCache304AndFailedRefreshFallback(): void
    {
        $url = 'https://repository.example.test/refresh.xml';
        $parser = new StoreParser('<modules><module id="cached"><name>Cached</name><version>1</version></module></modules>');
        $cachedFile = $this->cacheFile($url);
        mkdir(dirname($cachedFile), 0777, true);
        file_put_contents($cachedFile, serialize($parser));
        touch($cachedFile, 1);

        $reader = new FixtureStoreReader(false, false);
        $reader->setCacheDir($this->cacheDirectory);
        $reader->setCacheTTL('1 minute');
        $reader->responseStatus = 304;

        $cached = $reader->parse($url);
        $this->assertInstanceOf(StoreParser::class, $cached);
        $this->assertSame('cached', $cached->getDefines()[0]->getId());
        $this->assertSame(StoreReader::READ_FROM_CACHE, StoreReader::readCode());
        $this->assertNotEmpty(array_filter($reader->requestHeaders(), static fn (string $header): bool => str_starts_with($header, 'If-Modified-Since:')));

        touch($cachedFile, 1);
        $reader = new FixtureStoreReader(false, false);
        $reader->setCacheDir($this->cacheDirectory);
        $reader->setCacheTTL('1 minute');
        $reader->fetchSucceeds = false;
        $cached = $reader->parse($url);
        $this->assertInstanceOf(StoreParser::class, $cached);
        $this->assertSame('cached', $cached->getDefines()[0]->getId());
        $this->assertSame(StoreReader::READ_FROM_CACHE, StoreReader::readCode());
    }

    public function testSuccessfulRefreshWritesCacheAndInvalidCacheIsIgnored(): void
    {
        $url = 'https://repository.example.test/new.xml';
        $reader = new FixtureStoreReader(false, false);
        $reader->setCacheDir($this->cacheDirectory);
        $reader->responseContent = '<modules><module id="fresh"><name>Fresh</name><version>2</version></module></modules>';

        $parsed = $reader->parse($url);
        $this->assertInstanceOf(StoreParser::class, $parsed);
        $this->assertSame('fresh', $parsed->getDefines()[0]->getId());
        $this->assertFileExists($this->cacheFile($url));

        $invalidUrl = 'https://repository.example.test/invalid-cache.xml';
        $invalidFile = $this->cacheFile($invalidUrl);
        mkdir(dirname($invalidFile), 0777, true);
        file_put_contents($invalidFile, serialize('not a parser'));
        $cacheReader = new FixtureStoreReader(false, true);
        $cacheReader->setCacheDir($this->cacheDirectory);
        $cacheReader->setForce(null);
        $this->assertFalse($cacheReader->parse($invalidUrl));
        $this->assertSame(0, $cacheReader->fetchCount);
    }

    public function testHostBackoffSkipsRequestsExpiresAndClearsAfterSuccess(): void
    {
        $host = 'repository.example.test';
        $url  = 'https://' . $host . '/host-cache.xml';

        $reader = new FixtureStoreReader(true, false);
        $reader->seedHost($host, 0, time());
        $this->assertFalse($reader->parse($url));
        $this->assertSame(0, $reader->fetchCount);
        $this->assertContains("Ignore {$host} host request (timeout is 0, wait for end of grace period)", $reader->logs);

        $reader = new FixtureStoreReader(true, false);
        $reader->seedHost($host, 5, time() - 7201);
        $reader->fetchSucceeds = false;
        $this->assertFalse($reader->parse($url));
        $this->assertSame(1, $reader->fetchCount);
        $this->assertNotEmpty(array_filter($reader->logs, static fn (string $message): bool => str_contains($message, 'grace period expired')));

        $reader = new FixtureStoreReader(true, false);
        $reader->seedHost($host, 5, time());
        $reader->responseContent = '<modules><module id="fresh"><name>Fresh</name><version>1</version></module></modules>';
        $this->assertInstanceOf(StoreParser::class, $reader->parse($url));
        $this->assertFalse($reader->hasHost($host));
    }

    public function testMalformedRepositoryUrlFailsBeforeMakingARequest(): void
    {
        $reader = new FixtureNetworkStoreReader(false, false);

        $this->assertFalse($reader->parse('not a valid repository URL'));
        $this->assertNotEmpty(array_filter(
            $reader->logs,
            static fn (string $message): bool => str_contains($message, 'Malformed repository XML feed URL')
        ));
    }

    private function cacheFile(string $url): string
    {
        $hash = md5($url);

        return sprintf(
            '%s/dcrepo/%s/%s/%s.ser',
            $this->cacheDirectory,
            substr($hash, 0, 2),
            substr($hash, 2, 2),
            $hash
        );
    }

    private function removeDirectory(string $directory): void
    {
        foreach (new \FilesystemIterator($directory) as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                $this->removeDirectory($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }

        rmdir($directory);
    }
}
