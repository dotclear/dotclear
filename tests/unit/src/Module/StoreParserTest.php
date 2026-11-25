<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\StoreParser;
use Dotclear\App;
use Exception;
use PHPUnit\Framework\TestCase;

class StoreParserTest extends TestCase
{
    public function testParsesSupportedModulesAndLegacyArray(): void
    {
        $parser = new StoreParser(
            '<modules>' .
            '<module id="sample" xmlns:da="http://dotaddict.org/da/">' .
            '<file>sample.zip</file><name>Sample</name><version>1.2</version>' .
            '<author>Dotclear</author><desc>A sample module</desc><date>2026-01-01</date>' .
            '<da:dcmin>0</da:dcmin><da:details>More information</da:details>' .
            '<da:section>Tools</da:section><da:support>https://example.test</da:support>' .
            '<da:sshot>shot.png</da:sshot><da:tags><da:tag>utilities</da:tag></da:tags>' .
            '</module><module><name>Missing ID</name></module></modules>'
        );

        $defines = $parser->getDefines();
        $this->assertCount(1, $defines);
        $this->assertSame('sample', $defines[0]->getId());
        $this->assertSame('Sample', $defines[0]->get('name'));
        $this->assertSame('sample.zip', $defines[0]->get('file'));
        $this->assertSame('More information', $defines[0]->get('details'));
        $this->assertSame('Tools', $defines[0]->get('section'));
        $this->assertSame('utilities', $defines[0]->get('tags'));
        $this->assertSame(['sample'], array_keys($parser->getModules()));
        $this->assertSame('1.2', $parser->getModules()['sample']['version']);
    }

    public function testEmptyAndUnsupportedFeedsAndMalformedInput(): void
    {
        $this->assertSame([], (new StoreParser('<modules/>'))->getDefines());
        $this->assertSame([], (new StoreParser('<feed/>'))->getDefines());
        $unsupported = (new StoreParser(
            '<modules><module id="future" xmlns:da="http://dotaddict.org/da/">' .
            '<name>Future</name><da:dcmin>99.0</da:dcmin></module></modules>'
        ))->getDefines();
        $this->assertSame(App::config()->devMode() ? ['future'] : [], array_map(
            static fn (ModuleDefine $define): string => $define->getId(),
            $unsupported
        ));

        set_error_handler(static fn (int $errno, string $message): bool => true);
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Wrong data feed.');
            new StoreParser('<broken>');
        } finally {
            restore_error_handler();
        }
    }

    public function testLegacyFeedWithoutDotAddictNamespaceAndMissingIdentifiers(): void
    {
        $parser = new StoreParser(
            '<modules>' .
            '<module><name>Ignored</name></module>' .
            '<module id="legacy"><name>Legacy</name><version>1.0</version><author>Author</author></module>' .
            '</modules>'
        );

        $this->assertCount(1, $parser->getDefines());
        $define = $parser->getDefines()[0];
        $this->assertSame('legacy', $define->getId());
        $this->assertSame('Legacy', $define->get('label'));
        $this->assertSame('', $define->get('dc_min'));
        $this->assertSame('', $define->get('tags'));
        $this->assertSame(['legacy'], array_keys($parser->getModules()));
    }
}
