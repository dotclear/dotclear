<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\App;
use Dotclear\Helper\Html\WikiToHtml;

/**
 * @brief   The module backend REST service.
 * @ingroup dcLegacyEditor
 */
class Rest
{
    /**
     * Convert wiki to HTML REST service (JSON).
     *
     * @param   array<string, mixed>   $get    The get
     * @param   array<string, mixed>   $post   The post
     *
     * @return  array{msg: null|string, ret: bool}
     */
    public static function convert(array $get, array $post): array
    {
        $wiki = $post['wiki'] ?? '';
        $ret  = false;
        $html = '';
        if ($wiki !== '') {
            if (!App::filter()->wiki() instanceof WikiToHtml) {
                App::filter()->initWikiPost();
            }
            $html = App::formater()->callEditorFormater(My::id(), 'wiki', $wiki);
            $ret  = strlen($html) > 0;

            if ($ret) {
                $media_root = App::blog()->host();
                $html       = preg_replace_callback('/src="([^\"]*)"/', function (array $matches) use ($media_root): string {
                    if (!preg_match('/^http(s)?:\/\//', $matches[1])) {
                        // Relative URL, convert to absolute
                        return 'src="' . $media_root . $matches[1] . '"';
                    }

                    // Absolute URL, do nothing
                    return $matches[0];
                }, $html);
            }
        }

        return [
            'ret' => $ret,
            'msg' => $html,
        ];
    }
}
