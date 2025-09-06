<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Application task interface.
 *
 * This class execute application according to an Utility and its Process.
 * * An utility MUST extends Dotclear\Core\Utility class.
 * * A process MUST extends Dotclear\Core\Process class.
 *
 * @since   2.28
 */
interface TaskInterface
{
    /**
     * Run task.
     *
     * @throws  \Dotclear\Exception\ContextException
     * @throws  \Dotclear\Exception\ProcessException
     *
     * @param   string  $utility    The called app Utility
     * @param   string  $process    The called app Process
     */
    public function run(string $utility, string $process): void;

    /**
     * Check if a context is set.
     *
     * Method is not case sensitive.
     *
     * @param   string  $context    The context to check
     *
     * @return  bool    True if context is set
     */
    public function checkContext(string $context): bool;

    /**
     * Set a context.
     *
     * Method is not case sensitive.
     *
     * Context can be one of:
     * * BACKEND
     * * FRONTEND
     * * INSTALL
     * * MODULE
     * * UPGRADE
     *
     * @param   string  $context    The context to set
     */
    public function addContext(string $context): void;

    /**
     * Processes the given process.
     *
     * A process MUST extends Dotclear\Core\Process class.
     * A process can only be loaded from its Utility.
     *
     * @throws  \Dotclear\Exception\ProcessException
     *
     * @param   string  $process    The process
     */
    public function loadProcess(string $process): void;
}
