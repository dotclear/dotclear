<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\MyModule;
use Dotclear\Interface\Module\ModulesInterface;
use Exception;
use PHPUnit\Framework\TestCase;

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
