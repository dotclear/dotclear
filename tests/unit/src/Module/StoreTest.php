<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\Store;
use Dotclear\Interface\Module\ModulesInterface;
use Exception;
use PHPUnit\Framework\TestCase;

class FixtureStore extends Store
{
    public function check(?bool $force = false, bool $use_host_cache = true, bool $use_cache_only = true): bool
    {
        $this->defines = ['new' => [], 'update' => []];
        $this->data = ['new' => [], 'update' => []];

        return true;
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

class StoreTest extends TestCase
{
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

    public function testMalformedDownloadUrlRaisesTheStandardError(): void
    {
        $store = new Store($this->createStub(ModulesInterface::class), null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('An error occurred while downloading the file.');
        $store->download('::invalid::', '/tmp/unused-module.zip');
    }
}
