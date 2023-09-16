<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Application task interface.
 *
 * @since   2.28
 */
interface TaskInterface
{
    /**
     * Run task.
     *
     * @param   string 	$utility 	The called app Utility
     * @param   string 	$process    The called app Process
     */
    public function run(string $utility, string $process);

    /**
     * Check if a context is set.
     *
     * Method is not case sensitive.
     *
     * @param   string  $context    The cotenxt to check
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
     *
     * @param   string  $process    The process
     */
    public function loadProcess(string $process): void;

    /**
     * Get current lang.
     *
     * @return  string
     */
    public static function getLang(): string;

    /**
     * Set the lang to use.
     *
     * @param   string  $id     The lang ID
     */
    public static function setLang($id): void;
}
