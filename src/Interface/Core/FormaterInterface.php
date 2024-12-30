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
 * @brief   Text formater handler interface.
 *
 * @since   2.28
 */
interface FormaterInterface
{
    /**
     * Adds a new text formater.
     *
     * Which will call the function <var>$func</var> to
     * transform text. The function must be a valid callback and takes one
     * argument: the string to transform. It returns the transformed string.
     *
     * @param   string           $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param   string           $name       The formater name
     * @param   callable|null    $func       The function to use, must be a valid and callable callback
     */
    public function addEditorFormater(string $editor_id, string $name, ?callable $func): void;

    /**
     * Adds a formater name.
     *
     * @param   string  $format     The format
     * @param   string  $name       The name
     */
    public function addFormaterName(string $format, string $name): void;

    /**
     * Gets the formater name.
     *
     * @param   string  $format     The format
     *
     * @return  string  The formater name.
     */
    public function getFormaterName(string $format): string;

    /**
     * Gets the editors list.
     *
     * @return  array<string, string>   The editors.
     */
    public function getEditors(): array;

    /**
     * Gets the formaters.
     *
     * return formaters for an editor if editor is active
     * return empty() array if editor is not active.
     * It can happens when a user choose an editor and admin deactivate that editor later
     *
     * @param   string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     *
     * @return  array<string>   The formaters.
     */
    public function getFormater(string $editor_id): array;

    /**
     * Gets the formaters.
     *
     * @return     array<string, array<string>>  The formaters.
     */
    public function getFormaters(): array;

    /**
     * Call editor formater.
     *
     * If <var>$name</var> is a valid formater, it returns <var>$str</var>
     * transformed using that formater.
     *
     * @param   string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param   string  $name       The formater name
     * @param   string  $str        The string to transform
     */
    public function callEditorFormater(string $editor_id, string $name, string $str): string;
}
