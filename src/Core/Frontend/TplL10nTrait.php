<?php

/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use ArrayObject;

trait TplL10nTrait
{
    /**
     * tpl:lang [string] : Localized string (tpl value)
     *
     * attributes:
     *
     *      - string      string to localized without quotes
     *
     * @param      ArrayObject<string, mixed>   $attr      The attributes
     * @param      string                       $str_attr  The attributes (one string form)
     */
    public function l10n(ArrayObject $attr, string $str_attr): string
    {
        # Normalize content
        $str_attr = (string) preg_replace('/\s+/x', ' ', $str_attr);

        return "<?= __('" . str_replace("'", "\\'", $str_attr) . "') ?>";
    }
}
