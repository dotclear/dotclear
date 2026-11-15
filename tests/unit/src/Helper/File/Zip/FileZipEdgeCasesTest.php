<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\File\Zip;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\File\Zip\Zip;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FileZipEdgeCasesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dotclear-zip-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        Files::deltree($this->directory);
    }

    public function testZipValidationAndNameHelpers(): void
    {
        $stream = fopen('php://temp', 'w+b');
        $zip    = new FileZipEdgeCasesZipTester($stream);

        $this->assertSame('', $zip->format(null));
        $this->assertSame('folder/file.txt', $zip->format('/folder/file.txt'));
        $this->assertSame('folder/file.txt', $zip->format('folder/file.txt'));
        $this->assertSame(33, $zip->date((int) strtotime('1970-01-01 UTC')));

        $zip->addExclusion('/secret/');
        $this->assertFalse($zip->excluded(null));
        $this->assertFalse($zip->excluded('public.txt'));
        $this->assertTrue($zip->excluded('secret.txt'));
        $zip->addFile($this->directory . '/missing.txt', 'secret.txt');

        fclose($stream);
    }

    public function testZipRejectsMissingInputs(): void
    {
        $stream = fopen('php://temp', 'w+b');
        $zip    = new Zip($stream);

        try {
            $zip->addFile($this->directory . '/missing.txt');
            $this->fail('Expected missing file to be rejected.');
        } catch (Exception $exception) {
            $this->assertSame('File does not exist.', $exception->getMessage());
        }

        try {
            $zip->addDirectory($this->directory . '/missing', 'missing', true);
            $this->fail('Expected missing directory to be rejected.');
        } catch (Exception $exception) {
            $this->assertSame('Directory does not exist.', $exception->getMessage());
        }

        fclose($stream);
    }

    public function testArchiveRoundTripAndUnzip(): void
    {
        $archive = $this->createArchive();
        $unzip   = new Unzip($archive);

        $this->assertIsArray($unzip->getList());
        $this->assertSame('payload', $unzip->unzip('folder/file.txt'));
        $this->assertSame('', $unzip->unzip('folder/empty.txt'));
        $this->assertSame(['folder/file.txt', 'folder/empty.txt'], $unzip->getFilesList());
        $this->assertSame(['folder'], $unzip->getDirsList());
        $this->assertSame('folder', $unzip->getRootDir());
        $this->assertFalse($unzip->isEmpty());
        $this->assertTrue($unzip->hasFile('folder/file.txt'));
        $this->assertFalse($unzip->hasFile('missing.txt'));

        $target = $this->directory . '/nested/output.txt';
        $this->assertTrue($unzip->unzip('folder/file.txt', $target));
        $this->assertSame('payload', file_get_contents($target));

        $unzip->setExcludePattern('/file\.txt$/');
        $this->assertNull($unzip->unzip('folder/file.txt'));
        $unzip->close();
    }

    public function testUnzipValidationErrors(): void
    {
        $unzip = new Unzip($this->createArchive());
        $this->assertIsArray($unzip->getList());

        $this->assertExceptionMessage(
            'File missing.txt is not compressed in the zip.',
            fn () => $unzip->unzip('missing.txt')
        );
        $this->assertExceptionMessage(
            'Trying to unzip a folder name folder/.',
            fn () => $unzip->unzip('folder/')
        );

        $unzip->setExcludePattern('/file\.txt$/');
        $this->assertNull($unzip->unzip('folder/file.txt'));
        $unzip->close();
    }

    #[DataProvider('dataProviderUnsupportedCompression')]
    public function testUnsupportedCompressionMethods(int $method, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        (new FileZipEdgeCasesUnzipTester($this->directory . '/unused.zip'))->uncompress('', $method, 0);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderUnsupportedCompression(): array
    {
        return [
            'shrunk' => [1, 'Shrunk mode is not supported.'],
            'factor one' => [2, 'Compression factor 1 is not supported.'],
            'factor two' => [3, 'Compression factor 2 is not supported.'],
            'factor three' => [4, 'Compression factor 3 is not supported.'],
            'factor four' => [5, 'Compression factor 4 is not supported.'],
            'implode' => [6, 'Implode is not supported.'],
            'tokenizing' => [7, 'Tokenizing compression algorithm is not supported.'],
            'enhanced deflate' => [9, 'Enhanced Deflating is not supported.'],
            'pkware' => [10, 'PKWARE Date Compression Library Impoloding is not supported.'],
            'ibm terse' => [18, 'IBM TERSE is not supported.'],
            'unknown' => [99, 'Unknown uncompress method'],
        ];
    }

    public function testUncompressSupportedMethodsAndMalformedArchive(): void
    {
        $unzip = new FileZipEdgeCasesUnzipTester($this->directory . '/unused.zip');

        $this->assertSame('plain', $unzip->uncompress('plain', 0, 5));
        $this->assertSame('deflated', $unzip->uncompress(gzdeflate('deflated'), 8, 8));
        $this->assertSame('bzip', $unzip->uncompress(bzcompress('bzip'), 12, 4));

        $this->assertExceptionMessage(
            'Unable to write destination file.',
            fn () => $unzip->uncompress('plain', 0, 5, $this->directory)
        );
        $this->expectException(Exception::class);
        $unzip->uncompress('invalid', 12, 8);
    }

    public function testMalformedArchiveList(): void
    {
        $badArchive = $this->directory . '/bad.zip';
        file_put_contents($badArchive, 'not a zip');
        $invalid = new Unzip($badArchive);
        $this->assertFalse($invalid->getList());
        $this->assertTrue($invalid->isEmpty());
        $invalid->close();

        $missing = new Unzip($this->directory . '/missing.zip');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to open file.');
        $missing->getList();
    }

    private function createArchive(): string
    {
        $source = $this->directory . '/source.txt';
        file_put_contents($source, 'payload');
        file_put_contents($this->directory . '/empty.txt', '');

        $archive = $this->directory . '/archive.zip';
        $stream  = fopen($archive, 'wb');
        $zip     = new Zip($stream);
        $zip->addDirectory($this->directory, 'folder');
        $zip->addFile($source, 'folder/file.txt');
        $zip->addFile($this->directory . '/empty.txt', 'folder/empty.txt');
        $zip->write();
        fclose($stream);
        $zip->close();

        return $archive;
    }

    private function assertExceptionMessage(string $message, callable $callback): void
    {
        try {
            $callback();
        } catch (Exception $exception) {
            $this->assertSame($message, $exception->getMessage());

            return;
        }

        $this->fail(sprintf('Expected exception with message "%s".', $message));
    }
}

class FileZipEdgeCasesZipTester extends Zip
{
    public function format(?string $name): string
    {
        return $this->formatName($name);
    }

    public function excluded(?string $name): bool
    {
        return $this->isExcluded($name);
    }

    public function date(int $timestamp): int|float
    {
        return $this->makeDate($timestamp);
    }
}

class FileZipEdgeCasesUnzipTester extends Unzip
{
    public function uncompress(string $content, int $method, int $size, bool|string $target = false): true|string
    {
        return parent::uncompress($content, $method, $size, $target);
    }
}
