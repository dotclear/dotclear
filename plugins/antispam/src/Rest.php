<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use Dotclear\Helper\Html\XmlTag;

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class Rest
{
    /**
     * Gets the spams count.
     *
     * @return     XmlTag  The spams count.
     */
    public static function getSpamsCount(): XmlTag
    {
        $count = Antispam::countSpam();
        if ($count > 0) {
            $str = sprintf(($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)'), $count);
        } else {
            $str = '';
        }

        $rsp      = new XmlTag('count');
        $rsp->ret = $str;

        return $rsp;
    }
}
