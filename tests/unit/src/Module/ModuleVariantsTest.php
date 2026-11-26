<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\Plugins;
use Dotclear\Module\Themes;
use PHPUnit\Framework\TestCase;

class FixturePlugins extends Plugins
{
    public ?ModuleDefine $registered = null;

    public function registerAs(string $id, mixed $properties): void
    {
        $this->id = $id;
        $this->registerModule('Plugin', 'Description', 'Author', '1.0', $properties, 24);
        $this->id = null;
    }

    protected function defineModule(ModuleDefine $define): void
    {
        $this->registered = $define;
    }
}

class FixtureThemes extends Themes
{
    public ?ModuleDefine $registered = null;

    public function registerAs(string $id, mixed $properties): void
    {
        $this->id = $id;
        $this->registerModule('Theme', 'Description', 'Author', '1.0', $properties, 15);
        $this->id = null;
    }

    protected function defineModule(ModuleDefine $define): void
    {
        $this->registered = $define;
        parent::defineModule($define);
    }

    public function addDefinition(ModuleDefine $define): void
    {
        $this->defines[] = $define;
    }

    public function setThemePath(string $path): void
    {
        $this->path = [$path];
    }

    public function loadContext(string $namespace): void
    {
        $this->loadModulesContext([], $namespace, null);
    }
}

class ModuleVariantsTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/module-variants-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);
    }

    public function testPluginRegistrationAndProtectedList(): void
    {
        $plugins = new FixturePlugins();
        $plugins->registerModule('Ignored', '', '', '0');
        $this->assertNull($plugins->registered);

        $plugins->registerAs('array-plugin', ['type' => 'plugin', 'priority' => 3]);
        $this->assertSame('array-plugin', $plugins->registered?->getId());
        $this->assertSame('Plugin', $plugins->registered?->get('name'));
        $this->assertSame(3, $plugins->registered?->get('priority'));

        $plugins->registerAs('legacy-plugin', 'read,write');
        $this->assertSame('read,write', $plugins->registered?->get('permissions'));
        $this->assertSame(24, $plugins->registered?->get('priority'));
        $this->assertSame(\Dotclear\App::config()->distributedPlugins(), $plugins->getProtectedModules());
    }

    public function testThemeRegistrationAndCloning(): void
    {
        $themes = new FixtureThemes();
        $themes->registerAs('legacy-theme', 'parent-theme');
        $this->assertSame('parent-theme', $themes->registered?->get('parent'));
        $this->assertSame(15, $themes->registered?->get('priority'));
        $this->assertNotEmpty($themes->registered?->get('permissions'));

        $themeRoot = $this->temporaryDirectory . '/original';
        mkdir($themeRoot);
        mkdir($themeRoot . '/src', 0777, true);
        file_put_contents(
            $themeRoot . '/_define.php',
            "<?php\n\$this->registerModule('Original', 'Description', 'Author', '1.0');\n"
        );
        file_put_contents($themeRoot . '/src/Example.php', "<?php\nnamespace themes\\original;\n");
        file_put_contents($themeRoot . '/src/Modern.php', "<?php\nnamespace Dotclear\\Theme\\Berlin;\n");

        $define = (new ModuleDefine('original'))
            ->set('name', 'Original')
            ->set('root', $themeRoot);
        $themes->addDefinition($define);
        $themes->setThemePath($this->temporaryDirectory);
        $define->set('state', ModuleDefine::STATE_ENABLED);
        $themes->loadNsFile('original', 'upgrade');
        $themes->loadNsFile('unknown', 'public');
        $themes->loadContext('public');
        $themes->safeMode(true);
        $themes->loadModuleL10N('original', 'fr', 'main');
        $themes->safeMode(false);
        $themes->loadModuleL10N('original', null, 'main');

        mkdir($themeRoot . '_copy');
        $themes->cloneModule('original');
        $copy = $themeRoot . '_copy_1';

        $this->assertFileExists($copy . '/_define.php');
        $this->assertFileExists($copy . '/src/Example.php');
        $this->assertFileExists($copy . '/src/Modern.php');
        $this->assertStringContainsString('Original (copy #1)', (string) file_get_contents($copy . '/_define.php'));
        $this->assertStringContainsString('namespace themes\\original_copy_1;', (string) file_get_contents($copy . '/src/Example.php'));
        $this->assertStringContainsString('namespace Dotclear\\Theme\\original_copy_1;', (string) file_get_contents($copy . '/src/Modern.php'));
        $this->assertTrue($themes->isOverloadable('original'));
        $this->assertFalse($themes->isOverloadable('unknown'));
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
