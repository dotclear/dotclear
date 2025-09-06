<?php

/**
 * @package     Dotclear
 * @subpackage Core
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Exception\ProcessException;
use Dotclear\Helper\TraitDynamicProperties;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;

/**
 * @brief   Utility class structure.
 *
 * This class tags child class as Utility.
 * * An utility MUST extends Dotclear\Core\Utility class.
 * * A process MUST extends Dotclear\Core\Process class.
 *
 * @since   2.36
 */
abstract class Utility extends Container
{
    use TraitDynamicProperties, TraitProcess;

    /**
     * Utility Process.
     *
     * @var     array<string, string>   UTILITY_PROCESS
     */
    public const UTILITY_PROCESS = [];

    public function __construct()
    {
        // Create a non replaceable factory
        parent::__construct(new Factory(static::CONTAINER_ID, false));
    }

    public function getDefaultServices(): array
    {
        return static::UTILITY_PROCESS;
    }

    /**
     * Get process class name.
     *
     * @throws  ProcessException
     */
    public function getProcess(string $process): string
    {
        // Search in Utility container a class with this name that extend process
        if (is_string($service = $this->factory->get($process)) 
            && ($class = $this->checkProcess($process, $service)) !== ''
        ) {
            return $class;
        }

        throw new ProcessException(sprintf(__('Unable to get process %s'), $process));
    }

    /**
     * Initialize application utility.
     */
    public static function init(): bool
    {
        return !App::config()->cliMode();
    }

    /**
     * Check that service is an Utility Process.
     */
    private function checkProcess(string $process, string $service): string
    {
        try {
            $reflection = new \ReflectionClass($service);    // @phpstan-ignore-line should tag service as class-string
        } catch (\ReflectionException) {
            return '';
        }
        if ($reflection->getShortName() === $process
            && ($reflection->isSubclassOf(Process::class) || array_key_exists(TraitProcess::class, $reflection->getTraits()))
        ) {
            return $service;
        }

        return '';
    }
}
