<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\MyModule;
use Dotclear\Core\Backend\Icon;
use Dotclear\Core\Backend\Menu;
use Dotclear\Core\Backend\Menus;
use Dotclear\Interface\Module\ModulesInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class FixtureMyModule extends MyModule
{
    public static ?ModuleDefine $definition = null;

    protected static function define(): ModuleDefine
    {
        if (self::$definition === null) {
            self::$definition = (new ModuleDefine('fixture'))
            ->set('name', 'Fixture')
            ->set('root', '/tmp/fixture-module');
        }

        return self::$definition;
    }

    public static function defineFromModules(?ModulesInterface $modules): ModuleDefine
    {
        return self::getDefineFromNamespace($modules);
    }
}

class MyModuleTest extends TestCase
{
    protected function setUp(): void
    {
        FixtureMyModule::$definition = null;
    }

    public function testBasicModuleIdentityAndResourceHelpers(): void
    {
        $this->assertSame('/tmp/fixture-module', FixtureMyModule::path());
        $this->assertSame('fixture', FixtureMyModule::id());
        $this->assertSame('Fixture', FixtureMyModule::name());
        $this->assertFalse(FixtureMyModule::checkContext(-1));
        $this->assertTrue(FixtureRestrictedModule::checkContext(MyModule::BACKEND));
        $this->assertFalse(FixtureRestrictedModule::checkContext(MyModule::CONFIG));
        $this->assertSame('', FixtureUnconfiguredModule::path());
        $this->assertSame('undefined', FixtureUnconfiguredModule::name());
        $this->assertSame([], FixturePlugin::icons());
        $this->assertSame([], FixturePlugin::icons('dark'));
        $this->assertInstanceOf(\Dotclear\Core\Backend\Icon::class, FixturePlugin::icon());
        $this->assertSame('', FixturePlugin::manageUrl());
        $this->assertSame([], FixturePlugin::hiddenFields());
        $this->assertSame('', FixturePlugin::parsedHiddenFields());
        FixturePlugin::addBackendMenuItem();
        FixturePlugin::redirect();
        $this->assertFalse(FixtureTheme::checkContext(MyModule::BACKEND));
    }

    public function testDefinitionResolutionIsCachedAndReportsErrors(): void
    {
        $define = (new ModuleDefine('fixture'))->set('name', 'Resolved');
        $modules = $this->createMock(ModulesInterface::class);
        $modules->expects($this->once())
            ->method('getDefines')
            ->with(['namespace' => '\\' . __NAMESPACE__])
            ->willReturn([$define]);

        $this->assertSame($define, FixtureMyModule::defineFromModules($modules));
        $this->assertSame($define, FixtureMyModule::defineFromModules($this->createStub(ModulesInterface::class)));

        try {
            FixtureOtherMyModule::defineFromModules(null);
            $this->fail('Expected unresolved module namespace to throw');
        } catch (Exception $exception) {
            $this->assertStringContainsString('Invalid module structure', $exception->getMessage());
        }
    }

    public function testDefaultPermissionContextsReturnBooleanResults(): void
    {
        foreach ([
            MyModule::INSTALL,
            MyModule::PREPEND,
            MyModule::FRONTEND,
            MyModule::BACKEND,
            MyModule::MANAGE,
            MyModule::CONFIG,
            MyModule::MENU,
            MyModule::WIDGETS,
            MyModule::UNINSTALL,
            MyModule::MODULE,
            999,
        ] as $context) {
            $this->assertIsBool(FixtureMyModule::checkContext($context));
        }
    }

    public function testModuleLocaleAndResourceHelpersUseTheirPublicDefaults(): void
    {
        FixtureMyModule::l10n('main');

        $this->assertSame('', FixtureMyModule::fileURL('css/site.css'));
        $this->assertSame('', FixtureTheme::fileURL('/assets/theme.css'));

        $css = FixturePlugin::cssLoad('site', 'print', '1.2.3');
        $this->assertStringContainsString('rel="stylesheet"', $css);
        $this->assertStringContainsString('media="print"', $css);

        $js = FixturePlugin::jsLoad('/lib/controller.mjs', '1.2.3', true, true);
        $this->assertStringContainsString('type="module" defer', $js);
    }

