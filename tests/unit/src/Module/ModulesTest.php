<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\Modules;
use PHPUnit\Framework\TestCase;

class FixtureModules extends Modules
{
    public bool $throwOnInstall = false;

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Register a fixture using the same lifecycle fields as directory loading.
     *
     * @param array<string, mixed> $properties
     */
    public function add(string $id, string $root, array $properties = []): void
    {
        $this->id = $id;
        $this->mroot = $root;
        $this->registerModule(
            (string) ($properties['name'] ?? $id),
            (string) ($properties['desc'] ?? ''),
            (string) ($properties['author'] ?? ''),
            (string) ($properties['version'] ?? '1.0'),
            $properties
        );
        $this->id = null;
        $this->mroot = null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function scan(array $paths): array
    {
        return $this->parsePathModules($paths);
    }

    public function includeFile(string $file, bool $globals = false, bool $catch = true): mixed
    {
        return $this->loadModuleFile($file, $globals, $catch);
    }

    protected function loadModuleFile(string $file, bool $globals = false, bool $catch = true): mixed
    {
        if ($this->throwOnInstall && str_ends_with($file, '/_install.php')) {
            throw new \RuntimeException('install failed');
        }

        return parent::loadModuleFile($file, $globals, $catch);
    }
}

class ModulesTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/modules-test-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);
        unset($GLOBALS['module_init_runs'], $GLOBALS['module_admin_loaded'], $GLOBALS['module_public_loaded']);
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

    private function createModuleArchive(string $archive, string $id, string $version): void
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($archive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $this->assertTrue($zip->addEmptyDir($id));
        $this->assertTrue($zip->addFromString(
            $id . '/_define.php',
            "<?php\n\$this->registerModule('" . $id . "', '', '', '" . $version . "');\n"
        ));
        $this->assertTrue($zip->close());
    }

    public function testDefinitionsDependenciesAndVersionComparisons(): void
    {
        $modules = new FixtureModules();
        $modules->add('dependency', $this->temporaryDirectory . '/dependency', [
            'name' => 'Dependency',
            'type' => 'plugin',
            'version' => '2.0',
            'state' => ModuleDefine::STATE_ENABLED,
        ]);
        $modules->add('consumer', $this->temporaryDirectory . '/consumer', [
            'name' => 'Consumer',
            'type' => 'plugin',
            'version' => '1.0',
            'state' => ModuleDefine::STATE_ENABLED,
            'requires' => [['dependency', '3.0'], ['optional?', '1.0'], ['php', '99.0']],
        ]);

        $this->assertSame(['dependency', 'consumer'], array_map(static fn (ModuleDefine $d): string => $d->getId(), $modules->getDefines()));
        $this->assertSame(['consumer' => $modules->getDefine('consumer')->dump()], $modules->getDefines(['id' => 'consumer'], true));
        $this->assertSame(['dependency'], array_map(
            static fn (ModuleDefine $d): string => $d->getId(),
            $modules->getDefines(['id' => '!consumer'])
        ));
        $this->assertSame('Consumer', $modules->getDefine('consumer')->get('name'));
        $this->assertFalse($modules->moduleExists('unknown'));
        $this->assertSame('1.0', $modules->moduleInfo('consumer', 'version'));

        $consumer = $modules->getDefine('consumer');
        $modules->checkDependencies($consumer, true);
        $this->assertSame(ModuleDefine::STATE_SOFT_DISABLED, $consumer->get('state'));
        $this->assertArrayHasKey('dependency', $consumer->getMissing());
        $this->assertArrayHasKey('PHP', $consumer->getMissing());
        $this->assertSame(['consumer'], $modules->getDefine('dependency')->getImplies());
        $this->assertCount(2, $modules->getErrors());
        $consumer->set('state', ModuleDefine::STATE_ENABLED);
        $modules->lockDepModules($modules->getDefine('dependency'));
        $this->assertSame(['consumer'], $modules->getDefine('dependency')->getUsing());

        $modules->getDefine('dependency')->set('state', ModuleDefine::STATE_INIT_DISABLED);
        $dependent = new ModuleDefine('another');
        $dependent->set('requires', [['dependency']]);
        $dependent->set('state', ModuleDefine::STATE_ENABLED);
        $modules->checkDependencies($dependent);
        $this->assertStringContainsString('disabled', $dependent->getMissing()['dependency']);

        $modules->safeMode(true);
        $safe = new ModuleDefine('safe');
        $safe->set('requires', [['absent']])->set('state', ModuleDefine::STATE_ENABLED);
        $modules->checkDependencies($safe);
        $this->assertSame(ModuleDefine::STATE_ENABLED, $safe->get('state'));
        $this->assertTrue($modules->safeMode());
        $this->assertTrue($modules->versionsCompare('1.0-r2', '1.0-p1'));
        $this->assertTrue($modules->versionsCompare('2.4.0-alpha', '2.4.0', '==', false));

        $modules->resetErrors();
        $this->assertSame([], $modules->getErrors());
        $modules->resetModulesList();
        $this->assertTrue($modules->isEmpty());
    }

    public function testDuplicateDefinitionsKeepTheNewestVersionAndNormalizeLegacyFields(): void
    {
        $roots = [];
        foreach (['first', 'same', 'newer'] as $name) {
            $roots[$name] = $this->temporaryDirectory . '/' . $name;
            mkdir($roots[$name]);
        }

        $modules = new FixtureModules();
        $modules->add('duplicate', $roots['first'], [
            'name'     => 'First',
            'version'  => '1.0',
            'priority' => -5,
            'requires' => [['core', '2.0']],
        ]);
        $this->assertSame(1, $modules->getDefine('duplicate')->get('priority'));
        $this->assertSame('2.0', $modules->getDefine('duplicate')->get('dc_min'));

        $modules->add('duplicate', $roots['same'], ['name' => 'Same version', 'version' => '1.0']);
        $this->assertSame('First', $modules->getDefine('duplicate')->get('name'));

        $modules->add('duplicate', $roots['newer'], ['name' => 'Newer', 'version' => '2.0']);
        $this->assertCount(1, $modules->getDefines());
        $this->assertSame('Newer', $modules->getDefine('duplicate')->get('name'));
        $this->assertCount(2, $modules->getErrors());
    }

    public function testFileScanningAndSafeModuleInclusion(): void
    {
        $path = $this->temporaryDirectory . '/modules';
        mkdir($path);
        mkdir($path . '/first');
        mkdir($path . '/ignored');
        file_put_contents($path . '/first/_define.php', '<?php');
        file_put_contents($path . '/ignored/readme.txt', 'not a module');

        $modules = new FixtureModules();
        $scan = $modules->scan([$path]);
        $this->assertSame([['first']], array_values($scan));
        $this->assertSame($scan, $modules->scan([$path]));

        $includeFile = $path . '/result.php';
        file_put_contents($includeFile, '<?php echo "discard"; return 42;');
        $this->assertSame(42, $modules->includeFile($includeFile));
        $this->assertNull($modules->includeFile($path . '/missing.php'));

        $throwingFile = $path . '/throw.php';
        file_put_contents($throwingFile, '<?php throw new RuntimeException("caught");');
        $this->assertNull($modules->includeFile($throwingFile));
        $this->assertNull($modules->includeFile($throwingFile, false, false));
    }

    public function testModuleActivationDeactivationAndDeletion(): void
    {
        $root = $this->temporaryDirectory . '/module';
        mkdir($root);
        file_put_contents($root . '/contents.txt', 'fixture');

        $modules = new FixtureModules();
        $modules->add('fixture', $root, [
            'name' => 'Fixture',
            'type' => 'plugin',
            'state' => ModuleDefine::STATE_ENABLED,
        ]);
        $this->assertTrue($modules->moduleExists('fixture'));

        $modules->deactivateModule('fixture');
        $this->assertFileExists($root . '/_disabled');
        $modules->getDefine('fixture')->set('state', ModuleDefine::STATE_SOFT_DISABLED);
        $this->assertFalse($modules->moduleExists('fixture'));

        $modules->activateModule('fixture');
        $this->assertFileDoesNotExist($root . '/_disabled');

        $modules->getDefine('fixture')->set('state', ModuleDefine::STATE_ENABLED);
        $modules->deleteModule('fixture');
        $this->assertDirectoryDoesNotExist($root);
    }

    public function testInvalidStateTransitionsAndSymbolicLinkDeletion(): void
    {
        $modules = new FixtureModules();

        $root = $this->temporaryDirectory . '/hard-disabled';
        mkdir($root);
        $modules->add('hard-disabled', $root, ['name' => 'Hard disabled']);
        $modules->getDefine('hard-disabled')->set('state', ModuleDefine::STATE_HARD_DISABLED);

        try {
            $modules->deactivateModule('missing');
            $this->fail('Expected unknown module to be rejected');
        } catch (\Exception $exception) {
            $this->assertSame('No such module.', $exception->getMessage());
        }

        try {
            $modules->deactivateModule('hard-disabled');
            $this->fail('Expected hard-disabled module to be rejected');
        } catch (\Exception $exception) {
            $this->assertSame('Cannot deactivate plugin.', $exception->getMessage());
        }

        try {
            $modules->activateModule('missing');
            $this->fail('Expected unknown module to be rejected');
        } catch (\Exception $exception) {
            $this->assertSame('No such module.', $exception->getMessage());
        }

        try {
            $modules->deleteModule('hard-disabled');
            $this->fail('Expected enabled-only deletion to reject a disabled module');
        } catch (\Exception $exception) {
            $this->assertSame('No such module.', $exception->getMessage());
        }

        $target = $this->temporaryDirectory . '/linked-target';
        $link   = $this->temporaryDirectory . '/linked-module';
        mkdir($target);
        symlink($target, $link);
        $modules->add('linked', $link, ['name' => 'Linked']);
        $modules->getDefine('linked')->set('state', ModuleDefine::STATE_SOFT_DISABLED);
        $modules->deleteModule('linked', true);

        $this->assertFileDoesNotExist($link);
        $this->assertDirectoryExists($target);
    }

    public function testLoadingAndInstallingLegacyModules(): void
    {
        $moduleRoot = $this->temporaryDirectory . '/loaded';
        mkdir($moduleRoot);
        file_put_contents($moduleRoot . '/_define.php', "<?php\n\$this->registerModule('Loaded', '', '', '1.0', ['type' => 'plugin']);\n");
        file_put_contents($moduleRoot . '/_init.php', "<?php\n\$GLOBALS['module_init_runs'] = (\$GLOBALS['module_init_runs'] ?? 0) + 1;\n");
        file_put_contents($moduleRoot . '/_prepend.php', "<?php\nreturn true;\n");
        file_put_contents($moduleRoot . '/_admin.php', "<?php\n\$GLOBALS['module_admin_loaded'] = true;\n");
        file_put_contents($moduleRoot . '/_public.php', "<?php\n\$GLOBALS['module_public_loaded'] = true;\n");

        $disabledRoot = $this->temporaryDirectory . '/disabled';
        mkdir($disabledRoot);
        file_put_contents($disabledRoot . '/_define.php', "<?php\n\$this->registerModule('Disabled', '', '', '1.0', ['type' => 'plugin']);\n");
        file_put_contents($disabledRoot . '/_disabled', '');

        $modules = new FixtureModules();
        $modules->loadModules($this->temporaryDirectory, 'public');
        $this->assertSame(1, $GLOBALS['module_init_runs']);
        $this->assertTrue($GLOBALS['module_public_loaded']);
        $this->assertEqualsCanonicalizing(['disabled', 'loaded'], array_map(
            static fn (ModuleDefine $define): string => $define->getId(),
            $modules->getDefines()
        ));
        $this->assertSame(ModuleDefine::STATE_HARD_DISABLED, $modules->getDefine('disabled')->get('state'));
        $this->assertTrue($modules->moduleExists('loaded'));
        $this->assertFalse($modules->moduleExists('disabled'));

        $modules->loadNsFile('loaded', 'admin');
        $modules->loadNsFile('loaded', 'public');
        $modules->loadNsFile('loaded', 'upgrade');
        $this->assertTrue($GLOBALS['module_admin_loaded']);
        $this->assertTrue($GLOBALS['module_public_loaded']);

        $failureRoot = $this->temporaryDirectory . '/failed';
        mkdir($failureRoot);
        file_put_contents($failureRoot . '/_install.php', "<?php\nthrow new RuntimeException('install failed');\n");
        $modules->add('failed', $failureRoot, ['name' => 'Failed', 'version' => '1.0']);
        $modules->throwOnInstall = true;
        $message = '';
        $this->assertFalse($modules->installModule('failed', $message));
        $this->assertSame('install failed', $message);

        $this->assertNull($modules->installModule('missing', $message));
        foreach ($modules->getDefines() as $define) {
            $define->set('state', ModuleDefine::STATE_HARD_DISABLED);
        }
        $this->assertSame(['success' => [], 'failure' => []], $modules->installModules());
    }

    public function testInstallModulesCollectsFailuresAndSkipsDisabledModules(): void
    {
        $root = $this->temporaryDirectory . '/broken-install';
        mkdir($root);
        file_put_contents($root . '/_install.php', "<?php\nreturn true;\n");

        $modules = new FixtureModules();
        $modules->add('broken', $root, ['name' => 'Broken']);
        $modules->throwOnInstall = true;

        $disabledRoot = $this->temporaryDirectory . '/disabled-install';
        mkdir($disabledRoot);
        $modules->add('disabled-install', $disabledRoot, ['name' => 'Disabled']);
        $modules->getDefine('disabled-install')->set('state', ModuleDefine::STATE_HARD_DISABLED);

        $this->assertSame([
            'success' => [],
            'failure' => ['broken' => 'install failed'],
        ], $modules->installModules());
    }

    public function testInstallingNewAndUpdatingDisabledPackages(): void
    {
        $modules = new FixtureModules();
        $archive  = $this->temporaryDirectory . '/fresh.zip';
        $this->createModuleArchive($archive, 'fresh', '1.0');

        $this->assertSame(Modules::PACKAGE_INSTALLED, Modules::installPackage($archive, $modules));
        $this->assertFileExists($this->temporaryDirectory . '/fresh/_define.php');
        $this->assertFileDoesNotExist($archive);

        $upgradeRoot = $this->temporaryDirectory . '/upgradeable';
        mkdir($upgradeRoot);
        file_put_contents($upgradeRoot . '/_disabled', '');
        $modules->add('upgradeable', $upgradeRoot, [
            'name'    => 'Current',
            'type'    => 'plugin',
            'version' => '1.0',
        ]);

        $upgradeArchive = $this->temporaryDirectory . '/upgradeable.zip';
        $this->createModuleArchive($upgradeArchive, 'upgradeable', '2.0');

        $this->assertSame(Modules::PACKAGE_UPDATED, Modules::installPackage($upgradeArchive, $modules));
        $this->assertFileExists($upgradeRoot . '/_disabled');
        $this->assertStringContainsString('2.0', (string) file_get_contents($upgradeRoot . '/_define.php'));
        $this->assertFileDoesNotExist($upgradeArchive);
    }

    public function testPackageWithoutDefineIsRejectedAndRemoved(): void
    {
        $archive = $this->temporaryDirectory . '/invalid.zip';
        $zip     = new \ZipArchive();
        $this->assertTrue($zip->open($archive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $this->assertTrue($zip->addFromString('invalid/README.md', 'not a module'));
        $this->assertTrue($zip->close());

        $modules = new FixtureModules();
        try {
            Modules::installPackage($archive, $modules);
            $this->fail('Expected invalid module archive to be rejected');
        } catch (\Exception $exception) {
            $this->assertSame('The zip file does not appear to be a valid Dotclear module.', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($archive);
    }

    public function testArchiveContainingOnlyExcludedMetadataIsRejectedAndRemoved(): void
    {
        $archive = $this->temporaryDirectory . '/empty.zip';
        $zip     = new \ZipArchive();
        $this->assertTrue($zip->open($archive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $this->assertTrue($zip->addFromString('__MACOSX/._empty', 'ignored archive metadata'));
        $this->assertTrue($zip->close());

        $modules = new FixtureModules();
        try {
            Modules::installPackage($archive, $modules);
            $this->fail('Expected metadata-only archive to be rejected');
        } catch (\Exception $exception) {
            $this->assertSame('The zip file does not appear to be a valid Dotclear module.', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($archive);
    }

    public function testOlderPackageHonorsDevelopmentModeVersionPolicy(): void
    {
        $root = $this->temporaryDirectory . '/existing';
        mkdir($root);
        $modules = new FixtureModules();
        $modules->add('existing', $root, [
            'name'    => 'Existing',
            'type'    => 'plugin',
            'version' => '2.0',
        ]);

        $archive = $this->temporaryDirectory . '/existing.zip';
        $this->createModuleArchive($archive, 'existing', '1.0');

        if (\Dotclear\App::config()->devMode()) {
            $this->assertSame(Modules::PACKAGE_UPDATED, Modules::installPackage($archive, $modules));
            $this->assertFileExists($root . '/_define.php');
        } else {
            try {
                Modules::installPackage($archive, $modules);
                $this->fail('Expected older module package to be rejected');
            } catch (\Exception $exception) {
                $this->assertSame('Unable to upgrade "existing" (older or same version).', $exception->getMessage());
            }
        }

        $this->assertDirectoryExists($root);
        $this->assertFileDoesNotExist($archive);
    }

    public function testDisabledDependenciesAndModuleTypeErrors(): void
    {
        $root = $this->temporaryDirectory . '/mismatched';
        mkdir($root);

        $modules = new FixtureModules();
        $modules->setType('plugin');
        $modules->add('mismatched', $root, ['name' => 'Wrong type', 'type' => 'theme']);
        $this->assertCount(2, $modules->getErrors());
        $this->assertFileExists($root . '/_disabled');
        $this->assertStringContainsString('mismatch required module type', $modules->getErrors()[0]);

        $dependentRoot = $this->temporaryDirectory . '/dependent';
        mkdir($dependentRoot);
        $modules->setType(null);
        $modules->add('dependent', $dependentRoot, ['name' => 'Dependent']);
        $modules->getDefine('dependent')->addMissing('missing', 'Missing dependency');
        $modules->safeMode(true);
        $this->assertSame([], $modules->disableDepModules());
        $modules->safeMode(false);
        unset($_GET['dep']);
        $this->assertSame(['Dependent : Missing dependency'], $modules->disableDepModules());
        $this->assertFileExists($dependentRoot . '/_disabled');

        $_GET['dep'] = '1';
        $this->assertSame([], $modules->disableDepModules());
        unset($_GET['dep']);
    }

    public function testModuleListsAndMarkupUtilities(): void
    {
        $root = $this->temporaryDirectory . '/listed';
        mkdir($root);
        $modules = new FixtureModules();
        $modules->add('enabled', $root, ['name' => 'Enabled', 'version' => '2.0']);
        $modules->add('soft', $root, ['name' => 'Soft disabled']);
        $modules->add('hard', $root, ['name' => 'Hard disabled']);
        $modules->getDefine('soft')->set('state', ModuleDefine::STATE_SOFT_DISABLED);
        $modules->getDefine('hard')->set('state', ModuleDefine::STATE_HARD_DISABLED);

        $this->assertSame('', $modules->getProtectedModules());
        $this->assertSame('enabled', $modules->getDefines(['id' => 'enabled'])[0]->getId());
        $this->assertCount(3, $modules->getDefines(['ignored' => ['non-scalar']]));
        $this->assertSame(['enabled' => $modules->getDefine('enabled')->dump()], $modules->getDefines(['id' => 'enabled'], true));
        $this->assertTrue($modules->moduleExists('enabled'));
        $this->assertSame('2.0', $modules->moduleInfo('enabled', 'version'));
        $this->assertSame($root, $modules->moduleRoot('enabled'));
        $this->assertSame('enabled', $modules->getModules('enabled')['id']);
        $this->assertSame(['enabled', 'soft', 'hard'], array_keys($modules->getAnyModules()));
        $this->assertSame('hard', $modules->getAnyModules('hard')['id']);
        $this->assertSame(['soft', 'hard'], array_keys($modules->getDisabledModules()));
        $this->assertSame(['hard'], array_keys($modules->getHardDisabledModules()));
        $this->assertSame(['soft'], array_keys($modules->getSoftDisabledModules()));
        $this->assertSame(['soft', 'hard'], array_map(
            static fn (ModuleDefine $define): string => $define->getId(),
            $modules->getDefines(['state' => '!' . ModuleDefine::STATE_ENABLED])
        ));
        $modules->safeMode(true);
        $this->assertSame(['soft'], array_keys($modules->getModules()));
        $modules->loadModuleL10N('enabled', null, 'main');
        $modules->loadModuleL10Nresources('enabled', 'fr');
        $modules->safeMode(false);
        $modules->loadNsFiles('upgrade');
        $modules->loadNsFile('enabled', 'unknown');
        $this->assertSame('', $modules->loadNsClass('unknown', 'Admin'));
        $this->assertSame('', $modules->loadNsClass('enabled', 'Admin'));
        $modules->loadModuleL10N('enabled', null, 'main');
        $modules->loadModuleL10Nresources('enabled', null);

        $css = $modules->cssLoad('style.css?theme=dark', 'print', 'v1');
        $this->assertStringContainsString('rel="stylesheet"', $css);
        $this->assertStringContainsString('media="print"', $css);
        $this->assertStringContainsString('style.css?theme=dark', $css);
        $this->assertStringContainsString('<script ', $modules->jsLoad('script.js', null, true, true));
        $this->assertStringContainsString('type="module" defer', $modules->jsLoad('script', 'v1', true, true));
        $this->assertSame("<script src=\"script.js\"></script>\n", $modules->jsLoad('script.js', null));
    }
}
