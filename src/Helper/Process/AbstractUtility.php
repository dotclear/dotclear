<?php

/**
 * @package     Dotclear
 * @subpackage Core
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Process;

use Dotclear\App;
use Dotclear\Exception\ContextException;
use Dotclear\Exception\ProcessException;
use Dotclear\Helper\TraitDynamicProperties;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;
use ReflectionClass;

/**
 * @brief   Utility class structure.
 *
 * This class tags child class as Utility.
 * * An utility MUST extends Dotclear\Helper\Process\AbstractUtility class.
 * * A process MUST extends Dotclear\Helper\Process\TraitProcess class.
 *
 * @since   2.36
 */
abstract class AbstractUtility extends Container
{
    use TraitDynamicProperties;
    use TraitProcess;

    /**
     * Utility Process.
     *
     * @var     string[]   UTILITY_PROCESS
     */
    public const UTILITY_PROCESS = [];

    public function __construct()
    {
        // Check context
        if (!App::task()->checkContext(strtoupper(static::CONTAINER_ID))) {
            throw new ContextException(sprintf('Application is not in %s context.', static::CONTAINER_ID));
        }

        // Create a non replaceable factory
        parent::__construct(new Factory(static::CONTAINER_ID, false));

        // Add utility process
        foreach (static::UTILITY_PROCESS as $callback) {
            if (class_exists($callback)) { // limit to class
                // Create on the fly the Process ID. ie called from App:task()->run(Utility, Process)
                $this->factory->set((new ReflectionClass($callback))->getShortName(), $callback);
            }
        }
    }

    /**
     * Get process class name.
     *
     * Search in Utility container a service named <var>$process</var> which uses TraitProcess.
     *
     * @throws  ProcessException
     */
    public function getProcess(string $process): string
    {
        if (is_string($service = $this->factory->get($process)) && class_exists($service)) {
            $reflection = new ReflectionClass($service);
            if ($reflection->getShortName() === $process && array_key_exists(TraitProcess::class, $reflection->getTraits())) {
                return $service;
            }
        }

        throw new ProcessException(sprintf(__('Unable to get process %s'), $process));
    }
}
