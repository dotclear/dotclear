<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\File\Image;

use Dotclear\Helper\File\Image\SvgSanitizer;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    private SvgSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new SvgSanitizer();
    }

    public function testSanitizesSuppliedMaliciousSvg(): void
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert('XSS 1')" width="200" height="200">
    <script>alert('XSS 2');</script>
    <rect x="10" y="10" width="100" height="100" fill="blue" onclick="stealCookies()" />
    <a xlink:href="javascript:alert('XSS 3')">
        <text x="20" y="50" fill="white">Cliquez ici</text>
    </a>
    <foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><iframe src="https://site-malveillant.com"></iframe></body></foreignObject>
</svg>
SVG;

        $clean = $this->sanitizer->sanitize($svg);

        self::assertIsString($clean);
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($clean));
        $xpath = new DOMXPath($document);
        self::assertSame(0, $xpath->query('//*[local-name()="script" or local-name()="foreignObject" or local-name()="iframe"]')->length);
        self::assertSame(0, $xpath->query('//@*[starts-with(translate(name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "on")]')->length);
        self::assertSame(0, $xpath->query('//@*[local-name()="href"]')->length);
        self::assertSame(1, $xpath->query('//*[local-name()="rect"]')->length);
        self::assertSame('Cliquez ici', $xpath->query('//*[local-name()="text"]')->item(0)?->textContent);
        self::assertSame('200', $xpath->query('/*')->item(0)?->attributes?->getNamedItem('width')?->nodeValue);
    }

    public function testPreservesSafeSvgSourceUnchanged(): void
    {
        $svg = <<<'SVG'
<svg clip-rule="evenodd" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="1.5" viewBox="0 0 65 66" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><clipPath id="a"><path clip-rule="evenodd" d="m0 0h65v66h-65z"/></clipPath><g clip-path="url(#a)" transform="translate(-.001)"><g stroke="#c18187" stroke-width="4"><path d="m313.935 238.601 61-.243z" fill="none" transform="translate(-311.935 -175.129)"/><path d="m320.52 230.114 9.068-17.985h30.183l8.58 18.156z" fill="#c18187" transform="translate(-311.935 -176.129)"/><path d="m333.52 230.114 11.068-23.985 11.763 24.156z" fill="#c18187" transform="translate(-311.935 -204.129)"/></g><path d="m.145-.234-.061-.349c-.001-.006-.001-.012-.001-.018 0-.05.033-.075.098-.075.066 0 .098.025.098.074 0 .007 0 .013-.001.019l-.06.349zm.036.071c.057 0 .086.029.086.086 0 .058-.029.086-.086.086s-.086-.028-.086-.086c0-.057.029-.086.086-.086z" style="fill:#696e76;fill-rule:nonzero;stroke:#fff;stroke-width:.02;stroke-linecap:butt;stroke-miterlimit:2" transform="matrix(48 0 0 48 23.531 48.336)"/></g></svg>
SVG;

        $clean = $this->sanitizer->sanitize($svg);
        self::assertIsString($clean);
        $sourceDocument = new DOMDocument();
        $cleanDocument  = new DOMDocument();
        self::assertTrue($sourceDocument->loadXML($svg));
        self::assertTrue($cleanDocument->loadXML($clean));
        // libxml reorders namespace declarations when serializing, so compare canonical XML.
        self::assertSame($sourceDocument->C14N(), $cleanDocument->C14N());
    }

    public function testRejectsEmptyMalformedAndNonSvgInput(): void
    {
        self::assertFalse($this->sanitizer->sanitize(''));
        self::assertFalse($this->sanitizer->sanitize('<svg>'));
        self::assertFalse($this->sanitizer->sanitize('<html/>'));
    }

    public function testRemovesDangerousUrisAndCssButKeepsSafeValues(): void
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <image href="data:image/png;base64,AAAA"/><image href="data:text/html;base64,AAAA"/>
  <a href=" java&#x09;script:alert(1)"/><path style="fill:blue;behavior:url(x)"/>
  <style>@import url(https://evil.test/x.css)</style><rect fill="red"/>
</svg>
SVG;
        $clean = $this->sanitizer->sanitize($svg);
        self::assertIsString($clean);
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($clean));
        $xpath = new DOMXPath($document);
        self::assertSame(1, $xpath->query('//*[@href]')->length);
        self::assertSame(0, $xpath->query('//*[local-name()="a"]/@href')->length);
        self::assertSame(0, $xpath->query('//*[local-name()="path"]/@style')->length);
        self::assertSame(0, $xpath->query('//*[local-name()="style"]')->length);
        self::assertSame(1, $xpath->query('//*[local-name()="rect"]')->length);
    }

    public function testRemovesAllForbiddenElementsAndExecutableUriSchemes(): void
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <SCRIPT/><iframe/><object/><embed/><applet/><meta/><link/><base/><foreignObject/>
  <a href="vbscript:run()"/><a xlink:href="data:image/svg+xml;base64,AAAA"/>
  <image src="data:image/svg+xml;base64,AAAA"/><form action="javascript:x"/><button formaction="vbscript:x"/>
  <object data="data:text/plain,x"/><video poster="data:text/plain,x"/>
  <rect style="fill:expression(x)"/><rect style="behavior:x"/><rect style="background:url(vbscript:x)"/>
  <style>p{fill:expression(x)}</style><style>p{behavior:x}</style><style>@import 'x'</style>
</svg>
SVG;
        $clean = $this->sanitizer->sanitize($svg);
        self::assertIsString($clean);
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($clean));
        $xpath = new DOMXPath($document);
        self::assertSame(0, $xpath->query('//*[local-name()="script" or local-name()="iframe" or local-name()="object" or local-name()="embed" or local-name()="applet" or local-name()="meta" or local-name()="link" or local-name()="base" or local-name()="foreignObject"]')->length);
        self::assertSame(0, $xpath->query('//@href | //@src | //@action | //@formaction | //@data | //@poster | //@style')->length);
        self::assertSame(0, $xpath->query('//*[local-name()="style"]')->length);
    }

    public function testSanitizeFileAndNewPath(): void
    {
        $directory = sys_get_temp_dir() . '/svg-sanitizer-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($directory));
        $source = $directory . '/source.svg';
        $target = $directory . '/clean.svg';
        file_put_contents($source, '<svg xmlns="http://www.w3.org/2000/svg" onload="x"><script>x</script><circle/></svg>');

        try {
            self::assertSame($target, $this->sanitizer->sanitizeFile($source, $target));
            self::assertStringNotContainsString('script', (string) file_get_contents($target));
            self::assertTrue($this->sanitizer->sanitizeFileToNewPath($source, $target));
            self::assertFalse($this->sanitizer->sanitizeFile($directory . '/missing.svg'));
            self::assertFalse($this->sanitizer->sanitizeFile($directory));
            self::assertFalse($this->sanitizer->sanitizeFile($source, $directory . '/missing/clean.svg'));
            file_put_contents($source, '<not-svg/>');
            self::assertFalse($this->sanitizer->sanitizeFile($source, $target));
        } finally {
            @unlink($source);
            @unlink($target);
            @rmdir($directory);
        }
    }
}
