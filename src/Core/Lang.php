<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use Dotclear\Helper\L10n;
use Dotclear\Interface\Core\LangInterface;

/**
 * @brief   Simple lang setter.
 *
 * @since   2.28, lang features have been grouped in this class
 */
class Lang implements LangInterface
{
    /**
     * The lang code.
     *
     * @var     string  $lang
     */
    protected $lang = self::DEFAULT_LANG;

    /**
     * Gets the current language.
     *
     * @return     string  The language.
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * Sets the language.
     *
     * @param      string  $lang   The language
     */
    public function setLang(string $lang): void
    {
        $this->lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $lang) ? $lang : 'en';

        L10n::lang($this->lang);

        // deprecated since 2.28, use App::lang()->setLang() instead
        dcCore::app()->lang = $this->lang;
    }
}
