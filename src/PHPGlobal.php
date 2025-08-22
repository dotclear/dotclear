<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

if (!function_exists('terminate')) {
    /**
     * Terminate application/process
     *
     * It is not possible to disable, or create a namespaced function shadowing the global exit() function.
     * So, in order to test code, we should be able to mock this event, using terminate() global function.
     */
    function terminate(string|int $status = 0): never
    {
        exit($status);
    }
}
