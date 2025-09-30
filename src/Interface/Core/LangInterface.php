<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Interface\Helper\L10nInterface;

/**
 * @brief   Lang handler interface.
 *
 * @since   2.28
 * @since   2.36, extends L10n helper
 */
interface LangInterface extends L10nInterface
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
