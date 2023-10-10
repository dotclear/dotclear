<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

/**
 * @brief   The module REST service handler.
 * @ingroup antispam
 */
class Rest
{
    /**
     * Gets the spams count.
     *
     * @return  array<string, mixed>   The spams count message
     */
    public static function getSpamsCount(): array
    {
        $count = Antispam::countSpam();
        if ($count > 0) {
            $str = sprintf(($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)'), $count);
        } else {
            $str = '';
        }

        return [
            'ret' => $str,
        ];
    }
}
