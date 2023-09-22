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
 * @sicne   2.28
 */
class Lang implements LangInterface
{
    /**
     * The lang code.
     *
     * @var     string  $lang
     */
    protected $lang = self::DEFAULT_LANG;

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): void
    {
        $this->lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $lang) ? $lang : 'en';

        L10n::lang($this->lang);

        // deprecated since 2.28, use App::lang()->setLang() instead
        dcCore::app()->lang = $this->lang;
    }
}