    public function testPluginBackendHelpersWithBackendContext(): void
    {
        $root = sys_get_temp_dir() . '/my-plugin-' . bin2hex(random_bytes(6));
        mkdir($root);
        file_put_contents($root . '/icon.svg', '<svg/>');
        file_put_contents($root . '/icon-dark.svg', '<svg/>');

        FixtureMyModule::$definition = (new ModuleDefine('fixture'))
            ->set('name', 'Fixture')
            ->set('root', $root);

        $task     = \Dotclear\App::task();
        $contexts = new ReflectionProperty($task, 'context');
        $previous = $contexts->getValue($task);
        $contexts->setValue($task, ['BACKEND' => true]);

        try {
            $backendUrl = \Dotclear\App::backend()->url();
            $backendUrl->register('load.plugin.file', 'load.plugin.file.php');
            $backendUrl->register('load.theme.file', 'load.theme.file.php');
            $backendUrl->register('admin.plugin.fixture', 'admin.plugin.fixture.php');
            \Dotclear\App::backend()->menus()->offsetSet(Menus::MENU_PLUGINS, new Menu('plugins-menu', 'Plugins'));

            $icons = FixturePlugin::icons();
            $this->assertCount(2, $icons);
            $this->assertNotSame('', $icons[0]);
            $this->assertSame($icons, FixturePlugin::icons('missing'));
            $this->assertNotSame('', FixturePlugin::manageUrl());
            $this->assertStringContainsString('assets/theme.css', FixtureTheme::fileURL('/assets/theme.css'));

            $fields = FixturePlugin::hiddenFields(['extra' => 'value']);
            $this->assertNotEmpty($fields);
            $this->assertStringContainsString('value', FixturePlugin::parsedHiddenFields(['extra' => 'value']));
            $this->assertInstanceOf(Icon::class, FixturePlugin::icon());
            FixturePlugin::addBackendMenuItem(Menus::MENU_PLUGINS, ['tab' => 'example'], '(&.*)?$', 'custom');
        } finally {
            $contexts->setValue($task, $previous);
            FixtureMyModule::$definition = null;
            unlink($root . '/icon.svg');
            unlink($root . '/icon-dark.svg');
            rmdir($root);
        }
    }
}

class FixturePlugin extends \Dotclear\Module\MyPlugin
{
    protected static function define(): ModuleDefine
    {
        return FixtureMyModule::$definition ?? (new ModuleDefine('fixture'))->set('name', 'Fixture')->set('root', '/tmp/fixture-module');
    }
}

class FixtureTheme extends \Dotclear\Module\MyTheme
{
    protected static function define(): ModuleDefine
    {
        return FixtureMyModule::$definition ?? (new ModuleDefine('fixture'))->set('name', 'Fixture')->set('root', '/tmp/fixture-theme');
    }
}

class FixtureOtherMyModule extends MyModule
{
    protected static function define(): ModuleDefine
    {
        return new ModuleDefine('other');
    }

    public static function defineFromModules(?ModulesInterface $modules): ModuleDefine
    {
        return self::getDefineFromNamespace($modules);
    }
}

class FixtureRestrictedModule extends MyModule
{
    protected static function define(): ModuleDefine
    {
        return new ModuleDefine('restricted');
    }

    protected static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            self::BACKEND => true,
            self::CONFIG  => false,
            default       => null,
        };
    }
}

class FixtureUnconfiguredModule extends MyModule
{
    protected static function define(): ModuleDefine
    {
        return (new ModuleDefine('unconfigured'))
            ->set('name', null)
            ->set('root', null);
    }
}
