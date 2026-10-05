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
use Dotclear\Core\Content;
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
        $wiki = is_string($wiki = $post['wiki']) ? $wiki : '';
        $ret  = false;
        $html = '';
        if ($wiki !== '') {
            if (!App::filter()->wiki() instanceof WikiToHtml) {
                App::filter()->initWikiPost();
            }

            $html = App::formater()->callEditorFormater(My::id(), 'wiki', $wiki);

            # --BEHAVIOR-- coreContentFilter -- string, array<string[]> -- since 2.34
            # deprecated since 2.40 cope with coreContentFilterV2 instead
            App::behavior()->callBehavior('coreContentFilter', 'post', [
                [&$html, 'html'],
            ]);

            # --BEHAVIOR-- coreContentFilterV2 -- string, Content[] -- since 2.40
            $content = new Content('html', $html);
            App::behavior()->callBehavior(
                'coreContentFilterV2',
                'post',
                [
                    $content,
                ]
            );
            $html = $content->getContent();

            $ret = $html !== '';

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
