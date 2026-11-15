<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Process;

use Dotclear\Exception\ContextException;
use Dotclear\Exception\ProcessException;
use Dotclear\Helper\Process\AbstractUtility;
use Dotclear\Helper\Process\TraitProcess;
use PHPUnit\Framework\TestCase;

class AbstractUtilityProcess
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(true);
    }
}

class AbstractUtilityNoProcess
{
}

class AbstractUtilityTester extends AbstractUtility
{
    public const CONTAINER_ID = 'unit_utility_test';

    public const UTILITY_PROCESS = [
        AbstractUtilityProcess::class,
        AbstractUtilityNoProcess::class,
        'AbstractUtilityMissingProcess',
    ];

    public static function init(): bool
    {
        return self::status(true);
    }
}

class AbstractUtilityTest extends TestCase
{
    public function testUtilityContextAndProcessLookup(): void
    {
        try {
            new AbstractUtilityTester();
            $this->fail('Expected construction outside the utility context to be rejected');
        } catch (ContextException $exception) {
            $this->assertSame('Application is not in unit_utility_test context.', $exception->getMessage());
        }

        \Dotclear\App::task()->addContext(AbstractUtilityTester::CONTAINER_ID);
        $utility = new AbstractUtilityTester();

        $this->assertSame(AbstractUtilityProcess::class, $utility->getProcess('AbstractUtilityProcess'));

        foreach (['AbstractUtilityNoProcess', 'missing'] as $process) {
            try {
                $utility->getProcess($process);
                $this->fail('Expected an invalid utility process to be rejected');
            } catch (ProcessException $exception) {
                $this->assertSame('Unable to get process ' . $process . '.', $exception->getMessage());
            }
        }
    }
}
