<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\Cursor;

/**
 * @brief   Lang handler interface.
 *
 * @since   2.28
 */
interface LangInterface
{
    /**
     * The default lang (code).
     *
     * @var     string  VERSION_TABLE_NAME
     */
    public const DEFAULT_LANG = 'en';

    /**
     * Get the lang.
     *
     * @return 	string 	The lang code.
     */
    public function getLang(): string;

    /**
     * Set the lang.
     *
     * @param   string  $lang     The lang code
     */
    public function setLang(string $lang): void;

}
