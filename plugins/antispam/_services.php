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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class dcAntispamRest
{
    /**
     * Gets the spams count.
     *
     * @param      dcCore  $core   The core
     * @param      array   $get    The cleaned $_GET
     *
     * @return     xmlTag  The spams count.
     */
    public static function getSpamsCount(dcCore $core, $get)
    {
        $count = dcAntispam::countSpam($core);
        if ($count > 0) {
            $str = sprintf(($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)'), $count);
        } else {
            $str = '';
        }

        $rsp      = new xmlTag('count');
        $rsp->ret = $str;

        return $rsp;
    }
}
