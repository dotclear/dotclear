<?php

declare(strict_types=1);

namespace Dotclear\Tests\Module;

use Dotclear\Module\ModuleDefine;
use PHPUnit\Framework\TestCase;

class InitializedModuleDefine extends ModuleDefine
{
    protected function init(): void
    {
        $this->set('name', 'Initialized');
        $this->addImplies('dependency');
    }
}

class ModuleDefineTest extends TestCase
{
    public function testDefaultAndOverriddenProperties(): void
    {
        $define = new ModuleDefine('sample');

        $this->assertSame('sample', $define->getId());
        $this->assertSame('sample', $define->get('id'));
        $this->assertSame('undefined', $define->get('name'));
        $this->assertSame('undefined', $define->get('type'));
        $this->assertSame('0', $define->get('version'));
        $this->assertSame(1000, $define->get('priority'));
        $this->assertNull($define->get('not-defined'));
        $this->assertFalse($define->isDefined());
        $this->assertFalse(isset($define->name));

        $define
            ->set('name', 'Sample')
            ->set('version', '1.2.3')
            ->set('priority', -2)
            ->set('unknown', 'ignored');
        $define->author = 'Dotclear';

        $this->assertTrue($define->isDefined());
        $this->assertSame('Sample', $define->name);
        $this->assertSame('1.2.3', $define->get('version'));
        $this->assertSame('Dotclear', $define->get('author'));
        $this->assertNull($define->get('unknown'));
        $this->assertTrue(isset($define->name));

        unset($define->name);
        unset($define->unknown);
        $this->assertSame('undefined', $define->get('name'));
        $this->assertTrue(isset($define->name));
        $this->assertSame('sample', $define->get('id'));
    }

    public function testDependenciesDumpAndInitHook(): void
    {
        $define = new InitializedModuleDefine('initialized');
        $define->addImplies('second');
        $define->addMissing('missing', 'unavailable');
        $define->addUsing('consumer');

        $this->assertSame(['dependency'], array_slice($define->getImplies(), 0, 1));
        $this->assertSame(['dependency', 'second'], $define->getImplies());
        $this->assertSame(['missing' => 'unavailable'], $define->getMissing());
        $this->assertSame(['consumer'], $define->getUsing());
        $this->assertTrue($define->isDefined());

        $dump = $define->dump();
        $this->assertSame('initialized', $dump['id']);
        $this->assertSame('Initialized', $dump['name']);
        $this->assertFalse($dump['enabled']);
        $this->assertSame(['dependency', 'second'], $dump['implies']);
        $this->assertSame(['missing' => 'unavailable'], $dump['cannot_enable']);
        $this->assertSame(['consumer'], $dump['cannot_disable']);
    }

    public function testNullValuesAndEnabledStateAreReflectedInMagicProperties(): void
    {
        $define = new ModuleDefine('sample');

        $define->set('name', null);
        $this->assertSame('undefined', $define->name);
        $this->assertFalse(isset($define->name));
        $this->assertSame('undefined', $define->get('name'));

        $define->set('name', 'Sample');
        $define->set('state', ModuleDefine::STATE_ENABLED);
        $this->assertTrue(isset($define->name));
        $this->assertTrue($define->dump()['enabled']);

        unset($define->state);
        $this->assertSame(ModuleDefine::STATE_INIT_DISABLED, $define->get('state'));
        $this->assertFalse($define->dump()['enabled']);
    }
}
