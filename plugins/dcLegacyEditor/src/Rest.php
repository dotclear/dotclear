<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use dcCore;
use wiki2xhtml;
use xmlTag;

class Rest
{
    /**
     * Convert wiki to HTML REST service
     *
     * @param      dcCore  $core   The core
     * @param      array   $get    The get
     * @param      array   $post   The post
     *
     * @return     xmlTag  The xml tag.
     */
    public static function convert(dcCore $core, array $get, array $post): xmlTag
    {
        $wiki = $post['wiki'] ?? '';
        $rsp  = new xmlTag('wiki');

        $ret  = false;
        $html = '';
        if ($wiki !== '') {
            if (!(dcCore::app()->wiki2xhtml instanceof wiki2xhtml)) {
                dcCore::app()->initWikiPost();
            }
            $html = dcCore::app()->callFormater('wiki', $wiki);
            $ret  = strlen($html) > 0;

            if ($ret) {
                $media_root = dcCore::app()->blog->host;
                $html       = preg_replace_callback('/src="([^\"]*)"/', function ($matches) use ($media_root) {
                    if (!preg_match('/^http(s)?:\/\//', $matches[1])) {
                        // Relative URL, convert to absolute
                        return 'src="' . $media_root . $matches[1] . '"';
                    }
                    // Absolute URL, do nothing
                    return $matches[0];
                }, $html);
            }
        }

        $rsp->ret = $ret;
        $rsp->msg = $html;

        return $rsp;
    }
}
