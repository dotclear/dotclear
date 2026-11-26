<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\Modules;
use Dotclear\Module\Store;
use Dotclear\Module\StoreParser;
use Dotclear\Module\StoreReader;
use Dotclear\Interface\Module\ModulesInterface;
use Exception;
use PHPUnit\Framework\TestCase;

class FixtureStore extends Store
{
    public function userAgent(): string
    {
        return $this->user_agent;
    }

    /**
     * @param list<ModuleDefine> $new
     * @param list<ModuleDefine> $updates
     */
    public function setResults(array $new, array $updates = []): void
    {
        $this->defines = ['new' => $new, 'update' => $updates];
        foreach (['new' => $new, 'update' => $updates] as $type => $defines) {
            foreach ($defines as $define) {
                $this->data[$type][$define->getId()] = $define->dump();
            }
        }
    }
}

class FixtureStoreModules extends Modules
{
    public static string $installedPackage = '';

    public function addDefine(ModuleDefine $define): void
    {
        $this->defines[] = $define;
    }

    public static function installPackage(string $zip_file, ModulesInterface &$modules): int
    {
        self::$installedPackage = $zip_file;

        return self::PACKAGE_INSTALLED;
    }
}

class StoreTest extends TestCase
{
    private function cacheParser(string $url, StoreParser $parser): string
    {
        $hash = md5($url);
        $file = sprintf(
            '%s/dcrepo/%s/%s/%s.ser',
            \Dotclear\App::config()->cacheRoot(),
            substr($hash, 0, 2),
            substr($hash, 2, 2),
            $hash
        );
        mkdir(dirname($file), 0777, true);
        file_put_contents($file, serialize($parser));

        return $file;
    }

    private function removeCacheFile(string $file): void
    {
        if (is_file($file)) {
            unlink($file);
        }

        $directory = dirname($file);
        while ($directory !== \Dotclear\App::config()->cacheRoot() . '/dcrepo'
            && str_starts_with($directory, \Dotclear\App::config()->cacheRoot() . '/dcrepo/')
        ) {
            if (!@rmdir($directory)) {
                break;
            }
            $directory = dirname($directory);
        }
    }

    public function testSearchRankingAndCompatibilityResults(): void
    {
        $modules = $this->createStub(ModulesInterface::class);
        $store = new FixtureStore($modules, null);

        $best = (new ModuleDefine('plugin-search'))
            ->set('name', 'Search toolkit')
            ->set('desc', 'Search metadata')
            ->set('tags', 'search tools')
            ->set('author', 'Dotclear');
        $other = (new ModuleDefine('plugin-export'))
            ->set('name', 'Search extension')
            ->set('desc', 'Create archives');
        $store->setResults([$other, $best], [$best]);

        $this->assertSame([], $store->searchDefines('x +'));
        $matches = $store->searchDefines('search');
        $this->assertSame([$best, $other], $matches);
        $this->assertGreaterThan($other->get('score'), $best->get('score'));
        $this->assertSame([$other, $best], $store->getDefines());
        $this->assertSame([$best], $store->getDefines(true));
        $this->assertSame(['plugin-export', 'plugin-search'], array_keys($store->get()));
        $this->assertSame(['plugin-search'], array_keys($store->get(true)));
        $this->assertFalse($store->hasNewUdpates());
    }

    public function testEmptyQueryAndNullRepositoryAreHandled(): void
    {
        $modules = $this->createStub(ModulesInterface::class);
        $store = new Store($modules, null);
        $this->assertFalse($store->check());

        $store = new FixtureStore($modules, 'https://repository.example.test/dcstore.xml');
        $store->setResults([(new ModuleDefine('module'))->set('name', 'Module')]);

        $this->assertSame([], $store->searchDefines(''));
    }

