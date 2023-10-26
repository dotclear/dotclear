<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\App;

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
            if (!App::filter()->wiki()) {
                App::filter()->initWikiPost();
            }
            $html = App::formater()->callEditorFormater(My::id(), 'wiki', $wiki);
            $ret  = strlen($html) > 0;

            if ($ret) {
                $media_root = App::blog()->host();
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

        return [    // @phpstan-ignore-line
            'ret' => $ret,
            'msg' => $html,
        ];
    }
}
