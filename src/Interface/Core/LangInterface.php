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
 * @brief   Lang handler interface.
 *
 * @since   2.28
 */
interface LangInterface
{
    /**
     * The default language code.
     *
     * @var     string  DEFAULT_LANG
     */
    public const DEFAULT_LANG = 'en';

    /**
     * Gets the current language code.
     *
     * @return  string  The language code.
     */
    public function getLang(): string;

    /**
     * Sets the language code.
     *
     * @param   string  $lang   The language code
     */
    public function setLang(string $lang): void;
}