    public function testCheckSeparatesNewModulesAndUpdatesFromCachedFeed(): void
    {
        $url = 'https://repository.example.test/' . bin2hex(random_bytes(8)) . '.xml';
        $cacheFiles = [$this->cacheParser($url, new StoreParser(
            '<modules>' .
            '<module id="fresh"><name>Fresh</name><version>1.0</version></module>' .
            '<module id="upgrade"><name>Upgrade</name><version>2.0</version></module>' .
            '<module id="stale"><name>Stale</name><version>1.0</version></module>' .
            '</modules>'
        ))];
        $upgradeRepository = 'https://upgrade-repo.example.test/dcstore.xml';
        $cacheFiles[]       = $this->cacheParser($upgradeRepository, new StoreParser(
            '<modules><module id="upgrade"><name>Upgrade</name><version>3.0</version></module></modules>'
        ));
        $externalRepository = 'https://third-party.example.test';
        $cacheFiles[]       = $this->cacheParser($externalRepository . '/dcstore.xml', new StoreParser(
            '<modules><module id="external"><name>External</name><version>1.1</version></module></modules>'
        ));

        try {
            $modules = new FixtureStoreModules();
            $modules->addDefine((new ModuleDefine('upgrade'))
                ->set('name', 'Installed upgrade')
                ->set('version', '1.0')
                ->set('root', '/modules/upgrade')
                ->set('root_writable', true)
                ->set('repository', $upgradeRepository));
            $modules->addDefine((new ModuleDefine('stale'))
                ->set('name', 'Installed stale')
                ->set('version', '2.0'));
            $modules->addDefine((new ModuleDefine('external'))
                ->set('name', 'Installed external')
                ->set('version', '1.0')
                ->set('repository', $externalRepository));

            $store = new Store($modules, $url, null, false, true);

            $this->assertSame(['fresh'], array_map(static fn (ModuleDefine $define): string => $define->getId(), $store->getDefines()));
            $updates = array_values($store->getDefines(true));
            $this->assertCount(2, $updates);
            $this->assertSame(['external', 'upgrade'], array_map(static fn (ModuleDefine $define): string => $define->getId(), $updates));
            $this->assertSame('1.0', $updates[1]->get('current_version'));
            $this->assertSame('/modules/upgrade', $updates[1]->get('root'));
            $this->assertTrue($updates[1]->get('root_writable'));
            $this->assertSame('3.0', $updates[1]->get('version'));
            $this->assertTrue($updates[1]->get('repository'));
            $this->assertSame('1.0', $updates[0]->get('current_version'));
            $this->assertSame('1.1', $updates[0]->get('version'));
            $this->assertTrue($updates[0]->get('repository'));
            $this->assertArrayHasKey('fresh', $store->get());
            $this->assertArrayHasKey('upgrade', $store->get(true));
            $this->assertArrayHasKey('external', $store->get(true));
            $this->assertFalse($store->hasNewUdpates());
            $this->assertSame(StoreReader::READ_FROM_CACHE, StoreReader::readCode());
        } finally {
            foreach ($cacheFiles as $file) {
                $this->removeCacheFile($file);
            }
        }
    }

    public function testDeprecatedSearchAndInstallCompatibilityMethods(): void
    {
        $modules = new FixtureStoreModules();
        $store   = new FixtureStore($modules, null);
        $module  = (new ModuleDefine('plugin-search'))->set('name', 'Search extension');
        $store->setResults([$module]);

        $this->assertSame(['plugin-search'], array_keys($store->search('search')));

        $store->agent('Unit test agent');
        $this->assertSame('Unit test agent', $store->userAgent());
        $this->assertSame(Modules::PACKAGE_INSTALLED, $store->install('/tmp/module-package.zip'));
        $this->assertSame('/tmp/module-package.zip', FixtureStoreModules::$installedPackage);
    }

    public function testMalformedDownloadUrlRaisesTheStandardError(): void
    {
        $store = new Store($this->createStub(ModulesInterface::class), null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('An error occurred while downloading the file.');
        $store->download('::invalid::', '/tmp/unused-module.zip');
    }

    public function testProcessStopsWhenDownloadFails(): void
    {
        $store = new Store($this->createStub(ModulesInterface::class), null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('An error occurred while downloading the file.');
        $store->process('::invalid::', '/tmp/unused-module.zip');
    }
}
